<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Jobs\ProcessTransactionBackup;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function order(Request $request)
    {
        try {
            $amount = $request->get('amount');
            $reff = $request->get('reff');
            $expired = $request->get('expired');
            $name = $request->get('name');
            $hp = $request->get('hp');

            if (!$amount || $amount <= 0) {
                return response()->json(['error' => 'Amount must be positive'], 400);
            }

            try {
                $expiredDate = Carbon::parse(urldecode($expired));
                if ($expiredDate->isPast()) {
                    return response()->json(['error' => 'Expired date must be in the future'], 400);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid expired date format'], 400);
            }

            $originalAmount = $amount;
            $totalAmount = $amount + 2500;
            $code = '8834' . $hp;

            $payment = Payment::create([
                'reff' => $reff,
                'amount' => $totalAmount,
                'original_amount' => $originalAmount,
                'name' => $name,
                'hp' => $hp,
                'code' => $code,
                'expired' => $expiredDate,
                'status' => 'pending'
            ]);

            return response()->json([
                'amount' => (string)$totalAmount,
                'reff' => $reff,
                'expired' => $expiredDate->format('c'),
                'name' => $name,
                'code' => $code
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    public function payment(Request $request)
    {
        try {
            $reff = $request->get('reff');
            
            $payment = Payment::where('reff', $reff)->first();

            if (!$payment) {
                return response()->json(['error' => 'Payment not found'], 403);
            }

            // Cek apakah sudah expired
            if ($payment->isExpired()) {
                $payment->update(['status' => 'expired']);
                
                // Dispatch job untuk backup
                ProcessTransactionBackup::dispatch($payment);
                
                return response()->json([
                    'amount' => (string)$payment->amount,
                    'reff' => $payment->reff,
                    'name' => $payment->name,
                    'code' => $payment->code,
                    'status' => 'expired'
                ]);
            }

            // Cek apakah sudah dibayar (double payment)
            if ($payment->status === 'paid') {
                return response()->json(['error' => 'Payment already processed'], 403);
            }

            // Update status menjadi paid
            $payment->update([
                'status' => 'paid',
                'paid_at' => Carbon::now()
            ]);

            // Dispatch job untuk backup
            ProcessTransactionBackup::dispatch($payment);

            return response()->json([
                'amount' => (string)$payment->amount,
                'reff' => $payment->reff,
                'name' => $payment->name,
                'code' => $payment->code,
                'status' => 'paid'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    public function checkStatus(Request $request)
    {
        try {
            $reff = $request->get('reff');
            
            $payment = Payment::where('reff', $reff)->first();

            if (!$payment) {
                return response()->json(['error' => 'Payment not found'], 403);
            }

            $response = [
                'amount' => (string)$payment->amount,
                'reff' => $payment->reff,
                'name' => $payment->name,
                'expired' => $payment->expired->format('c'),
                'code' => $payment->code,
                'status' => $payment->status
            ];

            if ($payment->paid_at) {
                $response['paid'] = $payment->paid_at->format('c');
            }

            return response()->json($response);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}