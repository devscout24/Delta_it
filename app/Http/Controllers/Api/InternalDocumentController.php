<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\InternalDocument;
use App\Models\InternalDocumentFile;
use App\Models\InternalDocumentTags;
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

        // -----------------------------
        // COMPANY DOCUMENTS
        // -----------------------------
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
                        'id'   => $doc->id,
                        'type' => $doc->document_type ?? null,
                        'name' => $doc->document_name,

                        'company' => [
                            'id'    => $doc->company->id,
                            'name'  => $doc->company->name,
                            'email' => $doc->company->email,
                        ],

                        'document_type' => $doc->document_type,
                        'file_url'      => asset($doc->document_path),
                        'file_size_mb'  => $fileSizeMB,

                        'tags' => $doc->tags->pluck('tag'),
                        'created_at' => $doc->created_at,
                    ];
                });

            $response = $response->merge($companyDocs);
        }

        // -----------------------------
        // INTERNAL DOCUMENTS
        // -----------------------------
        if ($filter == 'all' || $filter == 'internal') {
            $internalDocs = InternalDocument::with(['company', 'files', 'tags'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->get()
                ->map(function ($doc) {

                    return [
                        'id'   => $doc->id,
                        'type' => $doc->type,
                        'name' => $doc->name,

                        'company' => [
                            'id'   => $doc->company->id,
                            'name' => $doc->company->name,
                        ],

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

        // -----------------------------
        // SORT FINAL LIST BY DATE
        // -----------------------------
        $sorted = $response->sortByDesc('created_at')->values();

        if ($sorted->isEmpty()) {
            return $this->error([], "No documents found", 404);
        }

        return $this->success($sorted, "Documents fetched successfully");
    }


    public function store(Request $request, $company_id)
    {
        $validated = Validator::make($request->all(), [
            'name'  => 'required|string|max:255',
            'type'  => 'nullable|string',
            'files' => 'required|array',
            'files.*' => 'file|max:5120', // 5MB each file

            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50|distinct',
        ]);

        if ($validated->fails()) {
            return $this->error($validated->errors()->first(), 422);
        }


        try {
            DB::beginTransaction();


            $document = InternalDocument::create([
                'name'       => $request->name,
                'type'       => $request->type,
                'company_id' => $company_id,
            ]);

            // Upload multiple files
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

            // Store tags
            if ($request->filled('tags')) {
                foreach ($request->tags as $tagName) {
                    InternalDocumentTags::create([
                        'internal_document_id' => $document->id,
                        'tag' => $tagName
                    ]);
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
