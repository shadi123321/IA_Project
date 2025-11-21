<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\ComplaintStatusHistory;
use App\Models\User;
use GuzzleHttp\Promise\Create;
class UserController extends Controller
{
   
   public function SubmitComplain(Request $request)
{
    //user id =auth->user(); instead of  'user_id' => $request->user_id,
   // add attachment

    $request->validate([
        'type' => 'required|string',
        'government_entity_id' => 'required|exists:government_entities,entity_id',
        'location' => 'nullable|string',
        'description' => 'nullable|string',
        'attachments.*' => 'file|max:4096'
    ]);

    $reference = "CMP-" . uniqid();

    $complaint = Complaint::create([
        'reference_number' => $reference,
       'user_id' => $request->user_id,
        'government_entity_id' => $request->government_entity_id,
        'type' => $request->type,
        'location' => $request->location,
        'description' => $request->description,
    ]);

    if ($request->hasFile('attachments')) {
        foreach ($request->attachments as $file) {
            $path = $file->store('attachments');

            ComplaintAttachment::create([
                'complaint_id' => $complaint->complaint_id,
                'file_path' => $path,
            ]);
        }
    }

    return response()->json([
        'message' => 'Complaint submitted successfully',
        'reference_number' => $reference
    ]);
}
public function myComplaints()
{
    $complaints = Complaint::where('user_id', auth()->id())->get();

    return response()->json($complaints);
}
public function show($reference)
{
    $complaint = Complaint::where('reference_number', $reference)
        ->with(['attachments', 'histories.handler'])
        ->firstOrFail();

    return response()->json($complaint);
}
public function addAttachment(Request $request, $reference)
{
    $request->validate(['file' => 'required|file|max:4096']);

    $complaint = Complaint::where('reference_number', $reference)->firstOrFail();

    $this->authorize('update', $complaint);

    $path = $request->file->store('attachments');

    ComplaintAttachment::create([
        'complaint_id' => $complaint->complaint_id,
        'file_path' => $path
    ]);

    return response()->json(['message' => 'Attachment added']);
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
