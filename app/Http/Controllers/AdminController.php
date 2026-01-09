<?php

namespace App\Http\Controllers;
use PDF;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\GovernmentEntity;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use App\Models\Complaint;
use App\Models\ComplaintStatusHistory;
use App\Http\Requests\StoreComplaintNoteRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator; // <- هنا
use App\Http\Requests\StoreGovernmentEntityRequest;



use function Symfony\Component\Clock\now;

class AdminController extends Controller
{

    public function storeEmployee(Request $request)
    {
        $request->validate([
            'name'                 => 'required|string|max:255',
            'email'                => 'required|email|unique:users,email',
            'password'             => 'required|min:6',
            'government_entity_id' => 'required|exists:government_entities,entity_id',
        ]);

        $user = User::create([
            'name'  => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
            'government_entity_id' => $request->government_entity_id,
        ]);

        $user->assignRole('employee');

        // Invalidate cache
        Cache::forget("employees:entity:{$request->government_entity_id}");

        return response()->json([
            'status' => true,
            'message' => 'Employee created successfully.',
            'employee' => $user,
        ], 201);
    }
   public function indexEmployees(Request $request)
    {
        $request->validate([
            'government_entity_id' => 'required|exists:government_entities,entity_id'
        ]);

        $entityId = $request->government_entity_id;
        $cacheKey = "employees:entity:$entityId";

        $employees = Cache::remember($cacheKey, 600, function () use ($entityId) {
            return User::with('governmentEntity')
                ->where('government_entity_id', $entityId)
                ->whereHas('roles', fn($q) => $q->where('name', 'employee'))
                ->get()
                ->map(function ($employee) {
                    return [
                        'id' => $employee->id,
                        'name' => $employee->name,
                        'email' => $employee->email,
                        'sector' => $employee->governmentEntity->entity_name
                    ];
                });
        });

        return response()->json([
            'status' => true,
            'employees' => $employees
        ]);
    }


    public function showEmployee($id)
    {
        $employee = User::whereHas('roles', function($q){
            $q->where('name', 'employee');
        })->find($id);

        if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Employee not found.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'employee' => $employee
        ]);
    }

   public function updateEmployee(Request $request, $id)
    {
        $request->validate([
            'government_entity_id' => 'sometimes|exists:government_entities,entity_id'
        ]);

        $employee = User::whereHas('roles', fn($q) => $q->where('name', 'employee'))
            ->find($id);

        if (!$employee) {
            return response()->json(['status' => false], 404);
        }

        $oldEntity = $employee->government_entity_id;

        if ($request->has('government_entity_id')) {
            $employee->government_entity_id = $request->government_entity_id;
        }

        $employee->save();

        Cache::forget("employees:entity:$oldEntity");
        Cache::forget("employees:entity:{$employee->government_entity_id}");

        return response()->json([
            'status' => true,
            'message' => 'Employee updated successfully.',
            'employee' => $employee
        ]);
    }
   public function deleteEmployee($id)
    {
        $employee = User::whereHas('roles', fn($q) => $q->where('name', 'employee'))
            ->find($id);

        if (!$employee) {
            return response()->json(['status' => false], 404);
        }

        Cache::forget("employees:entity:{$employee->government_entity_id}");
        $employee->delete();

        return response()->json([
            'status' => true,
            'message' => 'Employee deleted successfully.'
        ]);
    }

    public function indexGovernment()
    {
        /*$governments = Cache::remember(
            'governments:all',
            1800,
            fn() => GovernmentEntity::get()
        );*/
        $governments = GovernmentEntity::get();
        return response()->json([
            'status' => true,
            'governments' => $governments
        ]);
    }

    public function addGovernmentEntity(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'location' => 'nullable|string|max:255',
        'contact_email' => 'nullable|email|max:255',
        'contact_phone' => 'nullable|string|max:50',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);
    }

    // التحقق إن كانت الجهة موجودة مسبقًا بنفس الاسم
    $existing = GovernmentEntity::where('name', $request->name)->first();
    if ($existing) {
        return response()->json([
            'status' => 'error',
            'message' => 'Government entity already exists'
        ], 409); // 409 = Conflict
    }

    $entity = GovernmentEntity::create($request->all());

    return response()->json([
        'status' => 'success',
        'data' => $entity
    ], 201);
}
public function deleteGovernmentEntity($id)
{
    $entity = GovernmentEntity::find($id);

    if (!$entity) {
        return response()->json([
            'status' => 'error',
            'message' => 'Government entity not found'
        ], 404);
    }

    $entity->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'Government entity deleted successfully',
        'entity'=>$entity
    ]);
}

 public function MonitoringComplains(Request $request)
{
    $perPage = 20; // عدد العناصر لكل صفحة
    $page = $request->get('page', 1); // الصفحة الحالية من الـ query parameter

    $data = Cache::remember("complaints:monitoring:page_$page", 300, function () use ($perPage) {
        return DB::table('complaint_status_histories as h')
            ->join('complaints as c', 'h.complaint_id', '=', 'c.complaint_id')
            ->join('users as citizen', 'c.user_id', '=', 'citizen.id')
            ->leftJoin('users as employee', 'h.handled_by', '=', 'employee.id')
            ->select([
                'c.reference_number',
                'citizen.name as citizen_name',
                'h.status',
                'h.note',
                'employee.name as handled_by_employee',
                'h.changed_at',
            ])
            ->orderByDesc('h.changed_at')
            ->paginate($perPage);
    });

    return response()->json($data);
}


   public function statistics()
    {
        $stats = Cache::remember('complaints:statistics', 300, function () {
            $byStatus = Complaint::selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            return [
                'total' => $byStatus->sum(),
                'new' => $byStatus['new'] ?? 0,
                'processing' => $byStatus['processing'] ?? 0,
                'resolved' => $byStatus['resolved'] ?? 0,
                'rejected' => $byStatus['rejected'] ?? 0,
                'Employee_Count' => User::role('employee')->count(),
            ];
        });

        return response()->json(['complaints' => $stats]);
    }


