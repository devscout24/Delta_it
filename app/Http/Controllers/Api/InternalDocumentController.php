<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\InternalDocument;
use App\Models\InternalDocumentFile;
use App\Models\InternalDocumentTags;
use App\Models\Tag;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class InternalDocumentController extends Controller
{
    use ApiResponse;
    public function index(Request $request)
    {
        $filter = $request->filter; // all | internal | company
        $companyId = $request->company_id; // optional company id

        $response = collect();

        if ($filter == 'all' || $filter == 'company') {
            $companyDocs = Document::with(['company', 'tags'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->get()
                ->map(function ($doc) {

                    $absolutePath = public_path($doc->document_path);
                    $fileSizeMB = File::exists($absolutePath)
                        ? number_format(File::size($absolutePath) / 1048576, 2) . ' MB'
                        : 'N/A';

                    return [
                        'id'            => $doc->id,
                        'source'        => 'company',
                        'type'          => $doc->document_type,
                        'name'          => $doc->document_name,

                        'company' => $doc->company ? [
                            'id'    => $doc->company->id,
                            'name'  => $doc->company->name,
                            'email' => $doc->company->email,
                        ] : null,

                        'files' => [[
                            'file_name' => basename($doc->document_path),
                            'file_type' => $doc->document_type,
                            'file_url'  => asset($doc->document_path),
                            'file_size_mb' => $fileSizeMB,
                        ]],

                        'tags' => $doc->tags->pluck('tag'),
                        'created_at' => $doc->created_at,
                    ];
                });

            $response = $response->merge($companyDocs);
        }
        if ($filter == 'all' || $filter == 'internal') {

            // internal docs do NOT belong to any company
            if ($companyId) {
                // skip internal docs because internal docs don't have company_id
            } else {
                $internalDocs = InternalDocument::with(['files', 'tags'])
                    ->get()
                    ->map(function ($doc) {

                        return [
                            'id'      => $doc->id,
                            'source'  => 'internal',
                            'type'    => $doc->type,
                            'name'    => $doc->name,
                            'company' => null,  // internal -> no company

                            'files' => $doc->files->map(function ($file) {
                                return [
                                    'file_name' => $file->file_name,
                                    'file_type' => $file->file_type,
                                    'file_url'  => asset($file->file_path),
                                ];
                            }),

                            'tags' => $doc->tags->pluck('tag'),
                            'created_at' => $doc->created_at,
                        ];
                    });

                $response = $response->merge($internalDocs);
            }
        }
        $sorted = $response->sortByDesc('created_at')->values();

        if ($sorted->isEmpty()) {
            return $this->error([], "No documents found", 404);
        }

        return $this->success($sorted, "Documents fetched successfully");
    }



    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'company_id' => 'nullable|exists:companies,id',
            'name'       => 'required_without:company_id|string|max:255',
            'type'       => 'nullable|string',
            'files'      => 'required_without:company_id|array',
            'files.*'    => 'file|max:5120',
            'tags'      => 'nullable|array',
            'tags.*'    => 'string|max:50|distinct',
        ]);

        if ($validated->fails()) {
            return $this->error($validated->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();


            if ($request->filled('company_id')) {

                foreach ($request->file('files') as $uploadedFile) {
                    $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                    $uploadedFile->move(public_path('uploads/documents'), $filename);

                    $document = Document::create([
                        'company_id'    => $request->company_id,
                        'document_name' => $request->name,
                        'document_type' => $request->type,
                        'document_path' => 'uploads/documents/' . $filename,
                    ]);

                    if ($request->filled('tags')) {
                        foreach ($request->tags as $tagName) {
                            Tag::firstOrCreate([
                                'document_id' => $document->id,
                                'tag'         => $tagName
                            ]);
                        }
                    }
                }
            } else {
                $document = InternalDocument::create([
                    'name' => $request->name,
                    'type' => $request->type,
                ]);

                foreach ($request->file('files') as $uploadedFile) {

                    $filename = time() . '_' . $uploadedFile->getClientOriginalName();
                    $uploadedFile->move(public_path('uploads/internal_documents'), $filename);

                    InternalDocumentFile::create([
                        'internal_document_id' => $document->id,
                        'file_path' => 'uploads/internal_documents/' . $filename,
                        'file_type' => $uploadedFile->getClientOriginalExtension(),
                        'file_name' => $uploadedFile->getClientOriginalName(),
                    ]);
                }

                if ($request->filled('tags')) {
                    foreach ($request->tags as $tagName) {
                        InternalDocumentTags::create([
                            'internal_document_id' => $document->id,
                            'tag' => $tagName
                        ]);
                    }
                }
            }

            DB::commit();
            return $this->success([], "Internal document created successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), 500);
        }
    }



    public function show($id)
    {
        $doc = InternalDocument::with(['files', 'tags', 'company'])->find($id);

        if (!$doc) {
            return $this->error([], "Document not found", 404);
        }

        $data = [
            'id' => $doc->id,
            'name' => $doc->name,

            'company' => [
                'id' => $doc->company->id,
                'name' => $doc->company->name,
            ],

            'tags' => $doc->tags->pluck('tag'),

            'files' => $doc->files->map(function ($file) {
                return [
                    'file_name' => $file->file_name,
                    'file_type' => $file->file_type,
                    'file_url'  => asset($file->file_path),
                ];
            }),
        ];

        return $this->success($data, "Document fetched successfully.");
    }
    public function destroy($id)
    {
        $doc = InternalDocument::with('files')->find($id);

        if (!$doc) {
            return $this->error([], "Document not found", 404);
        }

        // Delete physical files
        foreach ($doc->files as $file) {
            $absolutePath = public_path($file->file_path);
            if (File::exists($absolutePath)) {
                unlink($absolutePath);
            }
        }

        $doc->delete(); // cascade deletes files + tags

        return $this->success([], "Document deleted successfully.");
    }
}
