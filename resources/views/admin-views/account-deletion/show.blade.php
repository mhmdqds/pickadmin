@extends('layouts.admin.app')

@section('title', 'Account Deletion Request')

@section('content')
<div class="content container-fluid">
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{asset('public/assets/admin/img/people.png')}}" class="w--26" alt="">
            </span>
            <span>Deletion request — {{ $deletion->request_uuid }}</span>
        </h1>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Summary</strong></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><strong>Status:</strong> {{ $deletion->status }}</div>
                <div class="col-md-3"><strong>Method:</strong> {{ $deletion->verification_method }}</div>
                <div class="col-md-3"><strong>Channel:</strong> {{ $deletion->channel }}</div>
                <div class="col-md-3"><strong>User ref:</strong> {{ $deletion->masked_user_reference ?? '—' }} @if($deletion->user_id) (#{{ $deletion->user_id }}) @endif</div>

                <div class="col-md-3"><strong>Requested at:</strong> {{ optional($deletion->requested_at)->format('Y-m-d H:i:s') ?? '—' }}</div>
                <div class="col-md-3"><strong>Verified at:</strong> {{ optional($deletion->verified_at)->format('Y-m-d H:i:s') ?? '—' }}</div>
                <div class="col-md-3"><strong>Processing started:</strong> {{ optional($deletion->processing_started_at)->format('Y-m-d H:i:s') ?? '—' }}</div>
                <div class="col-md-3"><strong>Completed at:</strong> {{ optional($deletion->completed_at)->format('Y-m-d H:i:s') ?? '—' }}</div>

                <div class="col-md-3"><strong>Failed at:</strong> {{ optional($deletion->failed_at)->format('Y-m-d H:i:s') ?? '—' }}</div>
                <div class="col-md-6"><strong>Failure reason:</strong> {{ $deletion->failure_reason ?? '—' }}</div>
                <div class="col-md-3"><strong>IP:</strong> {{ $deletion->ip_address ?? '—' }}</div>

                <div class="col-md-12">
                    <strong>Retention reason:</strong>
                    <pre class="bg-light p-3 rounded mt-2 mb-0" style="white-space: pre-wrap;">{{ $deletion->retention_reason ?? '—' }}</pre>
                </div>
            </div>
        </div>
        @if(in_array($deletion->status, ['failed', 'partially_retained']))
            <div class="card-footer">
                <form method="POST" action="{{ route('admin.customer.account-deletion.retry', $deletion) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn--primary"
                            onclick="return confirm('Retry this deletion request? The job will be re-dispatched.');">
                        Retry
                    </button>
                </form>
            </div>
        @endif
        @if($deletion->status === 'pending')
            <div class="card-footer">
                <form method="POST" action="{{ route('admin.customer.account-deletion.cancel', $deletion) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn--danger"
                            onclick="return confirm('Cancel this deletion request? The user will need to start over.');">
                        Cancel request
                    </button>
                </form>
            </div>
        @endif
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Deletion summary</strong></div>
        <div class="card-body">
            @if($deletion->deletion_summary)
                <div class="row">
                    @foreach(['deleted', 'anonymized', 'retained'] as $bucket)
                        <div class="col-md-4">
                            <h6 class="text-capitalize">{{ $bucket }}</h6>
                            <ul class="list-group">
                                @forelse($deletion->deletion_summary[$bucket] ?? [] as $table => $count)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>{{ $table }}</span>
                                        <span class="badge badge-soft-primary">{{ $count }}</span>
                                    </li>
                                @empty
                                    <li class="list-group-item text-muted">None</li>
                                @endforelse
                            </ul>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted mb-0">No summary recorded yet (deletion not yet processed).</p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><strong>Audit log</strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>When</th>
                        <th>Action</th>
                        <th>Actor</th>
                        <th>Status</th>
                        <th>Target</th>
                        <th>Affected</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $log->action }}</td>
                            <td>{{ $log->actor_type }}{{ $log->actor_id ? '#'.$log->actor_id : '' }}</td>
                            <td>{{ $log->status ?? '—' }}</td>
                            <td>{{ $log->target_table ?? '—' }}</td>
                            <td>{{ $log->affected_rows ?? '—' }}</td>
                            <td>{{ $log->message ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No log entries.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('admin.customer.account-deletion.list') }}" class="btn btn--secondary">Back to list</a>
    </div>
</div>
@endsection
