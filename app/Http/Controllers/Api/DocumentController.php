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
    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'document_name' => 'required|string|max:255',
            'document_type' => 'required|string|max:100',
            'file' => 'required|file|max:2048', // 2MB max
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50|distinct',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        if ($validated->fails()) {
            return $this->error($validated->errors(), 422);
        }

        try {
            DB::beginTransaction();

            $file = $request->file('file');
            if (!$file) {
                return $this->error('No file uploaded', 400);
            }

            // Upload file (custom helper)
            $filePath = $this->uploadFile($file, 'documents', null);

            // Create document
            $document = Document::create([
                'company_id' => $request->company_id,
                'document_name' => $request->document_name,
                'document_type' => $request->document_type,
                'document_path' => $filePath,
            ]);

            // Handle tags
            if ($request->filled('tags') && is_array($request->tags)) {
                foreach ($request->tags as $tag) {
                    Tag::create([
                        'document_id' => $document->id,
                        'tag' => $tag,
                    ]);
                }
            }

            DB::commit();
            return $this->success($document, 'Document uploaded successfully', 200);
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
            return response()->json(['errors' => $validated->errors()], 422);
        }

        $document = Document::find($request->id);

        if (!$document) {
            return $this->error('Document not found', 404);
        }

        // Delete the file from storage
        if ($document->document_path && file_exists(public_path($document->document_path))) {
            unlink(public_path($document->document_path));
        }
        // Delete the document record from the database
        $document->delete();

        return $this->success('Document deleted successfully', 200);
    }


    public function allDocuments($id)
    {
        $documents = Document::where('company_id', $id)->get();

        if ($documents->isEmpty()) {
            return $this->error([], 'No documents available.', 404);
        }

        $documents = $documents->map(function ($document) {
            $filePath = public_path($document->document_path);
            $fileSizeMB = null;

            if (File::exists($filePath)) {
                $fileSizeMB = number_format(File::size($filePath) / 1048576, 2); // bytes → MB
            }

            return [
                'id'             => $document->id,
                'company' => [
                    'id'     => $document->company_id,
                    'name' => $document->company->name,
                    'email' => $document->company->email
                ],
                'document_name'  => $document->document_name,
                'document_type'  => $document->document_type,
                'document_path'  => asset($document->document_path),
                'file_size_mb'   => $fileSizeMB ? "{$fileSizeMB} MB" : 'N/A',
            ];
        });

        return $this->success($documents, 'Documents fetched successfully.', 200);
    }
}
