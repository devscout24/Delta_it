<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Document;
use App\Models\Tag;
use App\Models\User;
use App\Models\Company;

class DocumentSeeder extends Seeder
{
    public function run()
    {
        $admin = User::where('role', 'admin')->first();
        $companies = Company::all();

        $allTagIds = Tag::pluck('id')->toArray();

        // Internal documents
        $doc1 = Document::create([
            'company_id'  => null,
            'uploaded_by' => $admin->id,
            'name'        => 'Building Rules',
            'file_path'   => 'documents/rules.pdf',
            'type'        => 'pdf',
            'visibility'  => 'internal',
        ]);
        $doc1->tags()->sync(array_slice($allTagIds, 0, 2));

        $doc2 = Document::create([
            'company_id'  => null,
            'uploaded_by' => $admin->id,
            'name'        => 'Safety Guidelines',
            'file_path'   => 'documents/safety.pdf',
            'type'        => 'pdf',
            'visibility'  => 'internal',
        ]);
        $doc2->tags()->sync(array_slice($allTagIds, 2, 2));

        // Company documents
        foreach ($companies as $index => $company) {
            $user = User::where('company_id', $company->id)->first();

            if (!$user) continue;

            $doc = Document::create([
                'company_id'  => $company->id,
                'uploaded_by' => $user->id,
                'name'        => $company->name . ' Contract Copy',
                'file_path'   => 'documents/company_doc.pdf',
                'type'        => 'pdf',
                'visibility'  => 'company',
            ]);

            // rotate through tags so each doc gets a different pair
            $offset = ($index * 2) % count($allTagIds);
            $doc->tags()->sync(array_slice($allTagIds, $offset, 2));
        }
    }
}
