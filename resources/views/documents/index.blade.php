@extends('layouts.app')

@section('title', 'Documents')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="bi bi-file-earmark-text"></i> Documents</h2>
        <a href="{{ route('documents.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create New Document
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="bi bi-funnel"></i> Filters
                @if(request()->hasAny(['search', 'status', 'department', 'priority']))
                <span class="badge bg-info ms-2">Active</span>
                @endif
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('documents.index') }}" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Search documents..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Pending" {{ request('status') == 'Pending' ? 'selected' : '' }}>Pending</option>
                            <option value="Received" {{ request('status') == 'Received' ? 'selected' : '' }}>Received</option>
                            <option value="Under Review" {{ request('status') == 'Under Review' ? 'selected' : '' }}>Under Review</option>
                            <option value="Forwarded" {{ request('status') == 'Forwarded' ? 'selected' : '' }}>Forwarded</option>
                            <option value="Approved" {{ request('status') == 'Approved' ? 'selected' : '' }}>Approved</option>
                            <option value="Rejected" {{ request('status') == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    @role('Administrator|Mayor')
                    <div class="col-md-2">
                        <label class="form-label small">Department</label>
                        <select name="department" class="form-select">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endrole
                    <div class="col-md-2">
                        <label class="form-label small">Priority</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="priority" id="priority" value="1" {{ request('priority') ? 'checked' : '' }}>
                            <label class="form-check-label" for="priority">
                                Priority Only
                            </label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-info">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <a href="{{ route('documents.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Clear
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Summary -->
    @if(request()->hasAny(['search', 'status', 'department', 'priority']))
    <div class="alert alert-info">
        <strong><i class="bi bi-info-circle"></i> Showing filtered results:</strong>
        @if(request('search'))
            <span class="badge bg-primary">Search: "{{ request('search') }}"</span>
        @endif
        @if(request('status'))
            <span class="badge bg-primary">Status: {{ request('status') === 'for_review' ? 'For Review' : request('status') }}</span>
        @endif
        @if(request('department'))
            @php
                $dept = $departments->find(request('department'));
            @endphp
            @if($dept)
                <span class="badge bg-primary">Department: {{ $dept->name }}</span>
            @endif
        @endif
        @if(request('priority'))
            <span class="badge bg-warning">Priority Only</span>
        @endif
        <span class="ms-2">{{ $documents->total() }} {{ Str::plural('document', $documents->total()) }} found</span>
    </div>
    @endif

    <!-- Documents Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-table"></i> Documents List</h6>
            <span class="badge bg-secondary">Total: {{ $documents->total() }}</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 12%; white-space: nowrap;">DOCUMENT #</th>
                            <th style="width: 18%; white-space: nowrap;">TITLE</th>
                            <th class="text-center" style="width: 8%; white-space: nowrap;">TYPE</th>
                            <th class="text-center" style="width: 10%; white-space: nowrap;">DEPARTMENT</th>
                            <th class="text-center" style="width: 10%; white-space: nowrap;">STATUS</th>
                            <th class="text-center" style="width: 10%; white-space: nowrap;">CREATED BY</th>
                            <th class="text-center" style="width: 12%; white-space: nowrap;">CURRENT HANDLER</th>
                            <th class="text-center" style="width: 8%; white-space: nowrap;">DATE</th>
                            <th class="text-center" style="width: 12%; white-space: nowrap;">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $document)
                        <tr class="{{ $document->is_priority ? 'table-warning' : '' }}">
                            <td class="text-center">
                                <strong>{{ $document->document_number }}</strong>
                                @if($document->is_priority)
                                <br><span class="badge badge-priority">PRIORITY</span>
                                @endif
                            </td>
                            <td>
                                {{ Str::limit($document->title, 50) }}
                                @php
                                    // For archived documents, check pre-archive status for icon display
                                    $iconStatus = $document->status;
                                    if ($document->status === 'Archived') {
                                        $preArchiveStatus = $document->getPreArchiveStatus();
                                        if ($preArchiveStatus && ($preArchiveStatus === 'Rejected' || $preArchiveStatus === 'Approved')) {
                                            $iconStatus = $preArchiveStatus;
                                        }
                                    }
                                @endphp
                                @if($iconStatus == 'Approved')
                                <i class="bi bi-check-circle-fill text-success" title="Approved" style="font-size: 0.9rem;"></i>
                                @endif
                                @if($iconStatus == 'Rejected')
                                <i class="bi bi-x-circle-fill text-danger" title="Rejected" style="font-size: 0.9rem;"></i>
                                @endif
                            </td>
                            <td class="text-center"><span class="badge bg-secondary">{{ $document->document_type }}</span></td>
                            <td class="text-center">{{ $document->department ? $document->department->code : 'N/A' }}</td>
                            <td class="text-center">
                                @php
                                    // For archived documents, get the pre-archive status for display
                                    $displayStatus = $document->status;
                                    $preArchiveStatus = null;
                                    if ($document->status === 'Archived') {
                                        $preArchiveStatus = $document->getPreArchiveStatus();
                                        if ($preArchiveStatus && ($preArchiveStatus === 'Rejected' || $preArchiveStatus === 'Approved')) {
                                            $displayStatus = $preArchiveStatus;
                                        }
                                    }
                                @endphp
                                <span class="badge bg-{{ $displayStatus == 'Approved' ? 'success' : ($displayStatus == 'Received' ? 'success' : ($displayStatus == 'Pending' ? 'warning' : ($displayStatus == 'Rejected' ? 'danger' : 'info'))) }}">
                                    {{ $displayStatus }}
                                </span>
                                @if($document->status === 'Archived' && $preArchiveStatus && $preArchiveStatus !== 'Archived')
                                <br><small class="text-muted">(Archived)</small>
                                @endif
                            </td>
                            <td class="text-center">{{ $document->creator ? $document->creator->name : 'Unknown' }}</td>
                            <td class="text-center">
                                @if($document->currentHandler)
                                {{ $document->currentHandler->name }}
                                @else
                                <span class="text-muted">Unassigned</span>
                                @endif
                            </td>
                            <td class="text-center"><small>{{ $document->created_at->format('M d, Y') }}</small></td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ route('documents.show', $document) }}" class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('manage-documents')
                                    @if($document->created_by == auth()->id() || auth()->user()->hasAnyRole(['Administrator', 'Mayor']))
                                    <a href="{{ route('documents.edit', $document) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endif
                                    @endcan
                                    <a href="{{ route('documents.print-qr', $document) }}" class="btn btn-sm btn-secondary" title="Print QR" target="_blank">
                                        <i class="bi bi-qr-code"></i>
                                    </a>
                                    @role('Administrator|Department Head')
                                    @if($document->status != 'Archived')
                                    <form method="POST" action="{{ route('documents.archive', $document) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-dark" title="Archive" onclick="return confirm('Archive this document?')">
                                            <i class="bi bi-archive"></i>
                                        </button>
                                    </form>
                                    @endif
                                    @endrole
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mb-0">No documents found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $documents->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

