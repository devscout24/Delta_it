<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Document;
use App\Models\User;
use App\Models\Company;

class DocumentSeeder extends Seeder
{
    public function run()
    {
        $admin = User::where('role', 'admin')->first();
        $companies = Company::all();

        // Internal documents
        Document::create([
            'company_id' => null,
            'uploaded_by' => $admin->id,
            'name' => 'Building Rules',
            'file_path' => 'documents/rules.pdf',
            'type' => 'pdf',
            'visibility' => 'internal',
        ]);

        Document::create([
            'company_id' => null,
            'uploaded_by' => $admin->id,
            'name' => 'Safety Guidelines',
            'file_path' => 'documents/safety.pdf',
            'type' => 'pdf',
            'visibility' => 'internal',
        ]);

        // Company documents
        foreach ($companies as $company) {

            $user = User::where('company_id', $company->id)->first();

            if (!$user) continue;

            Document::create([
                'company_id' => $company->id,
                'uploaded_by' => $user->id,
                'name' => $company->name . ' Contract Copy',
                'file_path' => 'documents/company_doc.pdf',
                'type' => 'pdf',
                'visibility' => 'company',
            ]);
        }
    }
}
