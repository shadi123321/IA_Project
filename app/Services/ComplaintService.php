<?php
namespace App\Services;

use App\Repositories\ComplaintRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ComplaintService
{
    protected $complaintRepo;
    protected $lockMinutes = 30;

    public function __construct(ComplaintRepository $complaintRepo)
    {
        $this->complaintRepo = $complaintRepo;
    }

    public function changeStatus(array $data)
    {
        return DB::transaction(function () use ($data) {

            $complain = $this->complaintRepo->findByReferenceForUpdate($data['reference_number']);

            if (!$complain) {
                throw new Exception("Complaint not found");
            }

            // منع تعديل الشكوى إذا كانت مغلقة
            if (in_array($complain->status, ['resolved', 'rejected'])) {
                throw new Exception("This complaint is already closed and cannot be modified.");
            }

        $lastHistory = $complain->histories()->orderBy('changed_at', 'desc')->first();
if ($lastHistory) {
    $minutesPassed = Carbon::parse($lastHistory->changed_at)->diffInMinutes(now());
    $remaining = max($this->lockMinutes - $minutesPassed, 0);

    // لا تمنع نفس الموظف من التعديل
    if ($lastHistory->handled_by != $data['user_id'] && $remaining > 0) {
        $employeeName = $lastHistory->handledBy ? $lastHistory->handledBy->name : 'Unknown';
        throw new Exception("Complaint is locked by {$employeeName}. Try again after {$remaining} minutes.");
    }
}



            // تحقق من منطق الانتقال بين الحالات
            $this->validateStatusTransition($complain->status, $data['status']);

            // تحديث الحالة وحفظ التاريخ
            $this->complaintRepo->updateStatus($complain, $data['status']);
            $this->complaintRepo->saveHistory($complain, $data['user_id'], $data['status'], $data['note'] ?? null);

            return $complain;
        });
    }

    protected function validateStatusTransition($currentStatus, $newStatus)
    {
        switch ($newStatus) {
            case 'processing':
                if ($currentStatus !== 'new') throw new Exception("Only NEW complaints can move to PROCESSING.");
                break;
            case 'resolved':
                if ($currentStatus !== 'processing') throw new Exception("Only PROCESSING complaints can be resolved.");
                break;
            case 'rejected':
                if ($currentStatus !== 'new') throw new Exception("Only NEW complaints can be rejected.");
                break;
            case 'new':
                throw new Exception("Cannot revert back to NEW.");
        }
    }
}
