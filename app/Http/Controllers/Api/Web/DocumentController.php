<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Document;
use App\Models\Tag;

class DocumentController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST DOCUMENTS
    // ======================
    public function index($company_id)
    {
        $documents = Document::with('tags')
            ->where('company_id', $company_id)
            ->latest()
            ->get()
            ->map(function ($doc) {

                $path = public_path($doc->document_path);
                $size = File::exists($path)
                    ? number_format(File::size($path) / 1048576, 2) . ' MB'
                    : '0 MB';

                return [
                    'id' => $doc->id,
                    'name' => $doc->document_name,
                    'file_url' => asset($doc->document_path),
                    'size' => $size,
                    'tags' => $doc->tags->pluck('tag')
                ];
            });

        return $this->success($documents, 'Documents fetched');
    }

    // ======================
    // STORE DOCUMENT
    // ======================
    public function store(Request $request, $company_id)
    {
        $request->validate([
            'file' => 'required|file|max:4096',
            'tags' => 'nullable|array'
        ]);

        DB::beginTransaction();

        try {
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/documents'), $filename);

            $doc = Document::create([
                'company_id' => $company_id,
                'document_name' => $file->getClientOriginalName(),
                'document_path' => 'uploads/documents/' . $filename
            ]);

            if ($request->tags) {
                foreach ($request->tags as $tag) {
                    Tag::create([
                        'document_id' => $doc->id,
                        'tag' => $tag
                    ]);
                }
            }

            DB::commit();

            return $this->success([], 'Document uploaded');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error([], $e->getMessage(), 500);
        }
    }

    // ======================
    // DELETE DOCUMENT
    // ======================
    public function destroy($id)
    {
        $doc = Document::find($id);

        if (!$doc) {
            return $this->error([], 'Document not found', 404);
        }

        $path = public_path($doc->document_path);

        if (file_exists($path)) {
            unlink($path);
        }

        $doc->delete();

        return $this->success([], 'Document deleted');
    }
}
