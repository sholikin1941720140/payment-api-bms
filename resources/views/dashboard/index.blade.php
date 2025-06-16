@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-12">
        <h2>Payment Management Dashboard</h2>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Filter Data</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('dashboard') }}">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="name" class="form-label">Nama Pelanggan</label>
                            <input type="text" name="name" class="form-control" value="{{ request('name') }}" placeholder="Cari nama...">
                        </div>
                        <div class="col-md-3">
                            <label for="reff" class="form-label">Reference</label>
                            <input type="text" name="reff" class="form-control" value="{{ request('reff') }}" placeholder="Cari reff...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Data Pembayaran ({{ $payments->total() }} total)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Reference</th>
                                <th>Nama</th>
                                <th>Amount</th>
                                <th>Code</th>
                                <th>Status</th>
                                <th>Expired</th>
                                <th>Paid At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                            <tr>
                                <td>{{ $payment->id }}</td>
                                <td>{{ $payment->reff }}</td>
                                <td>{{ $payment->name }}</td>
                                <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                <td>{{ $payment->code }}</td>
                                <td>
                                    <span class="badge bg-{{ $payment->status == 'paid' ? 'success' : ($payment->status == 'expired' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td>{{ $payment->expired->format('d/m/Y H:i') }}</td>
                                <td>{{ $payment->paid_at ? $payment->paid_at->format('d/m/Y H:i') : '-' }}</td>
                                <td>
                                    <select class="form-select form-select-sm status-update" data-id="{{ $payment->id }}">
                                        <option value="pending" {{ $payment->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="paid" {{ $payment->status == 'paid' ? 'selected' : '' }}>Paid</option>
                                        <option value="expired" {{ $payment->status == 'expired' ? 'selected' : '' }}>Expired</option>
                                    </select>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">Tidak ada data payment</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $payments->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.status-update').change(function() {
        var paymentId = $(this).data('id');
        var newStatus = $(this).val();
        var selectElement = $(this);

        $.ajax({
            url: '/dashboard/payment/' + paymentId + '/update-status',
            type: 'POST',
            data: {
                status: newStatus
            },
            success: function(response) {
                if(response.success) {
                    location.reload();
                }
            },
            error: function() {
                alert('Terjadi kesalahan saat mengupdate status');
                location.reload();
            }
        });
    });
});
</script>
@endsection