<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopController extends Controller
{
    public function getProducts()
    {
        return response()->json(Product::all());
    }

    public function createOrder(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'fulfillment_type' => 'required|in:pickup,delivery',
            'delivery_address' => 'required_if:fulfillment_type,delivery|nullable|string|max:1000',
            'phone_number' => 'required_if:fulfillment_type,delivery|nullable|string|max:50',
            'pts_used' => 'required|integer|min:0',
        ]);

        $result = DB::transaction(function () use ($validated, $user) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $product = Product::whereKey($validated['product_id'])->lockForUpdate()->firstOrFail();
            $pointsToUse = (int) $validated['pts_used'];

            if ($pointsToUse !== 0 && $pointsToUse !== (int) $product->pts_price) {
                return ['error' => 'Invalid points amount for this product.', 'status' => 422];
            }

            if ($pointsToUse > $lockedUser->coins) {
                return ['error' => 'You do not have enough points for this purchase.', 'status' => 422];
            }

            $cashCents = (int) round((float) $product->price * 100);
            if ($pointsToUse > 0) {
                $cashCents -= (int) round((float) $product->pts_discount * 100);
            }

            if ($validated['fulfillment_type'] === 'delivery') {
                $cashCents += 80;
            }

            $cashAmount = number_format(max(0, $cashCents) / 100, 2, '.', '');

            $lockedUser->coins -= $pointsToUse;
            $lockedUser->save();

            $order = Order::create([
                'user_id' => $lockedUser->id,
                'product_id' => $product->id,
                'fulfillment_type' => $validated['fulfillment_type'],
                'delivery_address' => $validated['delivery_address'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'pts_used' => $pointsToUse,
                'final_cash_amount' => $cashAmount,
                'status' => 'pending_payment',
            ]);

            return ['order' => $order, 'remaining_coins' => $lockedUser->coins];
        });

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['status']);
        }

        return response()->json([
            'message' => 'Order placed successfully.',
            'order' => $result['order'],
            'remaining_coins' => $result['remaining_coins'],
        ]);
    }
}
