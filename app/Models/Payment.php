<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reff',
        'amount',
        'original_amount',
        'name',
        'hp',
        'code',
        'expired',
        'paid_at',
        'status'
    ];

    protected $casts = [
        'expired' => 'datetime',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'original_amount' => 'decimal:2'
    ];

    public function isExpired()
    {
        return Carbon::now()->gt($this->expired);
    }

    public function generateCode()
    {
        return '8834' . $this->hp;
    }
}