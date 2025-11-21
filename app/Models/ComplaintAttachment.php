<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComplaintAttachment extends Model
{
    use HasFactory;
     protected $fillable = [
        'complaint_id',
        'file_path',
        'type',
    ];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }
}
