<?php

namespace App\Http\Controllers\Api;

use App\Models\Tag;
use App\Models\Document;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
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


            if ($request->document_type == 'company') {

                $company_id  = $request->company_id;
                if (!$company_id) {
                    return $this->error('Company ID is required for company documents', 400);
                }
            } else {
                $company_id  = null;
            }

            // Create document
            $document = Document::create([
                'document_name' => $request->document_name,
                'document_type' => $request->document_type,
                'document_path' => $filePath,
                'company_id' => $company_id,
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


            $document = [
                'id' => $document->id,
                'document_name' => $document->document_name,
                'document_type' => $document->document_type,
                'document_path' => asset($document->document_path),
                'company_id' => $document->company_id,
            ];


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

    public function getAllDocumentsList(Request $request)
    {
        $query = Document::with(['company', 'tags']);

        //  Filter by document type (internal / company)
        if ($request->has('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        //  Filter by company (if you want company based document)
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        //  Filter by tag name 
        if ($request->has('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('tag', 'LIKE', '%' . $request->tag . '%');
            });
        }

        $documents = $query->get()->map(function ($doc) {

            return [
                'id' => $doc->id,
                'document_name' => $doc->document_name,
                'document_type' => $doc->document_type,
                'company' => $doc->company ? $doc->company->commercial_name : 'techlabs',
                'tags' => $doc->tags->pluck('tag'),
                'document_url' => asset($doc->document_path),
            ];
        });

        if ($documents->isEmpty()) {
            return $this->error([], 'No documents available', 404);
        }

        return $this->success(
            $documents,
            'Documents retrieved successfully',
            200
        );
    }


    // mobile api 
    public function allDocuments()
    {
        $documents = Document::with(['company', 'tags'])->get();

        if ($documents->isEmpty()) {
            return $this->error(
                [],
                'No documents available.',
                404
            );
        }

        $formattedDocuments = $documents->map(function ($doc) {
            return [
                'id' => $doc->id,
                'document_path' => asset($doc->document_path),
            ];
        });

        return $this->success(
            $formattedDocuments,
            'All documents retrieved successfully.',
            200
        );
    }
}
