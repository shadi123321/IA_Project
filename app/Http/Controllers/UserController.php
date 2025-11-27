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
  public function OwisSubmitComplain(Request $request)
{
    $request->validate([
        'type' => 'required|string',
        'government_entity_id' => 'required',/* |exists:government_entities,entity_id',*/
        'location' => 'nullable|string',
        'description' => 'nullable|string',
        'attachments.*' => 'file|max:4096'
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
public function OwismyComplaints()
{
    /*$userId = auth('api')->id();
    if (!$userId) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }*/

    $complaints = Complaint::with(['attachments', 'governmentEntity'])
        ->where('user_id', 3)
        ->latest('complaint_id')
        ->get();


    $complaints->each(function ($complaint) {
        $complaint->attachments->transform(function ($attachment) {
            $attachment->file_path = Storage::url($attachment->file_path);
            return $attachment;
        });
    });

    return response()->json($complaints);
}




public function Owisshow($reference)
{
    $complaint = Complaint::where('reference_number', $reference)
        ->with(['attachments', 'histories.handler'])
        ->firstOrFail();


    $complaint->attachments->transform(function ($attachment) {
        $attachment->file_path = Storage::url($attachment->file_path);
        return $attachment;
    });

    return response()->json($complaint);
}


public function OwisaddAttachment(Request $request, $reference)
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

}
