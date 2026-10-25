<?php

namespace App\Http\Controllers\Store;

use App\Enums\PaymentStatus;
use App\Models\CourseAsset;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseAssetController
{
    public function __invoke(Order $order, CourseAsset $asset): RedirectResponse|StreamedResponse
    {
        abort_unless(auth()->check() && $order->user_id === auth()->id(), 403);
        abort_unless($order->payment_status === PaymentStatus::PAID, 403);
        abort_unless($asset->is_active, 404);

        $ownsCourse = $order->activeItems()
            ->where('product_id', $asset->product_id)
            ->exists();

        abort_unless($ownsCourse, 403);

        if (! $asset->isFile()) {
            abort_unless(filled($asset->url), 404);

            return redirect()->away($asset->url);
        }

        abort_unless(filled($asset->file_path) && Storage::disk('local')->exists($asset->file_path), 404);

        return Storage::disk('local')->download(
            $asset->file_path,
            $asset->file_name ?: 'material-del-curso',
        );
    }
}
