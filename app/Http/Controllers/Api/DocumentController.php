<?php

namespace App\Http\Controllers\Api;

use App\Models\Tag;
use App\Models\Document;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
    use ApiResponse;

    public function mobileIndex()
    {
        $user = Auth::guard('api')->user();

        if (! $user || ! $user->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $documents = Document::query()
            ->where('company_id', $user->company_id)
            ->orderByDesc('id')
            ->get()
            ->map(function ($document) {
                $relativePath = ltrim((string) $document->document_path, '/');
                $absolutePath = public_path($relativePath);

                $fileSizeBytes = File::exists($absolutePath) ? (int) File::size($absolutePath) : 0;
                $fileSizeMb = $fileSizeBytes > 0 ? number_format($fileSizeBytes / 1048576, 2) : '0.00';

                return [
                    'id' => $document->id,
                    'name' => $document->document_name ?: basename($relativePath),
                    'type' => $document->document_type ?: pathinfo($relativePath, PATHINFO_EXTENSION),
                    'size_mb' => $fileSizeMb,
                    'size_label' => $fileSizeMb . ' mb',
                    'file_url' => $relativePath ? asset($relativePath) : null,
                    'download_url' => $relativePath ? asset($relativePath) : null,
                ];
            })
            ->values();

        return $this->success([
            'documents' => $documents,
            'is_empty' => $documents->isEmpty(),
            'empty_message' => 'No documents have been added to this area yet',
        ], 'Mobile documents fetched successfully', 200);
    }

    public function mobileShow($id)
    {
        $user = Auth::guard('api')->user();

        if (! $user || ! $user->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $document = Document::query()
            ->where('company_id', $user->company_id)
            ->find($id);

        if (! $document) {
            return $this->error([], 'Document not found', 404);
        }

        $relativePath = ltrim((string) $document->document_path, '/');
        $absolutePath = public_path($relativePath);

        $fileSizeBytes = File::exists($absolutePath) ? (int) File::size($absolutePath) : 0;
        $fileSizeMb = $fileSizeBytes > 0 ? number_format($fileSizeBytes / 1048576, 2) : '0.00';

        return $this->success([
            'id' => $document->id,
            'name' => $document->document_name ?: basename($relativePath),
            'type' => $document->document_type ?: pathinfo($relativePath, PATHINFO_EXTENSION),
            'size_mb' => $fileSizeMb,
            'size_label' => $fileSizeMb . ' mb',
            'file_url' => $relativePath ? asset($relativePath) : null,
            'download_url' => $relativePath ? asset($relativePath) : null,
        ], 'Mobile document fetched successfully', 200);
    }

    public function allDocuments($id)
    {
        // Validate company exists
        $company = \App\Models\Company::find($id);
        if (!$company) {
            return $this->error([], 'Company not found.', 404);
        }

        $documents = Document::with('company', 'tags')->where('company_id', $id)->get();

        if ($documents->isEmpty()) {
            return $this->error([], 'No documents available.', 404);
        }

        $documents = $documents->map(function ($document) {

            $absolutePath = public_path($document->document_path);
            $fileSizeMB = File::exists($absolutePath)
                ? number_format(File::size($absolutePath) / 1048576, 2) . ' MB'
                : 'N/A';

            return [
                'id' => $document->id,
                'company' => [
                    'id' => $document->company->id,
                    'name' => $document->company->name,
                    'email' => $document->company->email,
                ],
                'document_name' => $document->document_name,
                'document_type' => $document->document_type,
                'document_path' => asset($document->document_path),
                'file_size_mb'  => $fileSizeMB,
                'tags'          => $document->tags->map(function ($tag) {
                    return [
                        'name' => $tag->tag
                    ];
                }),
            ];
        });

        return $this->success($documents, 'Documents fetched successfully.', 200);
    }


    public function store(Request $request, $company_id)
    {
        $validated = Validator::make($request->all(), [
            'document_name' => 'nullable|string|max:255',
            'document_type' => 'nullable|in:pdf,word,image,other',
            'file'          => 'required|file|max:4096',             // allow 4MB safe
            'tags'          => 'nullable|array',
            'tags.*'        => 'string|max:50|distinct',
        ]);

        if ($validated->fails()) {
            return $this->error($validated->errors(), 422);
        }

        try {
            DB::beginTransaction();

            // upload file manually
            $uploadedFile = $request->file('file');
            $filename = time() . '_' . $uploadedFile->getClientOriginalName();
            $uploadedFile->move(public_path('uploads/documents'), $filename);

            $document = Document::create([
                'company_id'    => $company_id,
                'document_name' => $request->document_name,
                'document_type' => $request->document_type,
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


            DB::commit();

            return $this->success([], 'Document uploaded successfully', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error($e->getMessage(), 500);
        }
    }


    public function deleteDocument(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'id' => 'required|exists:documents,id',
        ]);

        if ($validated->fails()) {
            return $this->error($validated->errors(), 422);
        }

        $document = Document::find($request->id);

        if (!$document) {
            return $this->error('Document not found', 404);
        }

        $filePath = public_path($document->document_path);
        if (File::exists($filePath)) {
            unlink($filePath);
        }

        $document->delete();

        return $this->success([], 'Document deleted successfully', 200);
    }
}
