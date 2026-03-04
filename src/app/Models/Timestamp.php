<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BreakTime;

class Timestamp extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'work_date', 'punchIn', 'punchOut'];
    protected $dates = ['punchIn', 'punchOut'];

    /**
     * ユーザー関連付け
     * 1対多
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTime()
    {
        return $this->hasMany(BreakTime::class);
    }
}
