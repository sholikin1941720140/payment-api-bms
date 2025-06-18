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

            $check = Payment::where('reff', $reff)->first();
            if ($check) {
                return response()->json(['error' => 'Reff already exists'], 403);
            }

            try {
                $decodedExpired = str_replace(' ', '+', $expired);

                $expiredDate = Carbon::parse($decodedExpired);
                $now = Carbon::now();

                if ($expiredDate->lte($now)) {
                    return response()->json([
                        'error' => 'Expired date must be in the future',
                        'current_time' => $now->toISOString(),
                        'expired_time' => $expiredDate->toISOString(),
                        'timezone_info' => [
                            'current_tz' => $now->getTimezone()->getName(),
                            'expired_tz' => $expiredDate->getTimezone()->getName()
                        ]
                    ], 400);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Invalid expired date format',
                    'received' => $expired,
                    'decoded' => urldecode($expired ?? ''),
                    'expected_format' => 'e.g. 2025-07-28T09:12:48+07:00',
                    'debug' => $e->getMessage()
                ], 400);
            }

            $originalAmount = (int) $amount;
            $totalAmount = $originalAmount + 2500;
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
                'amount' => (string) $totalAmount,
                'reff' => $reff,
                'expired' => $expiredDate->toISOString(),
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

            if ($payment->status === 'paid') {
                return response()->json(['error' => 'Payment already processed'], 403);
            }

            if ($payment->status === 'expired') {
                return response()->json(['error' => 'Payment already exists with expired status'], 403);
            }

            if ($payment->isExpired()) {
                $payment->update(['status' => 'expired']);

                ProcessTransactionBackup::dispatch($payment);

                return response()->json([
                    'amount' => (string)$payment->amount,
                    'reff' => $payment->reff,
                    'name' => $payment->name,
                    'code' => $payment->code,
                    'status' => 'expired'
                ]);
            }

            $payment->update([
                'status' => 'paid',
                'paid_at' => Carbon::now()
            ]);

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
                'paid' => $payment->paid_at ? $payment->paid_at->format('c') : null,
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