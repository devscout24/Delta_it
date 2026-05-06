<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    public function run()
    {
        $tags = [
            'Contract',
            'Invoice',
            'Legal',
            'Finance',
            'HR',
            'Operations',
            'Policy',
            'Report',
            'Agreement',
            'Compliance',
            'IT',
            'Marketing',
        ];

        foreach ($tags as $name) {
            Tag::firstOrCreate(['name' => $name]);
        }
    }
}
