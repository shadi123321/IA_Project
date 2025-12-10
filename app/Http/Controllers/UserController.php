<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Complaint;
use App\Notifications\ComplaintStatusUpdated;
use App\Models\ComplaintAttachment;
use App\Models\ComplaintStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\ComplaintService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ChangeComplaintStatusRequest;

class UserController extends Controller
{
    private $statusService;

    public function __construct(ComplaintService $statusService)
    {
        $this->statusService = $statusService;
    }

    /*------------------------------------
        🔹 Save FCM Token
    ------------------------------------*/
    public function saveFcmToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $user = Auth::user();

        $user->fcm_token = $request->token;
        $user->save();

        Log::info('New FCM Token Saved', [
            'user_id' => $user->id,
            'email'   => $user->email,
            'token'   => $request->token
        ]);

        return response()->json([
            'status' => true,
            'message' => 'FCM token saved successfully'
        ]);
    }

    /*------------------------------------
        🔹 Submit Complaint (Old version)
    ------------------------------------*/
    public function SubmitComplain(Request $request)
    {
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
    }

    /*------------------------------------
        🔹 All Complaints
    ------------------------------------*/
    public function complaints()
    {
        $complaints = Complaint::paginate(20);

        return response()->json([
            'status' => true,
            'complaints' => $complaints
        ]);
    }

    /*------------------------------------
        🔹 Show Complaint (with full history)
    ------------------------------------*/
    public function showComplaint($reference_number)
    {
        $complain = Complaint::where('reference_number', $reference_number)
            ->with(['histories' => function($query) {
                $query->orderBy('changed_at', 'asc');
            }])
            ->firstOrFail();

        $histories = $complain->histories->map(function($history) {
            $employee = User::find($history->handled_by);

            return [
                'status'     => $history->status,
                'note'       => $history->note,
                'changed_at' => $history->changed_at,
                'handled_by' => [
                    'id'   => $history->handled_by,
                    'employee' => $employee->name ?? 'Unknown',
                ],
            ];
        });

        return response()->json([
            'status'   => true,
            'complain' => [
                'reference_number' => $complain->reference_number,
                'status'           => $complain->status,
                'description'      => $complain->description,
                'location'         => $complain->location,
                'histories'        => $histories,
            ]
        ]);
    }

    /*------------------------------------
        🔹 Complaints by Entity
    ------------------------------------*/
    public function indexByEntity(Request $request)
    {
        $user = User::find($request->user);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        $complaints = Complaint::where('government_entity_id', $user->government_entity_id)->get();

        return response()->json([
            'status' => 'success',
            'sector' => $user->government_entity_id,
            'data' => $complaints
        ]);
    }

    /*------------------------------------
        🔹 Change Complaint Status (Service Layer)
    ------------------------------------*/
    public function changeStatus(ChangeComplaintStatusRequest $request)
    {
        try {

            $complain = $this->statusService->changeStatus($request->validated());

            return response()->json([
                'status'   => true,
                'message'  => "Status changed successfully",
                'complain' => $complain
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /*------------------------------------
        🔹 Employee Complaints
    ------------------------------------*/
    public function employeeComplaints()
    {
        $user = auth()->user();

        $complaints = Complaint::where('government_entity_id', $user->government_entity_id)
            ->with('user')
            ->get();

        return response()->json($complaints);
    }

    /*------------------------------------
        🔹 Update Complaint Status
    ------------------------------------*/
    public function updateStatus(Request $request, $reference)
    {
        $request->validate([
            'status' => 'required|in:new,processing,resolved,rejected',
            'note' => 'nullable|string',
        ]);

        $complaint = Complaint::where('reference_number', $reference)->firstOrFail();

        $this->authorize('employeeEdit', $complaint);

        $complaint->update(['status' => $request->status]);

        ComplaintStatusHistory::create([
            'complaint_id' => $complaint->complaint_id,
            'handled_by' => auth()->id(),
            'status' => $request->status,
            'note' => $request->note,
        ]);

        return response()->json(['message' => 'Complaint updated']);
    }

    /*------------------------------------
        🔹 Get User Role
    ------------------------------------*/
    public function getRole()
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        return response()->json([
            'roles' => $user->getRoleNames()
        ]);
    }

    /*------------------------------------
        🔹 Submit Complaint (NEW - JWT Version)
    ------------------------------------*/
    public function SubmitComplaint(Request $request)
    {
        $user = auth('api')->user();

        $request->validate([
            'type' => 'required|string',
            'government_entity_id' => 'required|exists:government_entities,entity_id',
            'location' => 'nullable|string',
            'description' => 'nullable|string',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,mp4,avi,mov|max:10240'
        ]);

        $reference = "CMP-" . uniqid();

        $complaint = Complaint::create([
            'reference_number' => $reference,
            'user_id' => $user->id,
            'government_entity_id' => $request->government_entity_id,
            'type' => $request->type,
            'location' => $request->location,
            'description' => $request->description,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('attachments', 'public');

                $mime = $file->getClientMimeType();

                $type = str_contains($mime, 'image') ? 'image'
                       : (str_contains($mime, 'video') ? 'video' : 'document');

                $complaint->attachments()->create([
                    'file_path' => $path,
                    'type'      => $type,
                ]);
            }
        }

        return response()->json(['message' => 'Complaint submitted successfully']);
    }

    /*------------------------------------
        🔹 My Complaints
    ------------------------------------*/
    public function myComplaints()
    {
        $complaints = Complaint::where('user_id', auth('api')->id())->get();

        return response()->json($complaints);
    }

    /*------------------------------------
        🔹 My Complaint Attachments
    ------------------------------------*/
    public function myComplaintsAtt($id)
    {
        $userId = auth('api')->id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $attachments = ComplaintAttachment::where('complaint_id', $id)->get();

        foreach($attachments as $attachment) {
            $attachment->file_path = Storage::url($attachment->file_path);
        }

        return response()->json([
            'attachments' => $attachments
        ]);
    }

    /*------------------------------------
        🔹 Show Complaint by ID
    ------------------------------------*/
    public function show($id)
    {
        $complaint = Complaint::where('complaint_id', $id)
            ->with(['histories' => function($query) {
                $query->orderBy('changed_at', 'asc');
            }])
            ->firstOrFail();

        return response()->json($complaint);
    }

    /*------------------------------------
        🔹 Show Attachments for Complaint
    ------------------------------------*/
    public function showAtt($id)
    {
        $attachments = ComplaintAttachment::where('complaint_id', $id)->get();

        foreach ($attachments as $attachment) {
            $attachment->file_path = Storage::url($attachment->file_path);
        }

        return response()->json([
            'attachments' => $attachments
        ]);
    }

    /*------------------------------------
        🔹 Add Attachment
    ------------------------------------*/
    public function addAttachment(Request $request, $id)
    {
        $request->validate([
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,mp4,avi,mov|max:10240'
        ]);

        $complaint = Complaint::where('complaint_id', $id)->firstOrFail();

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {

                $path = $file->store('attachments', 'public');
                $mime = $file->getClientMimeType();

                $type = str_contains($mime, 'image') ? 'image'
                        : (str_contains($mime, 'video') ? 'video' : 'document');

                $complaint->attachments()->create([
                    'file_path' => $path,
                    'type'      => $type,
                ]);
            }
        }

        return response()->json([
            'message' => 'Attachments added successfully'
        ]);
    }
}
