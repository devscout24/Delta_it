<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\AccessCard;

class AccessCardController extends Controller
{
    use ApiResponse;

    public function show($company_id)
    {
        $card = AccessCard::where('company_id', $company_id)->first();

        return $this->success($card, 'Access card data');
    }

    public function update(Request $request, $company_id)
    {
        $data = $request->validate([
            'active_card' => 'nullable|integer',
            'lost_damage_card' => 'nullable|integer',
            'active_parking_card' => 'nullable|integer',
            'max_parking_card' => 'nullable|integer',
        ]);

        $card = AccessCard::updateOrCreate(
            ['company_id' => $company_id],
            $data
        );

        return $this->success($card, 'Updated');
    }
}
