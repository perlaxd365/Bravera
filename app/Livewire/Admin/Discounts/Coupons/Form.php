<?php

namespace App\Livewire\Admin\Discounts\Coupons;

use App\Livewire\Forms\CouponForm;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Supplier;
use Livewire\Attributes\On;
use Livewire\Component;

class Form extends Component
{
    public CouponForm $form;

    public bool $show = false;

    public array $applyOptions = [];

    public function mount(): void
    {
        $this->applyOptions = [
            'suppliers' => Supplier::query()->pluck('business_name', 'id')->toArray(),
            'products' => Product::query()->pluck('name', 'id')->toArray(),
            'categories' => Category::query()->pluck('name', 'id')->toArray(),
        ];
    }

    #[On('coupon-create')]
    public function create(): void
    {
        $this->form->resetForm();
        $this->form->generateCode();
        $this->resetValidation();
        $this->show = true;
    }

    #[On('coupon-edit')]
    public function edit(int $id): void
    {
        $coupon = Coupon::findOrFail($id);
        $this->form->fromModel($coupon);
        $this->resetValidation();
        $this->show = true;
    }

    public function save(): void
    {
        $this->form->validate();

        $data = $this->form->toData();
        $data['created_by'] = auth()->id();

        if ($this->form->id) {
            $coupon = Coupon::findOrFail($this->form->id);
            $coupon->update($data);
            $message = 'Cupón actualizado correctamente.';
        } else {
            Coupon::create($data);
            $message = 'Cupón creado correctamente.';
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message,
        ]);

        $this->dispatch('coupon-saved');

        $this->form->resetForm();
        $this->show = false;
    }

    public function render()
    {
        return view('livewire.admin.discounts.coupons.form');
    }
}
