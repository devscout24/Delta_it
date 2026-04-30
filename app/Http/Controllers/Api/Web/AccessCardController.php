<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\AccessCard;
use App\Models\Company;

class AccessCardController extends Controller
{
    use ApiResponse;

    // ======================
    // GET ACCESS CARD DATA
    // ======================
    public function show($company_id)
    {
        if (!Company::where('id', $company_id)->exists()) {
            return $this->error([], 'Company not found', 404);
        }

        $card = AccessCard::where('company_id', $company_id)->first();

        return $this->success([
            'active_cards' => $card->active_card ?? 0,
            'lost_damage_cards' => $card->lost_damage_card ?? 0,
            'active_parking_cards' => $card->active_parking_card ?? 0,
            'max_parking_cards' => $card->max_parking_card ?? 0,
        ], 'Access card data');
    }

    // ======================
    // UPDATE ACCESS CARD
    // ======================
    public function update(Request $request, $company_id)
    {
        if (!Company::where('id', $company_id)->exists()) {
            return $this->error([], 'Company not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'active_cards' => 'nullable|integer|min:0',
            'lost_damage_cards' => 'nullable|integer|min:0',
            'active_parking_cards' => 'nullable|integer|min:0',
            'max_parking_cards' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $data = $validator->validated();

        $card = AccessCard::firstOrNew([
            'company_id' => $company_id
        ]);

        $card->active_card = $data['active_cards'] ?? $card->active_card ?? 0;
        $card->lost_damage_card = $data['lost_damage_cards'] ?? $card->lost_damage_card ?? 0;
        $card->active_parking_card = $data['active_parking_cards'] ?? $card->active_parking_card ?? 0;
        $card->max_parking_card = $data['max_parking_cards'] ?? $card->max_parking_card ?? 0;

        $card->save();

        return $this->success([
            'active_cards' => $card->active_card,
            'lost_damage_cards' => $card->lost_damage_card,
            'active_parking_cards' => $card->active_parking_card,
            'max_parking_cards' => $card->max_parking_card,
        ], 'Updated');
    }
}
