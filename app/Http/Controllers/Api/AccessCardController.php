<?php

namespace App\Http\Controllers\Api;

use App\Models\AccessCard;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

class AccessCardController extends Controller
{
    use ApiResponse;
    public function updateAccessCode(Request $request)
    {
        // Validate the request
        $request->validate([
            'active_card' => 'sometimes|integer|min:0',
            'lost_damage_card' => 'sometimes|integer|min:0',
            'active_parking_card' => 'sometimes|integer|min:0',
            'max_parking_card' => 'sometimes|integer|min:0',
        ]);

        // Check if any access card row exists
        $accessCard = AccessCard::first();

        if ($accessCard) {
            // Row exists → update it
            $accessCard->update($request->only([
                'active_card',
                'lost_damage_card',
                'active_parking_card',
                'max_parking_card'
            ]));
            $message = 'Access card updated successfully';
        } else {
            // No row → create it
            $accessCard = AccessCard::create($request->only([
                'active_card',
                'lost_damage_card',
                'active_parking_card',
                'max_parking_card'
            ]));
            $message = 'Access card created successfully';
        }
        $accessCard = AccessCard::first();

        return $this->success($accessCard, $message, 200);
    }


    // mobile api 
    public function getCardStats()
    {
        $data = AccessCard::select('active_card', 'lost_damage_card', 'active_parking_card', 'max_parking_card')->first();
        if (!$data) {
            return $this->error(null, 'Data not found', 404);
        }

        return $this->success(
            $data,
            'Access card data fetched successfully',
            200
        );
    }
}
