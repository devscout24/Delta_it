<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Collaborator;

class CollaboratorController extends Controller
{
    use ApiResponse;

    // ======================
    // LIST (BY COMPANY)
    // ======================
    public function index(Request $request)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id'
        ]);

        $query = Collaborator::where('company_id', $request->company_id);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                    ->orWhere('last_name', 'like', '%' . $request->search . '%');
            });
        }

        $collaborators = $query->latest()->paginate(10);

        return $this->success([
            'data' => $collaborators->items(),
            'pagination' => [
                'current_page' => $collaborators->currentPage(),
                'last_page' => $collaborators->lastPage(),
                'total' => $collaborators->total(),
            ]
        ], 'Collaborators fetched');
    }

    // ======================
    // SHOW
    // ======================
    public function show($id)
    {
        $collaborator = Collaborator::find($id);

        if (!$collaborator) {
            return $this->error([], 'Collaborator not found', 404);
        }

        return $this->success($collaborator, 'Collaborator details');
    }

    // ======================
    // STORE
    // ======================
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'job_position' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:collaborators,email',
            'phone_extension' => 'nullable|string|max:20',
            'phone_number' => 'nullable|string|max:20',
            'access_card_number' => 'nullable|string|max:50',
            'parking_card' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $collaborator = Collaborator::create([
            'company_id' => $request->company_id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'job_position' => $request->job_position,
            'email' => $request->email,
            'phone_extension' => $request->phone_extension,
            'phone_number' => $request->phone_number,
            'access_card_number' => $request->access_card_number,
            'parking_card' => $request->parking_card ?? false,
        ]);

        return $this->success($collaborator, 'Collaborator created', 201);
    }

    // ======================
    // UPDATE
    // ======================
    public function update(Request $request, $id)
    {
        $collaborator = Collaborator::find($id);

        if (!$collaborator) {
            return $this->error([], 'Collaborator not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'job_position' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:collaborators,email,' . $id,
            'phone_extension' => 'nullable|string|max:20',
            'phone_number' => 'nullable|string|max:20',
            'access_card_number' => 'nullable|string|max:50',
            'parking_card' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $collaborator->update($request->only([
            'first_name',
            'last_name',
            'job_position',
            'email',
            'phone_extension',
            'phone_number',
            'access_card_number',
            'parking_card'
        ]));

        return $this->success($collaborator, 'Collaborator updated');
    }

    // ======================
    // DELETE
    // ======================
    public function destroy($id)
    {
        $collaborator = Collaborator::find($id);

        if (!$collaborator) {
            return $this->error([], 'Collaborator not found', 404);
        }

        $collaborator->delete();

        return $this->success([], 'Collaborator deleted');
    }
}
