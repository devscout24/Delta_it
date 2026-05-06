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

        // filter: internal | company
        if ($request->filled('visibility')) {
            $query->where('visibility', $request->visibility);
        }

        // filter: company documents by company
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        // filter: by tag IDs (documents must have ALL given tags)
        if ($request->filled('tags')) {
            $tagIds = (array) $request->tags;
            foreach ($tagIds as $tagId) {
                $query->whereHas('tags', fn($q) => $q->where('tags.id', $tagId));
            }
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $perPage   = min((int) $request->input('per_page', 10), 100);
        $documents = $query->latest()->paginate($perPage);

        $data = $documents->getCollection()->map(function ($doc) {
            return [
                'id'         => $doc->id,
                'name'       => $doc->name,
                'file_url'   => Storage::url($doc->file_path),
                'visibility' => $doc->visibility,
                'company'    => $doc->company ? ['id' => $doc->company->id, 'name' => $doc->company->name] : null,
                'tags'       => $doc->tags->map(fn($t) => ['id' => $t->id, 'name' => $t->name]),
                'created_at' => $doc->created_at->format('d M Y'),
            ];
        });

        return $this->success([
            'data'       => $data,
            'pagination' => [
                'total'        => $documents->total(),
                'per_page'     => $documents->perPage(),
                'current_page' => $documents->currentPage(),
                'last_page'    => $documents->lastPage(),
                'from'         => $documents->firstItem(),
                'to'           => $documents->lastItem(),
            ],
        ], 'Documents fetched');
    }

    // ======================
    // STORE
    // ======================
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file'       => 'required|file|max:4096',
            'visibility' => 'required|in:internal,company',
            'company_id' => 'required_if:visibility,company|nullable|exists:companies,id',
            'tags'       => 'nullable|array',
            'tags.*'     => 'exists:tags,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        DB::beginTransaction();

        try {
            $path = $request->file('file')->store('documents', 'public');

            $doc = Document::create([
                'company_id'  => $request->visibility === 'internal' ? null : $request->company_id,
                'uploaded_by' => auth()->id(),
                'name'        => $request->file('file')->getClientOriginalName(),
                'file_path'   => $path,
                'type'        => $request->file('file')->getClientOriginalExtension(),
                'visibility'  => $request->visibility,
            ]);

            if ($request->filled('tags')) {
                $doc->tags()->sync($request->tags);
            }

            DB::commit();

            return $this->success([
                'id'       => $doc->id,
                'file_url' => Storage::url($path),
            ], 'Document created', 201);
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
            'id'         => $doc->id,
            'name'       => $doc->name,
            'file_url'   => Storage::url($doc->file_path),
            'visibility' => $doc->visibility,
            'company_id' => $doc->company_id,
            'tags'       => $doc->tags->map(fn($t) => ['id' => $t->id, 'name' => $t->name]),
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
            'file'       => 'nullable|file|max:4096',
            'visibility' => 'nullable|in:internal,company',
            'company_id' => 'nullable|exists:companies,id',
            'tags'       => 'nullable|array',
            'tags.*'     => 'exists:tags,id',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        if ($request->hasFile('file')) {
            if (Storage::disk('public')->exists($doc->file_path)) {
                Storage::disk('public')->delete($doc->file_path);
            }

            $path = $request->file('file')->store('documents', 'public');

            $doc->update([
                'name'      => $request->file('file')->getClientOriginalName(),
                'file_path' => $path,
                'type'      => $request->file('file')->getClientOriginalExtension(),
            ]);
        }

        if ($request->filled('visibility')) {
            $visibility  = $request->visibility;
            $company_id  = $visibility === 'internal' ? null : $request->company_id ?? $doc->company_id;
            $doc->update(['visibility' => $visibility, 'company_id' => $company_id]);
        }

        if ($request->filled('tags')) {
            $doc->tags()->sync($request->tags);
        }

        return $this->success([], 'Document updated');
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

        if (Storage::disk('public')->exists($doc->file_path)) {
            Storage::disk('public')->delete($doc->file_path);
        }

        $doc->tags()->detach();
        $doc->delete();

        return $this->success([], 'Document deleted');
    }
}
