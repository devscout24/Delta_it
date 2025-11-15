<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TicketMessage;
use App\Models\TicketMessageFile;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TicketAttachmentController extends Controller
{
    use ApiResponse;

    public function store(Request $request, $message_id)
    {
        $request->validate([
            'file' => 'required|file|max:20480' // 20MB
        ]);

        $message = TicketMessage::findOrFail($message_id);

        // store on public disk
        $path = $request->file('file')->store('ticket_files', 'public');

        $file = TicketMessageFile::create([
            'ticket_message_id' => $message->id,
            'file_path' => $path,                    // note: path relative to storage/app/public
            'file_type' => $request->file('file')->getClientMimeType(),
            'file_size' => $request->file('file')->getSize(),
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
