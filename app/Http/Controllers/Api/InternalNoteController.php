<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\InternalNote;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Validator;

class InternalNoteController extends Controller
{
    use ApiResponse;
    public function index()
    {
        try {
            $notes = InternalNote::all();

            $notes = $notes->map(function ($note) {
                return [
                    'id'    => $note->id,
                    'title' => $note->title,
                    'note'  => $note->note,
                ];
            });

            return $this->success($notes, 'Internal Notes Retrieved Successfully', 200);
        } catch (Exception $e) {
            return $this->error('Server Error', $e->getMessage(), 500);
        }
    }


    public function store(Request $request)
    {
        $data = $request->all();

        // Normalize data: wrap single note into an array
        if (!isset($data[0])) {
            $data = [$data]; // now we always have an array of notes
        }

        $createdNotes = [];

        try {
            foreach ($data as $item) {
                // Validate each note
                $validator = Validator::make($item, [
                    'title' => 'required|string|max:255',
                    'note'  => 'required|string',
                ]);

                if ($validator->fails()) {
                    return $this->error('Validation Error', $validator->errors(), 422);
                }

                // Create the note
                $createdNotes[] = InternalNote::create($validator->validated());
            }

            return $this->success($createdNotes, 'Internal Notes Added Successfully', 200);
        } catch (Exception $e) {
            return $this->error('Server Error', $e->getMessage(), 500);
        }
    }


    public function update(Request $request, $id)
    {
        // Find the note
        $note = InternalNote::find($id);

        if (!$note) {
            return $this->error('Not Found', 'Internal Note not found', 404);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'note'  => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation Error', $validator->errors(), 422);
        }

        try {
            // Update the note
            $note->update($validator->validated());

            return $this->success($note, 'Internal Note Updated Successfully', 200);
        } catch (Exception $e) {
            return $this->error('Server Error', $e->getMessage(), 500);
        }
    }

    public function destroy($id) {

        $note  = InternalNote::find($id);

        if(!$note){
            return $this->error('', 'Internal note not fond',404);
        }
        $note->delete();
        return $this->success('','Note deleted Successfull' , 200);
    }
}
