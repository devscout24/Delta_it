<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Document;
use App\Models\Tag;

class DocumentController extends Controller
{
    use ApiResponse;

    // ======================
    // GET TAGS
    // ======================
    public function tags()
    {
        $tags = Tag::select('id', 'name')->get();

        if ($tags->isEmpty()) {
            return $this->success([], 'No tags found', 200);
        }

        return $this->success($tags, 'Tags fetched');
    }

    // ======================
    // LIST DOCUMENTS
    // ======================
    public function index($company_id)
    {
        $documents = Document::with('tags')
            ->where('company_id', $company_id)
            ->latest()
            ->get();

        $data = $documents->map(function ($doc) {
            return [
                'id' => $doc->id,
                'file_url' => Storage::url($doc->file_path),
                'tags' => $doc->tags->map(fn($t) => $t->name),
            ];
        });

        return $this->success($data, 'Documents fetched');
    }

    // ======================
    // STORE DOCUMENT
    // ======================
    public function store(Request $request, $company_id)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf|max:10240',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        // upload file
        $path = $request->file('file')->store('documents', 'public');

        $document = Document::create([
            'company_id' => $company_id,
            'file_path' => $path,
        ]);

        // attach tags
        if ($request->filled('tags')) {
            $document->tags()->sync($request->tags);
        }

        return $this->success([
            'id' => $document->id,
            'file_url' => Storage::url($path)
        ], 'Document uploaded', 201);
    }

    // ======================
    // DELETE DOCUMENT
    // ======================
    public function destroy($id)
    {
        $document = Document::find($id);

        if (!$document) {
            return $this->error([], 'Document not found', 404);
        }

        // delete file
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        // delete pivot + document
        $document->tags()->detach();
        $document->delete();

        return $this->success([], 'Document deleted');
    }
}
