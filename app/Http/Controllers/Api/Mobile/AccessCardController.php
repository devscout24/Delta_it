<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\AccessCard;

class AccessCardController extends Controller
{
    use ApiResponse;

    // ======================
    // GET ACCESS CARD STATS
    // ======================
    public function index()
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $card = AccessCard::where('company_id', $user->company_id)
            ->first();

        if (!$card) {
            return $this->success([
                'active_cards' => 0,
                'lost_damage_cards' => 0,
                'active_parking_cards' => 0,
                'max_parking_cards' => 0,
                'available_parking_cards' => 0,
            ], 'No access card data found');
        }

        $availableParking = $card->max_parking_card - $card->active_parking_card;

        return $this->success([
            'active_cards' => $card->active_card,
            'lost_damage_cards' => $card->lost_damage_card,
            'active_parking_cards' => $card->active_parking_card,
            'max_parking_cards' => $card->max_parking_card,
            'available_parking_cards' => max(0, $availableParking),
        ], 'Access card stats fetched');
    }
}
