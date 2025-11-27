<?php
namespace App\Repositories;

use App\Models\Complaint;
use Illuminate\Support\Facades\DB;

class ComplaintRepository
{
    public function findByReferenceForUpdate(string $referenceNumber)
    {
        return Complaint::where('reference_number', $referenceNumber)
                        ->lockForUpdate()
                        ->first();
    }

    public function saveHistory($complaint, $userId, $status, $note = null)
    {
        return $complaint->histories()->create([
            'handled_by' => $userId,
            'status'     => $status,
            'note'       => $note,
            'changed_at' => now(),
        ]);
    }

    public function updateStatus($complaint, $status)
    {
        $complaint->status = $status;
        $complaint->save();
        return $complaint;
    }
}
