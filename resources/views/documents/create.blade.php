@extends('layouts.app')

@section('title', 'Create Document')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('documents.index') }}">Documents</a></li>
                <li class="breadcrumb-item active">Create Document</li>
            </ol>
        </nav>
        <h2 class="fw-bold"><i class="bi bi-file-plus"></i> Create New Document</h2>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Document Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('documents.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="title" class="form-label">Document Title <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('title') is-invalid @enderror" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title') }}" 
                                   required 
                                   autofocus>
                            @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="document_type" class="form-label">Document Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('document_type') is-invalid @enderror" 
                                    id="document_type" 
                                    name="document_type" 
                                    required>
                                @foreach($documentTypes as $type)
                                <option value="{{ $type }}" {{ old('document_type') == $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                                @endforeach
                            </select>
                            @error('document_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div id="custom_document_type_container" class="mt-2" style="display: none;">
                                <label for="custom_document_type" class="form-label">Specify Document Type <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('custom_document_type') is-invalid @enderror" 
                                       id="custom_document_type" 
                                       name="custom_document_type" 
                                       value="{{ old('custom_document_type') }}" 
                                       placeholder="Enter custom document type">
                                @error('custom_document_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="department_id" class="form-label">Forwarded to Department <span class="text-danger">*</span></label>
                            <select class="form-select @error('department_id') is-invalid @enderror" 
                                    id="department_id" 
                                    name="department_id" 
                                    required>
                                @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                    {{ $department->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="5">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Provide additional details about this document</small>
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       role="switch" 
                                       id="is_priority" 
                                       name="is_priority" 
                                       value="1"
                                       {{ old('is_priority') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_priority">
                                    <i class="bi bi-exclamation-triangle-fill text-warning"></i> 
                                    <strong>Mark as PRIORITY</strong>
                                </label>
                            </div>
                            <small class="form-text text-muted">Priority documents will be highlighted and require immediate attention</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Create Document
                            </button>
                            <a href="{{ route('documents.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>What happens next?</strong></p>
                    <ol class="small">
                        <li>A unique document number will be generated</li>
                        <li>A QR code will be created for tracking</li>
                        <li>The assigned department will be notified</li>
                        <li>You can print the QR code to attach to the physical document</li>
                    </ol>
                    
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-lightbulb"></i> <strong>Tip:</strong> Make sure to print and attach the QR code to the physical document for easy tracking!
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const documentTypeSelect = document.getElementById('document_type');
    const customDocumentTypeContainer = document.getElementById('custom_document_type_container');
    const customDocumentTypeInput = document.getElementById('custom_document_type');
    
    function toggleCustomDocumentType() {
        if (documentTypeSelect.value === 'Others') {
            customDocumentTypeContainer.style.display = 'block';
            customDocumentTypeInput.setAttribute('required', 'required');
        } else {
            customDocumentTypeContainer.style.display = 'none';
            customDocumentTypeInput.removeAttribute('required');
            customDocumentTypeInput.value = '';
        }
    }
    
    // Check on page load (for old values)
    toggleCustomDocumentType();
    
    // Listen for changes
    documentTypeSelect.addEventListener('change', toggleCustomDocumentType);
    
    // Handle form submission - set document_type to custom value if Others is selected
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        if (documentTypeSelect.value === 'Others') {
            if (!customDocumentTypeInput.value.trim()) {
                e.preventDefault();
                customDocumentTypeInput.focus();
                return false;
            }
            // Create a hidden input with the custom value as document_type
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'document_type';
            hiddenInput.value = customDocumentTypeInput.value.trim();
            form.appendChild(hiddenInput);
            // Remove the original select from submission
            documentTypeSelect.disabled = true;
        }
    });
});
</script>
@endsection

