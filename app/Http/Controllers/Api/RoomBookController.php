<?php

namespace App\Http\Controllers\Api;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Services\SlotService;
use App\Http\Controllers\Controller;

class RoomBookController extends Controller
{
    use ApiResponse;

    protected $slotService;
    public function __construct(SlotService $slotService)
    {
        $this->slotService = $slotService;
    }

    


}
