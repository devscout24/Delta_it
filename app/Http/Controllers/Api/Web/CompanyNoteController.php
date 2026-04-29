<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\CompanyNote;

class CompanyNoteController extends Controller
{
    use ApiResponse;

    public function index($company_id)
    {
        $notes = CompanyNote::where('company_id', $company_id)
            ->latest()
            ->get();

        return $this->success($notes, 'Notes fetched');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'note' => 'required|string'
        ]);

        $note = CompanyNote::create($data);

        return $this->success($note, 'Note added');
    }

    public function destroy($id)
    {
        $note = CompanyNote::find($id);

        if (!$note) {
            return $this->error([], 'Not found', 404);
        }

        $note->delete();

        return $this->success([], 'Deleted');
    }
}
