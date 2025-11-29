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
use GuzzleHttp\Promise\Create;
use App\Services\ComplaintService;
use App\Http\Requests\ChangeComplaintStatusRequest;
class UserController extends Controller
{
     private $statusService;

    public function __construct(ComplaintService $statusService)
    {
        $this->statusService = $statusService;
    }

public function showComplaint($reference_number)//Request $request)
{
    /*
    $request->validate([
        'reference_number' => 'required|exists:complaints,reference_number',
    ]);
 */
    // جلب الشكوى مع جميع السجلات المرتبطة
    $complain = Complaint::where('reference_number', $reference_number)
        ->with(['histories' => function($query) {
            $query->orderBy('changed_at', 'asc');
        }])
        ->firstOrFail();

    // إضافة اسم الموظف لكل سجل تاريخي
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
            'description'      => $complain->description ?? null,
            'location'         => $complain->location ?? null,
            'histories'        => $histories,
        ]
    ], 200);
}

public function indexByEntity(Request $request)
{
    // جلب الـ user من الـ body مباشرة (لتجريب فقط)
    $user = User::find($request->user);

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'User not found'
        ], 404);
    }

    // جلب الشكاوى حسب جهة المستخدم
    $complaints = Complaint::where('government_entity_id', $user->government_entity_id)
        ->get();

    return response()->json([
        'status' => 'success',
        'sector'=>$user->government_entity_id,
        'data' => $complaints
    ]);
}

public function changeStatus(ChangeComplaintStatusRequest $request)

    {
        try {
            $complain = $this->statusService->changeStatus($request->validated());

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

    return response()->json(['message' => 'Complaint submitted successfully']);
}

public function myComplaints()
{
    $complaints = Complaint::where('user_id', auth('api')->id())->get();

    return response()->json($complaints);
}

public function myComplaintsAtt()
{
    $userId = auth('api')->id();
    if (!$userId) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    $attachments = ComplaintAttachment::whereHas('complaint', function ($q) {
        $q->where('user_id', auth('api')->id());
    })->get();

    foreach($attachments as $attachment)
    {
        $attachment->file_path = Storage::url($attachment->file_path);
    }

    return response()->json([
        'attachments' => $attachments
    ]);
}

public function show($reference_number)
{
    $complaint = Complaint::where('reference_number', $reference_number)
        ->with(['histories' => function($query) {
            $query->orderBy('changed_at', 'asc');
        }])
        ->firstOrFail();

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
        'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,mp4,avi,mov|max:10240'
    ]);

    $complaint = Complaint::where('reference_number', $reference)->firstOrFail();

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

}
