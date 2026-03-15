<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Timestamp;

class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'timestamp_id', 'name', 'target_date', 'status', 'reason', 'payload', 'details_link', 'approved_by', 'approved_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function timestamp()
    {
        return $this->belongsTo(Timestamp::class);
    }
}
