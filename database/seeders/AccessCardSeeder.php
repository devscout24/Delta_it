<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AccessCard;
use App\Models\Company;

class AccessCardSeeder extends Seeder
{
    public function run()
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            AccessCard::create([
                'company_id' => $company->id,
                'active_card' => rand(5, 15),
                'lost_damage_card' => rand(0, 2),
                'active_parking_card' => rand(2, 10),
                'max_parking_card' => 15,
            ]);
        }
    }
}
