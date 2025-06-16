<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

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

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('dashboard.index', compact('payments'));
    }

    public function updateStatus(Request $request, Payment $payment)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,expired'
        ]);

        $payment->update([
            'status' => $request->status,
            'paid_at' => $request->status === 'paid' ? now() : null
        ]);

        return response()->json(['success' => true]);
    }
}