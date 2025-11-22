<?php

namespace App\Http\Controllers\Api;

use App\Models\AccessCard;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AccessCardController extends Controller
{
    use ApiResponse;
    public function updateAccessCode(Request $request, $company_id)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'active_card'         => 'sometimes|integer|min:0',
            'lost_damage_card'    => 'sometimes|integer|min:0',
            'active_parking_card' => 'sometimes|integer|min:0',
            'max_parking_card'    => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error([], $validator->errors(), 422);
        }

        // Get or create specific company's access card row
        $accessCard = AccessCard::firstOrNew([
            'company_id' => $company_id
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

    public function getCardStats($id)
    {
        $card = AccessCard::where('company_id', $id)
            ->select('active_card', 'lost_damage_card', 'active_parking_card', 'max_parking_card')
            ->first();

        if (!$card) {
            return $this->error(null, 'Access card data not found', 404);
        }

        return $this->success($card, 'Access card data fetched successfully', 200);
    }
}
