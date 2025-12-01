<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketMessage;
use App\Models\TicketMessageFile;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketAttachmentController extends Controller
{
    use ApiResponse;

    public function store(Request $request, $message_id)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:20480',
        ]);

        if ($validator->fails()) {
            return $this->error(null, $validator->errors()->first(), 422);
        }

        $message = TicketMessage::findOrFail($message_id);

        $uploadedFile = $request->file('file');

        // ⚠️ READ SIZE + TYPE BEFORE MOVING
        $fileSize = $uploadedFile->getSize();
        $fileType = $uploadedFile->getClientMimeType();

        // Move file using your global upload function
        $uploadedPath = $this->uploadFile(
            $uploadedFile,
            'ticket_files'
        );

        if (!$uploadedPath) {
            return $this->error(null, "File upload failed", 500);
        }

        $file = TicketMessageFile::create([
            'ticket_message_id' => $message->id,
            'file_path' => $uploadedPath,
            'file_type' => $fileType,
            'file_size' => $fileSize,
        ]);

        return $this->success($file, 'File uploaded', 201);
    }





    public function destroyFile(Request $request)
    {
        $request->validate(['file_id' => 'required|exists:ticket_message_files,id']);

        $file = TicketMessageFile::findOrFail($request->file_id);

        // delete physical file (if exists)
        \Illuminate\Support\Facades\Storage::disk('public')->delete($file->file_path);

        $file->delete();

        return $this->success(null, 'File deleted', 200);
    }
}
