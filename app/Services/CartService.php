<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\SupplierVariant;
use App\Modules\Ordering\Services\OrderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CartService
{
    public const SESSION_KEY = 'brevare_cart_id';

    /**
     * Obtiene (o crea) el carrito activo del cliente/sesión.
     */
    public function currentCart(): Cart
    {
        if (! Auth::check()) {
            return $this->sessionCart();
        }

        /*
     * Si existe un carrito explícitamente asociado a la sesión,
     * ese es el carrito actual.
     *
     * Esto es importante porque puede estar asociado a una orden
     * PENDING que el cliente acaba de dejar abierta en Culqi.
     */
        $sessionCartId = Session::get(self::SESSION_KEY);

        if ($sessionCartId) {
            $sessionCart = Cart::active()->whereKey($sessionCartId)->first();

            if ($sessionCart) {
                if ((int) $sessionCart->user_id === (int) Auth::id()) {
                    return $sessionCart;
                }

                // El comprador agregó productos antes de entrar. Ese carrito
                // tiene prioridad sobre un carrito vacío de su cuenta: es la
                // compra que acaba de preparar y espera retomar al volver.
                if ($sessionCart->user_id === null && $sessionCart->activeItems()->exists()) {
                    return $this->adoptGuestCart($sessionCart);
                }
            }
        }

        /*
     * Si no existe carrito en sesión, buscamos un carrito activo
     * que NO esté bloqueado por una orden pendiente.
     *
     * No reutilizamos aquí un carrito pendiente antiguo porque
     * podría pertenecer a otro intento de pago.
     */
        $cart = Cart::active()
            ->where('user_id', Auth::id())
            ->whereDoesntHave('orders', function ($query) {
                $query
                    ->where('status', OrderStatus::PENDING)
                    ->where('payment_status', PaymentStatus::PENDING);
            })
            ->latest()
            ->first();

        if ($cart) {
            Session::put(self::SESSION_KEY, $cart->id);

            return $cart;
        }

        return $this->adoptSessionCart();
    }

    /**
     * Vincula el carrito de invitado a la cuenta recién autenticada.
     *
     * Si la cuenta ya tiene un carrito activo, los productos se trasladan a
     * ese carrito para no perder ninguna de las dos selecciones. Si no existe,
     * el propio carrito de invitado pasa a ser el carrito del cliente.
     */
    private function adoptGuestCart(Cart $guestCart): Cart
    {
        $userCart = Cart::active()
            ->where('user_id', Auth::id())
            ->whereKeyNot($guestCart->id)
            ->whereDoesntHave('orders', function ($query) {
                $query
                    ->where('status', OrderStatus::PENDING)
                    ->where('payment_status', PaymentStatus::PENDING);
            })
            ->latest()
            ->first();

        if ($userCart === null) {
            $guestCart->update([
                'user_id' => Auth::id(),
                'session_id' => null,
            ]);

            Session::put(self::SESSION_KEY, $guestCart->id);

            return $guestCart;
        }

        foreach ($guestCart->items()->get() as $item) {
            $existing = CartItem::query()
                ->where('cart_id', $userCart->id)
                ->where('product_variant_id', $item->product_variant_id)
                ->where('supplier_variant_id', $item->supplier_variant_id)
                ->first();

            if ($existing) {
                $existing->increment('quantity', (int) $item->quantity);
                $item->delete();

                continue;
            }

            $item->update(['cart_id' => $userCart->id]);
        }

        $guestCart->delete();
        Session::put(self::SESSION_KEY, $userCart->id);

        return $userCart;
    }

    /**
     * Si el comprador vuelve al carrito desde un pago pendiente, cancela ese
     * intento, libera el stock reservado y reactiva el carrito original.
     */
    public function restorePendingCartForEditing(OrderService $orders): void
    {
        if (! Auth::check()) {
            return;
        }

        $cartId = Session::get(self::SESSION_KEY);

        if (! $cartId) {
            return;
        }

        $cart = Cart::query()
            ->whereKey($cartId)
            ->where('user_id', Auth::id())
            ->where('status', 'converted')
            ->first();

        // La sesión puede apuntar a un carrito vacío creado mientras el pago
        // estaba pendiente. En ese caso se recupera el último carrito
        // convertido que sí pertenece a un pedido esperando pago.
        if (! $cart) {
            $cart = Cart::query()
                ->where('user_id', Auth::id())
                ->where('status', 'converted')
                ->whereHas('orders', function ($query) {
                    $query
                        ->where('status', OrderStatus::PENDING)
                        ->where('payment_status', PaymentStatus::PENDING);
                })
                ->latest('updated_at')
                ->first();

            if ($cart) {
                Session::put(self::SESSION_KEY, $cart->id);
            }
        }

        if (! $cart) {
            return;
        }

        $order = $cart->orders()->latest('id')->first();

        if ($order?->payment_status === PaymentStatus::PAID) {
            return;
        }

        if (
            $order?->status === OrderStatus::PENDING
            && $order->payment_status === PaymentStatus::PENDING
        ) {
            $orders->abandonPendingOrder(
                $order,
                'El comprador volvió al carrito antes de completar el pago.'
            );

            $order->refresh();

            if ($order->payment_status === PaymentStatus::PAID) {
                return;
            }
        }

        $cart->update(['status' => 'active']);
    }

    /**
     * Obtiene un carrito que puede ser modificado.
     *
     * Si el carrito actual pertenece a una orden pendiente,
     * NO se modifica esa compra. Se crea un carrito nuevo para
     * los productos que el cliente quiera agregar posteriormente.
     */
    private function writableCart(): Cart
    {
        $cart = $this->currentCart();

        $hasPendingOrder = $cart->orders()
            ->where('status', OrderStatus::PENDING)
            ->where('payment_status', PaymentStatus::PENDING)
            ->exists();

        if (! $hasPendingOrder) {
            return $cart;
        }

        /*
     * El carrito anterior está reservado para el pedido pendiente.
     * Creamos un carrito nuevo para una nueva compra.
     */
        $newCart = Cart::create([
            'user_id' => Auth::id(),
            'status' => 'active',
        ]);

        Session::put(self::SESSION_KEY, $newCart->id);

        return $newCart;
    }

    private function sessionCart(): Cart
    {
        $cartId = Session::get(self::SESSION_KEY);

        if ($cartId) {
            $cart = Cart::active()->where('id', $cartId)->first();

            if ($cart) {
                return $cart;
            }
        }

        $cart = Cart::create([
            'session_id' => Str::random(40),
            'status' => 'active',
        ]);

        Session::put(self::SESSION_KEY, $cart->id);

        return $cart;
    }

    /**
     * Si el cliente tiene carrito de sesión, lo adopta; sino crea uno nuevo.
     */
    private function adoptSessionCart(): Cart
    {
        $sessionCartId = Session::get(self::SESSION_KEY);

        if ($sessionCartId) {
            $sessionCart = Cart::active()->where('id', $sessionCartId)->first();

            if ($sessionCart && $sessionCart->user_id === null) {
                $sessionCart->update([
                    'user_id' => Auth::id(),
                    'session_id' => null,
                ]);

                /*
     * Conservamos el ID en sesión.
     *
     * Así, después de iniciar sesión, este sigue siendo
     * explícitamente el carrito actual del comprador.
     */
                Session::put(self::SESSION_KEY, $sessionCart->id);

                return $sessionCart;
            }
        }

        return Cart::create([
            'user_id' => Auth::id(),
            'status' => 'active',
        ]);
    }

    /**
     * Agrega una variante al carrito con su proveedor.
     */
    public function add(
        int $productVariantId,
        ?int $supplierVariantId = null,
        int $quantity = 1,
    ): CartItem {
        $cart = $this->writableCart();

        $variant = ProductVariant::query()
            ->where('is_active', true)
            ->findOrFail($productVariantId);

        $supplierVariant = $this->resolveSupplierVariant(
            $variant,
            $supplierVariantId,
            $quantity
        );

        $existing = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variant->id)
            ->where('supplier_variant_id', $supplierVariant->id)
            ->first();

        $newQuantity = ($existing?->quantity ?? 0) + $quantity;

        $available = $supplierVariant->availableStock();

        if ($newQuantity > $available) {
            abort(422, "Stock disponible: {$available} unidades.");
        }

        $values = [
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'supplier_variant_id' => $supplierVariant->id,
            'quantity' => $newQuantity,
            'unit_price' => round((float) $variant->sale_price, 2),
            'unit_cost' => $supplierVariant->cost_price,
            'supplier_shipping_cost' => $supplierVariant->shipping_cost,
        ];

        if ($existing) {
            $existing->update($values);

            return $existing->fresh();
        }

        return CartItem::create($values);
    }

    /**
     * Resuelve el proveedor para una variante:
     * una variante específica, el proveedor principal
     * o el primer proveedor con stock disponible.
     */
    public function resolveSupplierVariant(
        ProductVariant $variant,
        ?int $supplierVariantId = null,
        int $quantity = 1,
    ): SupplierVariant {
        // Cada intento necesita su propia consulta: reutilizar el mismo builder
        // arrastra el where y el limit de la consulta anterior, y la búsqueda
        // termina sin resultados aunque haya stock disponible.
        $available = fn () => $variant->supplierVariants()->available();

        if ($supplierVariantId) {
            $supplierVariant = $available()
                ->whereKey($supplierVariantId)
                ->first();

            if ($supplierVariant) {
                return $supplierVariant;
            }
        }

        $supplierVariant = $available()
            ->default()
            ->first();

        if ($supplierVariant) {
            return $supplierVariant;
        }

        return $available()
            ->orderByDesc('stock')
            ->firstOrFail();
    }

    public function updateQuantity(int $itemId, int $quantity): void
    {
        $cart = $this->writableCart();

        $item = CartItem::query()
            ->where('cart_id', $cart->id)
            ->whereKey($itemId)
            ->first();

        if (! $item) {
            return;
        }

        if ($quantity <= 0) {
            $item->delete();

            return;
        }

        $supplierVariant = $item->supplierVariant;

        if ($supplierVariant && $quantity > $supplierVariant->availableStock()) {
            abort(
                422,
                'Stock disponible: '.$supplierVariant->availableStock().' unidades.'
            );
        }

        $item->update([
            'quantity' => $quantity,
        ]);
    }

    public function remove(int $itemId): void
    {
        $cart = $this->writableCart();

        CartItem::where('cart_id', $cart->id)
            ->whereKey($itemId)
            ->delete();
    }

    public function items()
    {
        $items = $this->currentCart()->activeItems()->get()->sortBy('created_at');

        // El precio del carrito es una copia del precio de la variante. Si el
        // catálogo cambia antes de pagar, el carrito debe reflejar el precio
        // vigente y no conservar una cotización vieja que ya no está a la venta.
        foreach ($items as $item) {
            $variant = $item->variant;

            if ($variant === null) {
                continue;
            }

            $values = [];
            $salePrice = round((float) $variant->sale_price, 2);

            if (abs((float) $item->unit_price - $salePrice) > 0.001) {
                $values['unit_price'] = $salePrice;
            }

            $supplierVariant = $item->supplierVariant;

            if ($supplierVariant !== null) {
                $cost = round((float) $supplierVariant->cost_price, 2);
                $shipping = round((float) $supplierVariant->shipping_cost, 2);

                if (abs((float) $item->unit_cost - $cost) > 0.001) {
                    $values['unit_cost'] = $cost;
                }

                if (abs((float) $item->supplier_shipping_cost - $shipping) > 0.001) {
                    $values['supplier_shipping_cost'] = $shipping;
                }
            }

            if ($values !== []) {
                $item->update($values);
            }
        }

        return $items;
    }

    public function count(): int
    {
        return $this->items()->sum('quantity');
    }

    public function subtotal(): float
    {
        return round($this->items()->sum(fn ($item) => $item->unit_price * $item->quantity), 2);
    }

    public function costTotal(): float
    {
        return round(
            $this->items()->sum(fn ($item) => ($item->unit_cost + $item->supplier_shipping_cost) * $item->quantity),
            2
        );
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function clear(): void
    {
        $cart = $this->writableCart();

        CartItem::where('cart_id', $cart->id)->delete();
    }

    /**
     * Marca el carrito como convertido (ya generó pedido).
     */
    public function markAsConverted(): void
    {
        $this->currentCart()->update(['status' => 'converted']);
    }
}
