<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Document;

class AdminDocumentController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST
    // ======================
    public function index(Request $request)
    {
        $query = Document::with(['tags', 'company']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('search')) {
            $query->where('document_name', 'like', '%' . $request->search . '%');
        }

        $documents = $query->latest()->paginate(10);

        $data = $documents->getCollection()->map(function ($doc) {
            return [
                'id' => $doc->id,
                'name' => $doc->document_name,
                'file_url' => Storage::url($doc->document_path),

                'company' => $doc->company?->name,

                'tags' => $doc->tags->pluck('name'),

                'created_at' => $doc->created_at->format('d M Y'),
            ];
        });

        return $this->success([
            'data' => $data,
            'pagination' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'total' => $documents->total(),
            ]
        ], 'Documents fetched');
    }

    // ======================
    // STORE
    // ======================
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:4096',
            'company_id' => 'nullable|exists:companies,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        DB::beginTransaction();

        try {
            $path = $request->file('file')->store('documents', 'public');

            $doc = Document::create([
                'company_id' => $request->company_id,
                'document_name' => $request->file('file')->getClientOriginalName(),
                'document_path' => $path
            ]);

            if ($request->filled('tags')) {
                $doc->tags()->sync($request->tags);
            }

            DB::commit();

            return $this->success([
                'id' => $doc->id,
                'file_url' => Storage::url($path)
            ], 'Document created');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error([], $e->getMessage(), 500);
        }
    }

    // ======================
    // SHOW
    // ======================
    public function show($id)
    {
        $doc = Document::with('tags', 'company')->find($id);

        if (!$doc) {
            return $this->error([], 'Not found', 404);
        }

        return $this->success([
            'id' => $doc->id,
            'name' => $doc->document_name,
            'file_url' => Storage::url($doc->document_path),
            'company_id' => $doc->company_id,
            'tags' => $doc->tags->pluck('id'),
        ], 'Document details');
    }

    // ======================
    // UPDATE
    // ======================
    public function update(Request $request, $id)
    {
        $doc = Document::find($id);

        if (!$doc) {
            return $this->error([], 'Not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'file' => 'nullable|file|max:4096'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        // replace file (optional)
        if ($request->hasFile('file')) {

            if (Storage::disk('public')->exists($doc->document_path)) {
                Storage::disk('public')->delete($doc->document_path);
            }

            $path = $request->file('file')->store('documents', 'public');

            $doc->update([
                'document_name' => $request->file('file')->getClientOriginalName(),
                'document_path' => $path
            ]);
        }

        // update tags
        if ($request->filled('tags')) {
            $doc->tags()->sync($request->tags);
        }

        return $this->success([], 'Updated');
    }

    // ======================
    // DELETE
    // ======================
    public function destroy($id)
    {
        $doc = Document::find($id);

        if (!$doc) {
            return $this->error([], 'Not found', 404);
        }

        if (Storage::disk('public')->exists($doc->document_path)) {
            Storage::disk('public')->delete($doc->document_path);
        }

        $doc->tags()->detach();
        $doc->delete();

        return $this->success([], 'Deleted');
    }
}
