<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $timestamps = true;
    
    const TYPE_DEPOSIT = 1;
    const TYPE_WITHDRAWAL = 2;

    protected $fillable = [
        'user_id',
        'type',
        'currency_id',
        'amount',
        'content'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function currency(){
        return $this->belongsTo(Currency::class);
    }
}
