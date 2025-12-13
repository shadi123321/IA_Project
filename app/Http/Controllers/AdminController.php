<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\GovernmentEntity;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use App\Models\Complaint;
use App\Models\ComplaintStatusHistory;
use App\Http\Requests\StoreComplaintNoteRequest;
use Illuminate\Support\Facades\DB;




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

        // إنشاء الموظف (الحساب غير مفعل)
        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'email_verified_at' => now(), // غير مفعّل
            'government_entity_id' => $request->government_entity_id,
        ]);

        // إضافة دور الموظف
        $user->assignRole('employee');

        return response()->json([
            'status'   => true,
            'message'  => 'Employee created successfully. Account will be activated after first login.',
            'employee' => $user,
        ], 201);
    }

      public function indexEmployees(Request $request)
{
        $request->validate([
        'government_entity_id' => 'required|exists:government_entities,entity_id'
      ]);

    $employees = User::with('governmentEntity')
        ->where('government_entity_id', $request->government_entity_id)
        ->whereHas('roles', function ($q) {
            $q->where('name', 'employee');
        })
        ->get()->map(function ($employee) {
            return [
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
         'sector' => $employee->governmentEntity->entity_name
            ];
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

        $employee = User::whereHas('roles', function($q){
            $q->where('name', 'employee');
        })->find($id);

         if (!$employee) {
            return response()->json([
                'status' => false,
                'message' => 'Employee not found.'
            ], 404);
        }

        if ($request->has('government_entity_id')) $employee->government_entity_id = $request->government_entity_id;

        $employee->save();

        return response()->json([
            'status' => true,
            'message' => 'Employee updated successfully.',
            'employee' => $employee
        ]);
    }

    public function deleteEmployee($id)
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

        $employee->delete();

        return response()->json([
            'status' => true,
            'message' => 'Employee deleted successfully.'
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
 
   public function MonitoringComplains()
{
    $data = DB::table('complaint_status_histories as h')
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

        ->orderBy('h.changed_at', 'desc')
        ->get();

    return response()->json([
        'data' => $data
    ]);
}

    public function statistics()
    {
        // عدد الشكاوى حسب الحالة (Query واحد فقط)
        $complaintsByStatus = Complaint::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
$employeesCount = User::role('employee')->count(); // = 0



        return response()->json([
            'complaints' => [
                'total'      => $complaintsByStatus->sum(),
                'new'        => $complaintsByStatus['new'] ?? 0,
                'processing' => $complaintsByStatus['processing'] ?? 0,
                'resolved'   => $complaintsByStatus['resolved'] ?? 0,
                'rejected'   => $complaintsByStatus['rejected'] ?? 0,
                'Employee_Count'=>$employeesCount,
            ],

        ]);
    }

}


/////////////
 /*
    $request->validate([
        'reference_number' => 'required|exists:complaints,reference_number',
    ]);
 */



