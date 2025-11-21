<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GovernmentEntity extends Model
{
 use HasFactory;
    protected $primaryKey = 'entity_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'description',
        'location',
        'contact_email',
        'contact_phone',
    ];

    // العلاقة مع الـ Users
    public function users()
    {
        return $this->hasMany(User::class, 'government_entity_id', 'entity_id');
    }

    // العلاقة مع الشكاوى
    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'government_entity_id', 'entity_id');
    }
}
