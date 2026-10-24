<?php

namespace App\Services;

use App\Models\Favorite;
use App\Models\Product;
use App\Models\User;

class FavoriteService
{
    /** @var array<int, array<int, int>> */
    private array $cache = [];

    /**
     * @return array<int, int>
     */
    public function productIdsFor(?int $userId): array
    {
        if (! $userId) {
            return [];
        }

        return $this->cache[$userId] ??= Favorite::query()
            ->where('user_id', $userId)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function toggle(User $user, Product $product): bool
    {
        $favorite = Favorite::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            unset($this->cache[$user->id]);

            return false;
        }

        Favorite::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
        unset($this->cache[$user->id]);

        return true;
    }
}
