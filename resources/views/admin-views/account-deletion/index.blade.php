@extends('layouts.admin.app')

@section('title', 'Account Deletion Requests')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{asset('public/assets/admin/img/people.png')}}" class="w--26" alt="">
            </span>
            <span>{{ translate('messages.account_deletion_requests') }}</span>
        </h1>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><strong>All:</strong> {{ $counts['all'] }}</div>
                <div class="col-md-3"><strong>Pending:</strong> {{ $counts['pending'] }}</div>
                <div class="col-md-3"><strong>Processing:</strong> {{ $counts['processing'] }}</div>
                <div class="col-md-3"><strong>Completed:</strong> {{ $counts['completed'] }}</div>
                <div class="col-md-3"><strong>Partially retained:</strong> {{ $counts['partially_retained'] }}</div>
                <div class="col-md-3"><strong>Failed:</strong> {{ $counts['failed'] }}</div>
                <div class="col-md-3"><strong>Cancelled:</strong> {{ $counts['cancelled'] }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Filter by status</label>
                        <select name="status" class="form-control js-select2-custom">
                            <option value="">All</option>
                            <option value="pending"            {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="verified"           {{ $status === 'verified' ? 'selected' : '' }}>Verified</option>
                            <option value="processing"         {{ $status === 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="completed"          {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="partially_retained" {{ $status === 'partially_retained' ? 'selected' : '' }}>Partially retained</option>
                            <option value="failed"             {{ $status === 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="cancelled"          {{ $status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn--primary">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light">
                    <tr>
                        <th>Request ID</th>
                        <th>User reference</th>
                        <th>Verification</th>
                        <th>Channel</th>
                        <th>Status</th>
                        <th>Requested at</th>
                        <th>Completed at</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $r)
                        <tr>
                            <td>{{ $r->request_uuid }}</td>
                            <td>
                                <span class="text-muted">{{ $r->masked_user_reference ?? '—' }}</span>
                                @if($r->user_id) <span class="badge badge-soft-secondary">#{{ $r->user_id }}</span> @endif
                            </td>
                            <td>{{ $r->verification_method }}</td>
                            <td>{{ $r->channel }}</td>
                            <td>
                                @php
                                    $badgeClass = match($r->status) {
                                        'completed' => 'badge-soft-success',
                                        'failed' => 'badge-soft-danger',
                                        'processing' => 'badge-soft-info',
                                        'pending' => 'badge-soft-warning',
                                        'partially_retained' => 'badge-soft-warning',
                                        'verified' => 'badge-soft-primary',
                                        'cancelled' => 'badge-soft-secondary',
                                        default => 'badge-soft-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $r->status }}</span>
                            </td>
                            <td>{{ optional($r->requested_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ optional($r->completed_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            <td>
                                <a href="{{ route('admin.customer.account-deletion.show', $r) }}" class="btn btn-sm btn--primary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No deletion requests found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
