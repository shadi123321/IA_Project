<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\ComplaintStatusHistory;
use App\Models\User;
use GuzzleHttp\Promise\Create;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{

   public function SubmitComplaint(Request $request)
{
    $request->validate([
        'type' => 'required|string',
        'government_entity_id' => 'required',/* |exists:government_entities,entity_id',*/
        'location' => 'nullable|string',
        'description' => 'nullable|string',
        'attachments.*' => 'file|max:4096' // تحديد لواحق 
    ]);

    $reference = "CMP-" . uniqid();

    $complaint = Complaint::create([
        'reference_number' => $reference,
        'user_id' => 3, //
        'government_entity_id' => $request->government_entity_id,
        'type' => $request->type,
        'location' => $request->location,
        'description' => $request->description,
    ]);

    if ($request->hasFile('attachments')) {
        foreach ($request->file('attachments') as $file) {
            $path = $file->store('attachments', 'public');

            $mime = $file->getClientMimeType();
            $type = str_contains($mime, 'image') ? 'image' : 'document';

            $complaint->attachments()->create([
                'file_path' => $path,
                'type' => $type,
            ]);
        }
    }

    return response()->json(['message' => 'Complaint submitted successfully']);
}


public function myComplaints()
{
    /*$userId = auth('api')->id();
    if (!$userId) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }*/

    $complaints = Complaint::with('governmentEntity')
        ->where('user_id', 3)
        ->latest('complaint_id')
        ->get();

    return $complaints;
/*
    $complaints->each(function ($complaint) {
        $complaint->attachments->transform(function ($attachment) {
            $attachment->file_path = Storage::url($attachment->file_path);
            return $attachment;
        });
    });
*/
    return response()->json($complaints);
}

public function myComplaintsAtt()
{
    /*$userId = auth('api')->id();
    if (!$userId) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }*/

    $attachments = ComplaintAttachment::whereHas('complaint', function ($q) {
        $q->where('user_id', 3);
    })->get();

    foreach($attachments as $attachment)
    {
        $attachment->file_path = Storage::url($attachment->file_path);
    }

    return response()->json([
        'attachments' => $attachments
    ]);
}


public function show($reference)
{
    $complaint = Complaint::where('reference_number', $reference)
        ->with(['histories.handler'])
        ->firstOrFail();


    $complaint->attachments->transform(function ($attachment) {
        $attachment->file_path = Storage::url($attachment->file_path);
        return $attachment;
    });

    return response()->json($complaint);
}

public function showAtt($reference)
{
    $attachments = ComplaintAttachment::whereHas('complaint', function ($q) use ($reference) {
        $q->where('reference_number', $reference);
    })->get();

    foreach ($attachments as $attachment) {
        $attachment->file_path = Storage::url($attachment->file_path);
    }

    return response()->json([
        'attachments' => $attachments
    ]);
}


public function addAttachment(Request $request, $reference)
{
    $request->validate([
        'file' => 'required|file|max:4096'
    ]);

    $complaint = Complaint::where('reference_number', $reference)->firstOrFail();

    // Store the file on the public disk
    $path = $request->file('file')->store('attachments', 'public');

    // Use the relationship to create the attachment

    $mime = $request->file('file')->getClientMimeType();
    $type = str_contains($mime, 'image') ? 'image' : 'document';

    $attachment = $complaint->attachments()->create([
        'file_path' => $path,
        'type' => $type,
    ]);

    // Replace file_path with a public URL for frontend use
    $attachment->file_path = Storage::url($attachment->file_path);

    return response()->json([
        'message' => 'Attachment added successfully',
        'attachment' => $attachment
    ]);
}


public function employeeComplaints()
{
    $user = auth()->user();

    $complaints = Complaint::where('government_entity_id', $user->government_entity_id)
        ->with('user')
        ->get();

    return response()->json($complaints);
}
public function updateStatus(Request $request, $reference)
{
    $request->validate([
        'status' => 'required|in:new,processing,resolved,rejected',
        'note' => 'nullable|string',
    ]);

    $complaint = Complaint::where('reference_number', $reference)->firstOrFail();

    $this->authorize('employeeEdit', $complaint);

    // تحديث الحالة
    $complaint->update(['status' => $request->status]);

    // إضافة سجل تاريخ
    ComplaintStatusHistory::create([
        'complaint_id' => $complaint->complaint_id,
        'handled_by' => auth()->id(),
        'status' => $request->status,
        'note' => $request->note,
    ]);

    // إرسال إشعار للمواطن (Placeholder)
    // Notification::send($complaint->user, new ComplaintStatusChanged($complaint));

    return response()->json(['message' => 'Complaint updated']);
}
}