public function search(Request $request)
{
    $search = trim($request->Key_Search);


    // إذا كان البحث فارغًا
    /*
    if (!$search) {
        return response()->json([
            'message' => 'Key_Search is required',
            'data' => []
        ], 422);
    }
*/
    $page = $request->get('page', 1); // Pagination page
    $reference = str_replace('CMP-', '', $search);

    // Cache Key ذكي لكل كلمة بحث وصفحة
    $cacheKey = "search:" . md5($search) . ":page:$page";

    // Cache لمدة 2 دقيقة
    $result = Cache::remember($cacheKey, 120, function () use ($search, $reference) {
        // البحث باسم المواطن
        $userComplaints = Complaint::whereHas('user', function ($q) use ($search) {
                $q->role('citizen')
                  ->where('name', 'like', "%{$search}%");
            })
            ->with('user')
            ->paginate(20);

        // البحث بالـ reference_number
        $referenceComplaints = Complaint::with('user')
            ->where('reference_number', 'like', "%CMP-{$reference}%")
            ->paginate(20);

        return [
            'user_complaints' => $userComplaints,
            'reference_complaints' => $referenceComplaints,
        ];
    });

    // التأكد من وجود نتائج
    if ($result['user_complaints']->isEmpty() && $result['reference_complaints']->isEmpty()) {
        return response()->json([
            'message' => 'No results found',
            'data' => []
        ], 404);
    }

    return response()->json([
        'data' => $result
    ]);
}
public function MonitoringComplainsDailyPDF(Request $request)
{
    $date = $request->date
        ? Carbon::parse($request->date)->toDateString()
        : Carbon::today()->toDateString();

    $data = DB::table('complaint_status_histories as h')
        ->join('complaints as c', 'h.complaint_id', '=', 'c.complaint_id')
        ->join('users as citizen', 'c.user_id', '=', 'citizen.id')
        ->leftJoin('users as employee', 'h.handled_by', '=', 'employee.id')
        ->whereDate('h.changed_at', $date)
        ->select([
            'c.reference_number',
            'citizen.name as citizen_name',
            'h.status',
            'h.note',
            'employee.name as handled_by_employee',
            'h.changed_at',
        ])
        ->orderByDesc('h.changed_at')
        ->get();

    $pdf = PDF::loadView('pdf.monitoring_daily', [
        'data' => $data,
        'date' => $date
    ]);

    return $pdf->download("monitoring_report_$date.pdf");
}

    /*public function addEntity(StoreGovernmentEntityRequest $request)
    {
        $government = GovernmentEntity::create($request->validated());

        return response()->json([
            'status'    => true,
            'message'   => 'Government entity created successfully',
            'government'=> $government
        ], 201);

    }

    public function deleteEntity($id)
    {
        $government = GovernmentEntity::find($id);

        if (!$government) {
            return response()->json([
                'status' => false,
                'message' => 'Government entity not found'
            ], 404);
        }

        $government->delete();

        return response()->json([
            'status' => true,
            'message' => 'Government entity deleted successfully'
        ]);
    }*/


}


/////////////
 /*
    $request->validate([
        'reference_number' => 'required|exists:complaints,reference_number',
    ]);
 */



