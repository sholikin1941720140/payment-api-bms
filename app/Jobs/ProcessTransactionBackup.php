<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Models\TransactionBackup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class ProcessTransactionBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function handle()
    {
        TransactionBackup::create([
            'reff' => $this->payment->reff,
            'amount' => $this->payment->amount,
            'name' => $this->payment->name,
            'code' => $this->payment->code,
            'status' => $this->payment->status,
            'processed_at' => Carbon::now()
        ]);
    }
}