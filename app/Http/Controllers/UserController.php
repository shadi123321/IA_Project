<?php

namespace App\Http\Controllers;
use Carbon\Carbon;

 use App\Http\Requests\StoreComplaintNoteRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Complaint;
use App\Notifications\ComplaintStatusUpdated;
use App\Models\ComplaintAttachment;
use App\Models\ComplaintStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Promise\Create;
use App\Services\ComplaintService;
use App\Http\Resources\ComplaintResource;
use App\Http\Requests\ChangeComplaintStatusRequest;
use App\Models\GovernmentEntity;
use Illuminate\Support\Facades\Log;
use App\Notifications\ComplaintReceivedFcm;

class UserController extends Controller
{
     private $statusService;

    public function __construct(ComplaintService $statusService)
    {
        $this->statusService = $statusService;
    }


    public function saveFcmToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string'
        ]);

        $user = Auth::user();

        if ($user->fcm_token !== $request->token) {
            $user->fcm_token = $request->token;
            $user->save();
        }

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

    public function complaints()
    {
        $complaints = Complaint::with('attachments')->paginate(20);

        return response()->json([
            'status'     => true,
            'complaints' => ComplaintResource::collection($complaints),
            'meta'       => [
                'current_page' => $complaints->currentPage(),
                'from'         => $complaints->firstItem(),
                'to'           => $complaints->lastItem(),
                'last_page'    => $complaints->lastPage(),
                'per_page'     => $complaints->perPage(),
                'total'        => $complaints->total(),
            ],
        ]);
    }

    public function showComplaint($reference_number)
    {
        $complain = Complaint::where('reference_number', $reference_number)
            ->with(['histories' => function($query) {
                $query->orderBy('changed_at', 'asc');
            }, 'attachments', 'governmentEntity'])
            ->firstOrFail();

        $complaintData = (new ComplaintResource($complain))->toArray(request());

        $histories = $complain->histories->map(function($history) {
            $employee = User::find($history->handled_by);
            return [
                'status'     => $history->status,
                'note'       => $history->note,
                'changed_at' => $history->changed_at,
                'handled_by' => [
                    'id'       => $history->handled_by,
                    'employee' => $employee->name ?? 'Unknown',
                ],
            ];
        });

        return response()->json([
            'status'   => true,
            'complain' => array_merge($complaintData, [
                'histories' => $histories,
            ])
        ], 200);
    }
public function indexByEntity()
{
    $user = auth('api')->user();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'User not found'
        ], 404);
    }

    $complaints = Complaint::where('government_entity_id', $user->government_entity_id)
        ->orderByDesc('created_at')
        ->paginate(20);

    $ge = GovernmentEntity::find($user->government_entity_id);

    return response()->json([
        'status' => 'success',
        'government_entity' => $ge->name,
        'data' => ComplaintResource::collection($complaints),
        'meta' => [
            'current_page' => $complaints->currentPage(),
            'from'         => $complaints->firstItem(),
            'to'           => $complaints->lastItem(),
            'last_page'    => $complaints->lastPage(),
            'per_page'     => $complaints->perPage(),
            'total'        => $complaints->total(),
        ],
    ]);
}



