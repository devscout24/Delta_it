<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use App\Models\Document;
use App\Models\Tag;

class AdminDocumentController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST ALL DOCUMENTS
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

            $path = public_path($doc->document_path);
            $size = File::exists($path)
                ? number_format(File::size($path) / 1048576, 2) . ' MB'
                : '0 MB';

            return [
                'id' => $doc->id,
                'name' => $doc->document_name,
                'file_url' => asset($doc->document_path),
                'size' => $size,

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
    // STORE DOCUMENT
    // ======================
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:4096',
            'company_id' => 'nullable|exists:companies,id',
            'tags' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        DB::beginTransaction();

        try {
            $file = $request->file('file');
            $name = time() . '_' . $file->getClientOriginalName();

            $file->move(public_path('uploads/documents'), $name);

            $doc = Document::create([
                'company_id' => $request->company_id, // nullable for internal
                'document_name' => $file->getClientOriginalName(),
                'document_path' => 'uploads/documents/' . $name
            ]);

            // TAGS (pivot system)
            if ($request->tags) {
                $tagIds = [];

                foreach ($request->tags as $tagName) {
                    $tag = Tag::firstOrCreate(['name' => $tagName]);
                    $tagIds[] = $tag->id;
                }

                $doc->tags()->sync($tagIds);
            }

            DB::commit();

            return $this->success([
                'id' => $doc->id,
                'file_url' => asset($doc->document_path)
            ], 'Document created');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error([], $e->getMessage(), 500);
        }
    }

    // ======================
    // SHOW (OPTIONAL)
    // ======================
    public function show($id)
    {
        $doc = Document::with('tags', 'company')->find($id);

        if (!$doc) {
            return $this->error([], 'Not found', 404);
        }

        return $this->success($doc, 'Document details');
    }

    // ======================
    // UPDATE (OPTIONAL)
    // ======================
    public function update(Request $request, $id)
    {
        $doc = Document::find($id);

        if (!$doc) {
            return $this->error([], 'Not found', 404);
        }

        if ($request->tags) {
            $tagIds = [];

            foreach ($request->tags as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }

            $doc->tags()->sync($tagIds);
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

        $path = public_path($doc->document_path);

        if (file_exists($path)) {
            unlink($path);
        }

        $doc->delete();

        return $this->success([], 'Deleted');
    }
}
