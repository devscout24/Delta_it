<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Models\Document;

class DocumentController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST DOCUMENTS
    // ======================
    public function index()
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $documents = Document::where('company_id', $user->company_id)
            ->latest()
            ->get()
            ->map(function ($doc) {

                $path = public_path($doc->file_path);

                $size = File::exists($path)
                    ? number_format(File::size($path) / 1048576, 2)
                    : '0.00';

                return [
                    'id' => $doc->id,
                    'name' => $doc->name,
                    'type' => $doc->type,
                    'size_mb' => $size,
                    'file_url' => asset($doc->file_path),
                ];
            });

        return $this->success([
            'documents' => $documents,
            'is_empty' => $documents->isEmpty(),
        ], 'Documents fetched');
    }

    // ======================
    // SINGLE DOCUMENT
    // ======================
    public function show($id)
    {
        $user = Auth::guard('api')->user();

        if (!$user || !$user->company_id) {
            return $this->error([], 'Unauthorized', 401);
        }

        $doc = Document::where('company_id', $user->company_id)
            ->find($id);

        if (!$doc) {
            return $this->error([], 'Document not found', 404);
        }

        $path = public_path($doc->file_path);

        $size = File::exists($path)
            ? number_format(File::size($path) / 1048576, 2)
            : '0.00';

        return $this->success([
            'id' => $doc->id,
            'name' => $doc->name,
            'type' => $doc->type,
            'size_mb' => $size,
            'file_url' => asset($doc->file_path),
        ], 'Document fetched');
    }
}