public function changeStatus(ChangeComplaintStatusRequest $request)
{
    try {
        $complain = $this->statusService->changeStatus(
            $request->validated()
        );

        return response()->json([
            'status'   => true,
            'message'  => "Status changed successfully",
            'complain' => $complain
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => $e->getMessage()
        ], 400);
    }
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
public function getRole()
{
    $user = auth('api')->user();  // المستخدم الحالي عبر JWT

    if (!$user) {
        return response()->json([
            'message' => 'User not authenticated'
        ], 401);
    }

    return response()->json([
        'roles' => $user->getRoleNames()  // إرجاع أسماء الأدوار
    ]);
}

public function SubmitComplaint(Request $request)
{
    $user = auth('api')->user();  // المستخدم الحالي عبر JWT

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
        'user_id' => $user->id, //
        'government_entity_id' => $request->government_entity_id,
        'type' => $request->type,
        'location' => $request->location,
        'description' => $request->description,
    ]);

    if ($request->hasFile('attachments')) {
        foreach ($request->file('attachments') as $file) {
            $path = $file->store('attachments', 'public');

            $mime = $file->getClientMimeType();

            if (str_contains($mime, 'image')) {
                $type = 'image';
            } elseif (str_contains($mime, 'video')) {
                $type = 'video';
            } else {
                $type = 'document';
            }

            $complaint->attachments()->create([
                'file_path' => $path,
                'type'      => $type,
            ]);
        }
    }

    //if ($complaint->user->getFcmToken()) {

                Log::info('Sending FCM Notification', [
                    'user_id' => $complaint->user->id,
                    'reference_number' => $complaint->reference_number,
                    'new_status' => $complaint->status
                ]);

                $complaint->user->notify(new ComplaintReceivedFcm($complaint));

                Log::info('FCM Notification Sent Successfully', [
                    'user_id' => $complaint->user->id,
                    'reference_number' => $complaint->reference_number,
                    'new_status' => $complaint->status
                ]);
    //}

    return response()->json(['message' => 'Complaint submitted successfully']);
}

    public function myComplaints()
    {
        $complaints = Complaint::where('user_id', auth('api')->id())
            ->with('governmentEntity') // Important: load the relationship
            ->paginate(10);

        return ComplaintResource::collection($complaints);
    }

public function myComplaintsAtt($id)
{
    $userId = auth('api')->id();
    if (!$userId) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $attachments = ComplaintAttachment::where('complaint_id', $id)->get();

    foreach($attachments as $attachment)
    {
        $attachment->file_path = Storage::url($attachment->file_path);
    }

    return response()->json([
        'attachments' => $attachments
    ]);
}

public function show($id)
{
    $complaint = Complaint::where('complaint_id', $id)
        ->with('governmentEntity')
        ->firstOrFail();

    return new ComplaintResource($complaint);
}

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

            if (str_contains($mime, 'image')) {
                $type = 'image';
            } elseif (str_contains($mime, 'video')) {
                $type = 'video';
            } else {
                $type = 'document';
            }

            $complaint->attachments()->create([
                'file_path' => $path,
                'type'      => $type,
            ]);
        }
    }

    return response()->json([
        'message'     => 'Attachments added successfully'
    ]);
}
public function EmployeeAddNote(StoreComplaintNoteRequest $request)
{
    $employee = Auth::user();

    // جلب الشكوى باستخدام reference_number
    $complaint = Complaint::where(
        'reference_number',
        $request->reference_number
    )->firstOrFail();

    // إضافة الملاحظة في جدول history
    $history = ComplaintStatusHistory::create([
        'complaint_id' => $complaint->complaint_id,
        'handled_by'   => $employee->id,
        'note'         => $request->note,
        'changed_at'   => now(),
    ]);

    return response()->json([
        'message' => 'Note added successfully',
      'handled_by'   => "Empolyee:".$employee->name,
        'data'    => $history->note,
    ], 201);
}

    public function search(Request $request)
    {
        $reference = $request->get('reference_number');

        $complaint = Complaint::where('reference_number', $reference)
                              ->with('attachments')
                              ->first();

        $user = auth('api')->user();
        if($complaint->user_id != $user->id)
        {
            return response()->json([
                'message' => 'This reference does not belong to your complaints'
            ], 403);

        }

        if (!$complaint) {
            return response()->json([
                'status' => false,
                'message' => 'Complaint not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'complaint' => new ComplaintResource($complaint)
        ]);
    }

    public function indexGovernment()
    {
        $governments = GovernmentEntity::get();

        return response()->json([
            'status' => true,
            'governments' => $governments
        ]);
    }
}
