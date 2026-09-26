<?php

namespace App\Services;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Support\Collection;

class CustomerAddressService
{
    public function addressesFor(User $user): Collection
    {
        return $user->customerAddresses()
            ->with('location')
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();
    }

    public function create(User $user, array $data): CustomerAddress
    {
        $data['user_id'] = $user->id;

        $address = CustomerAddress::create($data);

        if (($data['is_default'] ?? false) || $user->customerAddresses()->count() === 1) {
            $this->makeDefault($address);
        }

        return $address->load('location');
    }

    public function update(CustomerAddress $address, array $data): CustomerAddress
    {
        $address->update($data);

        if (! empty($data['is_default'])) {
            $this->makeDefault($address);
        }

        return $address->fresh(['location']);
    }

    public function delete(CustomerAddress $address): void
    {
        $wasDefault = $address->is_default;

        $address->delete();

        if ($wasDefault) {
            $next = CustomerAddress::where('user_id', $address->user_id)
                ->orderByDesc('updated_at')
                ->first();

            if ($next) {
                $this->makeDefault($next);
            }
        }
    }

    public function makeDefault(CustomerAddress $address): void
    {
        CustomerAddress::where('user_id', $address->user_id)
            ->whereKeyNot($address->id)
            ->update(['is_default' => false]);

        if (! $address->is_default) {
            $address->update(['is_default' => true]);
        }
    }
}
