<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\CompanyNote;
use App\Models\Company;

class CompanyNoteController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST NOTES
    // ======================
    public function index($company_id)
    {
        if (!Company::where('id', $company_id)->exists()) {
            return $this->error([], 'Company not found', 404);
        }

        $notes = CompanyNote::where('company_id', $company_id)
            ->latest()
            ->get();

        $data = $notes->map(function ($note) {
            return [
                'id' => $note->id,
                'title' => $note->title,
                'note' => $note->note,
                'created_at' => $note->created_at->format('d M Y'),
            ];
        });

        return $this->success($data, 'Notes fetched');
    }

    // ======================
    // STORE NOTE
    // ======================
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'title' => 'required|string|max:255',
            'note' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $data = $validator->validated();

        $note = CompanyNote::create($data);

        return $this->success([
            'id' => $note->id,
            'title' => $note->title,
            'note' => $note->note,
        ], 'Note added', 201);
    }

    // ======================
    // DELETE NOTE
    // ======================
    public function destroy(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id'
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $note = CompanyNote::where('id', $id)
            ->where('company_id', $request->company_id)
            ->first();

        if (!$note) {
            return $this->error([], 'Note not found', 404);
        }

        $note->delete();

        return $this->success([], 'Deleted');
    }
}
