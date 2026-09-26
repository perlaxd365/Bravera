<?php

namespace App\Livewire\Account;

use App\Models\CustomerAddress;
use App\Modules\Location\Services\LocationService;
use App\Services\CustomerAddressService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.account')]
class Addresses extends Component
{
    public $addresses;

    public bool $showForm = false;

    public ?int $editingId = null;

    public bool $editing = false;

    public array $departments = [];

    public array $provinces = [];

    public array $districts = [];

    public string $fullName = '';

    public string $phone = '';

    public ?int $departmentId = null;

    public ?int $provinceId = null;

    public ?int $districtId = null;

    public string $address = '';

    public string $reference = '';

    public bool $isDefault = false;

    public function mount(): void
    {
        $this->refresh();
    }

    public function newAddress(): void
    {
        $this->reset(['editingId', 'editing', 'fullName', 'phone', 'departmentId', 'provinceId', 'districtId', 'address', 'reference']);
        $this->isDefault = false;
        $this->departments = app(LocationService::class)->getDepartments()->toArray();
        $this->showForm = true;
        $this->resetValidation();
    }

    public function editAddress(int $id): void
    {
        $address = CustomerAddress::find($id);

        if (! $address || $address->user_id !== auth()->id()) {
            return;
        }

        $this->editingId = $id;
        $this->editing = true;
        $this->fullName = $address->full_name;
        $this->phone = $address->phone ?? '';
        $this->address = $address->address;
        $this->reference = $address->reference ?? '';
        $this->isDefault = $address->is_default || $this->addresses->count() === 1;

        $this->departments = app(LocationService::class)->getDepartments()->toArray();

        $location = app(LocationService::class)->findWithHierarchy($address->location_id);

        if ($location) {
            $this->districtId = $location->id;

            if ($location->parent) {
                $this->provinceId = $location->parent->id;
                $this->provinces = app(LocationService::class)->getProvinces($location->parent->parent->id)->toArray();

                if ($location->parent->parent) {
                    $this->departmentId = $location->parent->parent->id;
                    $this->provinces = app(LocationService::class)->getProvinces($this->departmentId)->toArray();
                }

                $this->districts = app(LocationService::class)->getDistricts($this->provinceId)->toArray();
            }
        }

        $this->showForm = true;
        $this->resetValidation();
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->editing = false;
        $this->editingId = null;
    }

    public function updatedDepartmentId($value): void
    {
        $this->provinceId = null;
        $this->districtId = null;
        $this->provinces = [];
        $this->districts = [];

        if ($value) {
            $this->provinces = app(LocationService::class)->getProvinces((int) $value)->toArray();
        }
    }

    public function updatedProvinceId($value): void
    {
        $this->districtId = null;
        $this->districts = [];

        if ($value) {
            $this->districts = app(LocationService::class)->getDistricts((int) $value)->toArray();
        }
    }

    public function save(): void
    {
        $this->validate([
            'fullName' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'districtId' => ['required', 'integer'],
            'address' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $service = app(CustomerAddressService::class);

        $data = [
            'full_name' => $this->fullName,
            'phone' => $this->phone,
            'location_id' => $this->districtId,
            'address' => $this->address,
            'reference' => $this->reference ?: null,
            'is_default' => $this->isDefault,
        ];

        if ($this->editingId) {
            $address = CustomerAddress::find($this->editingId);

            if (! $address || $address->user_id !== auth()->id()) {
                return;
            }

            $service->update($address, $data);
            $message = 'Dirección actualizada.';
        } else {
            $service->create(auth()->user(), $data);
            $message = 'Dirección creada.';
        }

        $this->dispatch('notify', ['type' => 'success', 'message' => $message]);

        $this->cancelForm();
        $this->refresh();
    }

    public function makeDefault(int $id): void
    {
        $address = CustomerAddress::find($id);

        if ($address && $address->user_id === auth()->id()) {
            app(CustomerAddressService::class)->makeDefault($address);
            $this->refresh();
        }
    }

    public function deleteAddress(int $id): void
    {
        $address = CustomerAddress::find($id);

        if ($address && $address->user_id === auth()->id()) {
            app(CustomerAddressService::class)->delete($address);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Dirección eliminada.']);
            $this->cancelForm();
            $this->refresh();
        }
    }

    private function refresh(): void
    {
        $this->addresses = app(CustomerAddressService::class)->addressesFor(auth()->user());
    }

    public function render()
    {
        return view('livewire.account.addresses');
    }
}
