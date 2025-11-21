<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ComplaintStatusHistory extends Model
{
    use HasFactory;

     protected $primaryKey = 'history_id';

    protected $fillable = [
        'complaint_id',
        'handled_by',
        'status',
        'note',
        'changed_at',
    ];

    public $timestamps = false;

    public function complaint()
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
