<?php

namespace App\Services;

use App\Repositories\ComplaintRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ComplaintService
{
    protected ComplaintRepository $complaintRepo;
    protected int $lockMinutes = 30;

    public function __construct(ComplaintRepository $complaintRepo)
    {
        $this->complaintRepo = $complaintRepo;
    }

    /* =========================
       Change Complaint Status
    ========================== */
    public function changeStatus(array $data)
    {
        return DB::transaction(function () use ($data) {

            /* ---------- Row Lock ---------- */
            $complain = $this->complaintRepo
                ->findByReferenceForUpdate($data['reference_number']);

            if (!$complain) {
                throw new Exception("Complaint not found");
            }

            /* ---------- Closed Complaint ---------- */
            if (in_array($complain->status, ['resolved', 'rejected'])) {
                throw new Exception(
                    "This complaint is already closed and cannot be modified."
                );
            }

            $userId = Auth::id();

            /* ---------- Last History (Cached) ---------- */
            $cacheKey = "complaint:last-history:{$complain->id}";

            $lastHistory = Cache::remember(
                $cacheKey,
                now()->addMinutes(5),
                function () use ($complain) {
                    return $complain->histories()
                        ->with(['handler:id,name'])
                        ->orderBy('changed_at', 'desc')
                        ->first();
                }
            );

            if ($lastHistory) {
                $minutesPassed = Carbon::parse($lastHistory->changed_at)
                    ->diffInMinutes(now());

                $remaining = max($this->lockMinutes - $minutesPassed, 0);

                // لا تمنع نفس الموظف
                if ($lastHistory->handled_by != $userId && $remaining > 0) {
                    $employeeName = $lastHistory->handler?->name ?? 'Unknown';
                    throw new Exception(
                        "Complaint is locked by {$employeeName}. Try again after {$remaining} minutes."
                    );
                }
            }

            /* ---------- Status Transition ---------- */
            $this->validateStatusTransition(
                $complain->status,
                $data['status']
            );

            /* ---------- Update Status ---------- */
            $this->complaintRepo
                ->updateStatus($complain, $data['status']);

            $this->complaintRepo->saveHistory(
                $complain,
                $userId,
                $data['status'],
                $data['note'] ?? null
            );

            /* ---------- Invalidate Cache ---------- */
            Cache::forget($cacheKey);
            Cache::forget("complaint:details:{$complain->id}");

            return $complain;
        });
    }

    /* =========================
       Status Rules
    ========================== */
    protected function validateStatusTransition(string $currentStatus,  string $newStatus ): void
     {
        switch ($newStatus) {
            case 'processing':
                if ($currentStatus !== 'new') {
                    throw new Exception(
                        "Only NEW complaints can move to PROCESSING."
                    );
                }
                break;

            case 'resolved':
                if ($currentStatus !== 'processing') {
                    throw new Exception(
                        "Only PROCESSING complaints can be resolved."
                    );
                }
                break;

            case 'rejected':
                if ($currentStatus !== 'new') {
                    throw new Exception(
                        "Only NEW complaints can be rejected."
                    );
                }
                break;

            case 'new':
                throw new Exception("Cannot revert back to NEW.");
        }
    }
}
