<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Timestamp;

class BreakTime extends Model
{
    use HasFactory;
    protected $table = 'breaks';
    protected $fillable = ['timestamp_id', 'breakIn', 'breakOut'];
    protected $dates = ['breakIn', 'breakOut'];

    /**
     * Timestamp関連付け
     * 1対多
     */
    public function timestamp()
    {
        return $this->belongsTo(Timestamp::class);
    }

}
