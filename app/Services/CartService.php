<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\SupplierVariant;
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
            $sessionCart = Cart::active()
                ->whereKey($sessionCartId)
                ->where('user_id', Auth::id())
                ->first();

            if ($sessionCart) {
                return $sessionCart;
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
            'unit_price' => $variant->sale_price,
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
        $available = fn() => $variant->supplierVariants()->available();

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
                'Stock disponible: ' . $supplierVariant->availableStock() . ' unidades.'
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
        return $this->currentCart()->activeItems()->get()->sortBy('created_at');
    }

    public function count(): int
    {
        return $this->items()->sum('quantity');
    }

    public function subtotal(): float
    {
        return round($this->items()->sum(fn($item) => $item->unit_price * $item->quantity), 2);
    }

    public function costTotal(): float
    {
        return round(
            $this->items()->sum(fn($item) => ($item->unit_cost + $item->supplier_shipping_cost) * $item->quantity),
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
