<?php

namespace App\Http\Controllers\Api;

use App\Models\AccessCard;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;

class AccessCardController extends Controller
{
    use ApiResponse;
    public function updateAccessCode(Request $request)
    {
        // Validate request
        $request->validate([
            'company_id'          => 'required|exists:companies,id',
            'active_card'         => 'sometimes|integer|min:0',
            'lost_damage_card'    => 'sometimes|integer|min:0',
            'active_parking_card' => 'sometimes|integer|min:0',
            'max_parking_card'    => 'sometimes|integer|min:0',
        ]);

        // Get or create specific company's access card row
        $accessCard = AccessCard::firstOrNew([
            'company_id' => $request->company_id
        ]);

        // Fill only valid updatable fields
        $accessCard->fill($request->only([
            'active_card',
            'lost_damage_card',
            'active_parking_card',
            'max_parking_card',
        ]));

        // Save the row
        $accessCard->save();

        $message = $accessCard->wasRecentlyCreated
            ? 'Access card created successfully'
            : 'Access card updated successfully';

        return $this->success($accessCard, $message, 200);
    }

    public function getCardStats()
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error(null, 'User not associated with any company', 403);
        }

        $card = AccessCard::where('company_id', $user->company_id)
            ->select('active_card', 'lost_damage_card', 'active_parking_card', 'max_parking_card')
            ->first();

        if (!$card) {
            return $this->error(null, 'Access card data not found', 404);
        }

        return $this->success($card, 'Access card data fetched successfully', 200);
    }
}
