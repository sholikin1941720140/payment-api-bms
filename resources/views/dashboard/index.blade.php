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
                    <h5>Data Pembayaran (total {{ $payments->total() }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
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
                                    <td>{{ $loop->iteration + ($payments->currentPage() - 1) * $payments->perPage() }}</td>
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

                    <div class="d-flex justify-content-end">
                        {{ $payments->appends(request()->query())->links() }}
                    </div>                    
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            let originalValues = {};

            $('.status-update').on('focus', function() {
                originalValues[$(this).data('id')] = $(this).val();
            });

            $('.status-update').change(function() {
                var paymentId = $(this).data('id');
                var newStatus = $(this).val();
                var selectElement = $(this);
                var originalStatus = originalValues[paymentId];

                selectElement.prop('disabled', true);
                var loadingText = selectElement.closest('td').find('.loading-status');
                if (loadingText.length === 0) {
                    selectElement.after('<span class="loading-status text-muted ms-2"><i class="fa fa-spinner fa-spin"></i> Updating...</span>');
                }

                $.ajax({
                    url: '/dashboard/update-status/' + paymentId,
                    type: 'POST',
                    data: {
                        status: newStatus,
                        id: paymentId,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if(response.success) {
                            showNotification('success', response.message || 'Status berhasil diubah');
                            updateStatusIndicators(paymentId, newStatus, response.payment);
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    },
                    error: function(xhr) {
                        var errorMessage = 'Terjadi kesalahan saat mengupdate status';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join(', ');
                        }
                        selectElement.val(originalStatus);

                        showNotification('error', errorMessage);
                    },
                    complete: function() {
                        selectElement.prop('disabled', false);
                        selectElement.closest('td').find('.loading-status').remove();
                    }
                });
            });

            function updateStatusIndicators(paymentId, status, paymentData) {
                var row = $('[data-id="' + paymentId + '"]').closest('tr');

                var statusBadge = row.find('.status-badge');
                statusBadge.removeClass('badge-pending badge-paid badge-expired');
                statusBadge.addClass('badge-' + status);
                statusBadge.text(status.toUpperCase());

                if (paymentData.is_expired_by_time) {
                    row.find('.expired-indicator').show();
                }
            }

            function showNotification(type, message) {
                $('.notification-alert').remove();

                var alertClass = type === 'success' ? 'alert-success' : 
                                type === 'error' ? 'alert-danger' : 
                                'alert-info';

                var notification = `
                    <div class="alert ${alertClass} alert-dismissible fade show notification-alert" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                        <strong>${type.charAt(0).toUpperCase() + type.slice(1)}!</strong> ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                $('body').append(notification);

                setTimeout(function() {
                    $('.notification-alert').fadeOut();
                }, 5000);
            }
        });
    </script>
@endsection