<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use App\Jobs\ProcessTransactionBackup;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Payment::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('reff')) {
            $query->where('reff', 'like', '%' . $request->reff . '%');
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('dashboard.index', compact('payments'));
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,expired',
            'id' => 'required|exists:payments,id'
        ]);

        $payment = Payment::findOrFail($request->id);
        $newStatus = $request->status;
        $currentStatus = $payment->status;

        $isExpiredByTime = Carbon::now()->gt($payment->expired);

        $validationResult = $this->validateStatusChange($payment, $newStatus, $currentStatus, $isExpiredByTime);

        if (!$validationResult['valid']) {
            return response()->json(['error' => $validationResult['message']], 403);
        }

        $updateData = ['status' => $newStatus];

        if ($newStatus === 'paid') {
            $updateData['paid_at'] = Carbon::now();
        } elseif ($newStatus !== 'paid' && $payment->paid_at) {
            $updateData['paid_at'] = null;
        }

        $payment->update($updateData);

        ProcessTransactionBackup::dispatch($payment->fresh());

        return response()->json([
            'success' => true,
            'message' => "Status berhasil diubah menjadi {$newStatus}",
            'payment' => [
                'id' => $payment->id,
                'status' => $newStatus,
                'expired_at' => $payment->expired->format('Y-m-d H:i:s'),
                'is_expired_by_time' => $isExpiredByTime
            ]
        ]);
    }

    private function validateStatusChange($payment, $newStatus, $currentStatus, $isExpiredByTime)
    {
        if ($currentStatus === $newStatus) {
            return [
                'valid' => false,
                'message' => "Status sudah {$currentStatus}, tidak perlu diubah"
            ];
        }

        if ($currentStatus === 'paid' && $newStatus !== 'paid') {
            return [
                'valid' => false,
                'message' => 'Payment yang sudah dibayar tidak dapat diubah statusnya'
            ];
        }

        if ($newStatus === 'paid') {
            if ($isExpiredByTime) {
                return [
                    'valid' => false,
                    'message' => 'Tidak dapat mengubah status menjadi paid karena payment sudah expired'
                ];
            }

            if ($currentStatus === 'expired') {
                return [
                    'valid' => false,
                    'message' => 'Payment yang sudah expired tidak dapat diubah menjadi paid'
                ];
            }
        }

        if ($newStatus === 'expired') {
            if ($currentStatus === 'paid') {
                return [
                    'valid' => false,
                    'message' => 'Payment yang sudah paid tidak dapat diubah menjadi expired'
                ];
            }
        }

        if ($newStatus === 'pending') {
            if ($currentStatus === 'paid') {
                return [
                    'valid' => false,
                    'message' => 'Payment yang sudah paid tidak dapat dikembalikan ke pending'
                ];
            }

            if ($isExpiredByTime) {
                return [
                    'valid' => false,
                    'message' => 'Tidak dapat mengubah status menjadi pending karena waktu sudah expired'
                ];
            }
        }

        if ($isExpiredByTime && $newStatus !== 'expired' && $currentStatus !== 'paid') {
            return [
                'valid' => false,
                'message' => 'Payment sudah melewati batas waktu, status hanya bisa expired'
            ];
        }

        return ['valid' => true, 'message' => 'Valid'];
    }
}