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

    public function allDocuments($id)
    {
        $documents = Document::with('company')->where('company_id', $id)->get();

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
                'file_size_mb' => $fileSizeMB,
            ];
        });

        return $this->success($documents, 'Documents fetched successfully.', 200);
    }


    public function store(Request $request, $company_id)
    {
        $validated = Validator::make($request->all(), [
            'document_name' => 'required|string|max:255',
            'document_type' => 'required|in:pdf,word,image,other',
            'file' => 'required|file|max:4096', // allow 4MB safe
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50|distinct',
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
