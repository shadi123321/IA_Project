<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Complaint extends Model
{
        use HasFactory;

     protected $primaryKey = 'complaint_id';

    protected $fillable = [
        'reference_number',
        'user_id',
        'government_entity_id',
        'type',
        'location',
        'description',
        'status',
    ];

    // المواطن
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // الجهة الحكومية
    public function governmentEntity()
    {
        return $this->belongsTo(GovernmentEntity::class, 'government_entity_id', 'entity_id');
    }

    // المرفقات
    public function attachments()
    {
        return $this->hasMany(ComplaintAttachment::class, 'complaint_id');
    }

    // سجل التحديثات
    public function histories()
    {
        return $this->hasMany(ComplaintStatusHistory::class, 'complaint_id');
    }
}
