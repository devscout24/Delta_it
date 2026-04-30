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
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|exists:companies,id',
            'search'     => 'nullable|string|max:255',
            'sort_by'    => 'nullable|string|in:first_name,last_name,job_position,email',
            'sort_order' => 'nullable|string|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 'Validation error', 422);
        }

        $query = Collaborator::where('company_id', $request->company_id);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                    ->orWhere('last_name', 'like', '%' . $request->search . '%');
            });
        }

        // Optional sorting
        if ($request->filled('sort_by')) {
            $query->orderBy($request->sort_by, $request->get('sort_order', 'asc'));
        } else {
            $query->latest();
        }

        $collaborators = $query->paginate(10);

        $data = $collaborators->getCollection()->map(function ($c) {
            return [
                'id' => $c->id,
                'first_name' => $c->first_name,
                'last_name' => $c->last_name,
                'full_name' => $c->first_name . ' ' . $c->last_name,
                'job_position' => $c->job_position,
                'email' => $c->email,
                'phone_number' => $c->phone_number,
                'access_card_number' => $c->access_card_number,
                'parking_card' => (bool)$c->parking_card,
            ];
        });

        return $this->success([
            'data' => $data,
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
    public function show(Request $request, $id)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id'
        ]);

        $collaborator = Collaborator::where('id', $id)
            ->where('company_id', $request->company_id)
            ->first();

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
            'email' => 'nullable|email|unique:collaborators,email,NULL,id,company_id,' . $request->company_id,
            'phone_extension' => 'nullable|string|max:20',
            'phone_number' => 'nullable|string|max:20',
            'access_card_number' => 'nullable|string|max:50',
            'parking_card' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors(), 'Validation error', 422);
        }

        $collaborator = Collaborator::create($request->all());

        return $this->success($collaborator, 'Collaborator created', 201);
    }

    // ======================
    // UPDATE
    // ======================
    public function update(Request $request, $id)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id'
        ]);

        $collaborator = Collaborator::where('id', $id)
            ->where('company_id', $request->company_id)
            ->first();

        if (!$collaborator) {
            return $this->error([], 'Collaborator not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'job_position' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:collaborators,email,' . $id . ',id,company_id,' . $request->company_id,
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
    public function destroy(Request $request, $id)
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id'
        ]);

        $collaborator = Collaborator::where('id', $id)
            ->where('company_id', $request->company_id)
            ->first();

        if (!$collaborator) {
            return $this->error([], 'Collaborator not found', 404);
        }

        $collaborator->delete();

        return $this->success([], 'Collaborator deleted');
    }
}
