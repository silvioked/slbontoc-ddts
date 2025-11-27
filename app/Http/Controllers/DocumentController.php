<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Department;
use App\Models\DocumentStatusLog;
use App\Models\User;
use App\Services\QRCodeService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    protected $qrCodeService;
    protected $notificationService;

    public function __construct(QRCodeService $qrCodeService, NotificationService $notificationService)
    {
        $this->qrCodeService = $qrCodeService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of documents
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Document::with(['creator', 'department']);

        // Filter based on user role
        if ($user->hasRole('LGU Staff')) {
            // LGU Staff can see their own documents AND documents forwarded to their department
            $query->where(function($q) use ($user) {
                // Documents created by the user
                $q->where('created_by', $user->id);
            })->orWhere(function($q) use ($user) {
                // Documents forwarded to their department (even if not created by them)
                if ($user->department_id) {
                    $q->where('department_id', $user->department_id)
                      ->whereIn('status', ['Forwarded', 'Received', 'Under Review']);
                }
            });
        } elseif ($user->hasRole('Department Head')) {
            // Department Heads can only see documents in their department
            $query->where('department_id', $user->department_id);
        }
        // Administrators and Mayor can see all documents (no filter applied)

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->filled('status')) {
            $status = $request->status;
            
            // Special handling for "for_review" - show Received, Under Review, or Forwarded
            if ($status === 'for_review') {
                $query->whereIn('status', ['Received', 'Under Review', 'Forwarded']);
            }
            // Special handling for Rejected and Approved: include archived documents
            // because these documents are often archived but we still want to show them when filtering
            elseif ($status === 'Rejected' || $status === 'Approved') {
                // Include both active and archived documents with this status
                // Also check archived documents that had this status before archiving
                $query->where(function($q) use ($status) {
                    // Documents with current status matching
                    $q->where('status', $status);
                    
                    // OR archived documents that had this status before archiving
                    // We'll need to check status logs for archived documents
                    if ($status === 'Rejected' || $status === 'Approved') {
                        // Get document IDs from status logs where the old_status matches
                        // and the new_status is "Archived"
                        $archivedDocIds = DB::table('document_status_logs')
                            ->where('old_status', $status)
                            ->where('new_status', 'Archived')
                            ->distinct()
                            ->pluck('document_id');
                        
                        if ($archivedDocIds->isNotEmpty()) {
                            $q->orWhere(function($subQ) use ($archivedDocIds) {
                                $subQ->where('status', 'Archived')
                                     ->whereIn('id', $archivedDocIds);
                            });
                        }
                    }
                });
            } else {
                // For other statuses, just filter by current status
                $query->where('status', $status);
            }
        }

        // Apply department filter (for Administrators and Mayor)
        if ($request->filled('department') && ($user->hasRole('Administrator') || $user->hasRole('Mayor'))) {
            $query->where('department_id', $request->department);
        }

        // Apply priority filter
        if ($request->filled('priority')) {
            $query->where('is_priority', true);
        }

        // Exclude archived documents unless specifically requested OR filtering by Approved/Rejected status
        // (Approved and Rejected documents may be archived, but we want to show them when filtering)
        if (!$request->filled('show_archived') && 
            $request->status !== 'Approved' && 
            $request->status !== 'Rejected') {
            $query->active();
        }

        // Order by latest and paginate
        // Load status logs for archived documents to check pre-archive status
        $documents = $query->with('statusLogs')->latest()->paginate(15)->withQueryString();
        $departments = Department::active()->get();

        return view('documents.index', compact('documents', 'departments'));
    }

    /**
     * Show the form for creating a new document (All authenticated users)
     */
    public function create()
    {
        // All authenticated users can create documents
        $departments = Department::active()->get();
        $documentTypes = ['Certification', 'Disbursement Voucher', 'Leave Term', 'Obligation Request', 'Program of Work', 'SEF', 'Service Record', 'Others'];
        
        return view('documents.create', compact('departments', 'documentTypes'));
    }

    /**
     * Store a newly created document in storage (All authenticated users)
     */
    public function store(Request $request)
    {
        // All authenticated users can create documents

        // Validate input
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'document_type' => ['required', 'string'],
            'custom_document_type' => ['nullable', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'is_priority' => ['nullable', 'boolean'],
        ]);

        // If document_type is "Others", use the custom_document_type value
        if ($validated['document_type'] === 'Others') {
            if (empty($validated['custom_document_type'])) {
                return back()->withInput()
                    ->withErrors(['custom_document_type' => 'Please specify the document type.']);
            }
            $validated['document_type'] = trim($validated['custom_document_type']);
        }

        DB::beginTransaction();
        try {
            // Generate unique document number
            $documentNumber = Document::generateDocumentNumber();

            // Get department and creator info before creating document
            $department = Department::find($validated['department_id']);
            $creator = Auth::user();
            $creatorRole = $creator->roles->first()->name ?? 'User';

            // Create document (forwarded to department)
            $document = Document::create([
                'document_number' => $documentNumber,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'document_type' => $validated['document_type'],
                'department_id' => $validated['department_id'],
                'created_by' => Auth::id(),
                'status' => 'Forwarded',
                'is_priority' => $request->has('is_priority') ? true : false,
            ]);

            // Generate QR code
            $qrCodePath = $this->qrCodeService->generateDocumentQRCode(
                $document->document_number,
                $document->id
            );
            $document->update(['qr_code_path' => $qrCodePath]);

            // Create initial status log
            DocumentStatusLog::createLog(
                $document->id,
                Auth::id(),
                null,
                'Forwarded',
                "Document forwarded to {$department->name} by {$creatorRole}"
            );

            // Notify department users with specific details
            $priorityText = $document->is_priority ? ' [PRIORITY]' : '';
            $notificationType = $document->is_priority ? 'warning' : 'info';
            $actionText = $document->is_priority ? 'This is a PRIORITY document. Please acknowledge receipt and take immediate action.' : 'Please acknowledge receipt and take action.';
            
            $this->notificationService->notifyDepartmentUsers(
                $document->department_id,
                'New Document Forwarded - ' . $department->name . $priorityText,
                "New document '{$document->title}' ({$document->document_number}) has been forwarded to {$department->name} by {$creator->name} ({$creatorRole}). {$actionText}",
                $notificationType,
                $document->id
            );

            DB::commit();

            return redirect()->route('documents.show', $document)
                ->with('success', 'Document created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->withErrors(['error' => 'Failed to create document: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified document
     */
    public function show(Document $document)
    {
        // Check if user has permission to view this document
        $user = Auth::user();
        
        // LGU Staff, Administrators, and Mayor can view all documents (needed for QR scanning and tracking)
        if (!$user->hasRole('Administrator') && !$user->hasRole('Mayor') && !$user->hasRole('LGU Staff')) {
            // Department Head can view documents in their department OR documents they've handled/forwarded
            if ($user->hasRole('Department Head')) {
                $hasHandled = DocumentStatusLog::where('document_id', $document->id)
                    ->where('updated_by', $user->id)
                    ->exists();
                    
                if ($document->department_id !== $user->department_id && !$hasHandled) {
                    abort(403, 'Unauthorized access to this document.');
                }
            }
        }

        $document->load(['creator', 'department', 'currentHandler', 'statusLogs.updatedBy']);
        
        return view('documents.show', compact('document'));
    }

    /**
     * Show the form for editing the specified document
     */
    public function edit(Document $document)
    {
        $this->authorize('manage-documents');
        
        // Only creator, admin, or mayor can edit
        if (!Auth::user()->hasAnyRole(['Administrator', 'Mayor']) && $document->created_by !== Auth::id()) {
            abort(403, 'Unauthorized to edit this document.');
        }

        $departments = Department::active()->get();
        $documentTypes = ['Certification', 'Disbursement Voucher', 'Leave Term', 'Obligation Request', 'Program of Work', 'SEF', 'Service Record', 'Others'];
        
        return view('documents.edit', compact('document', 'departments', 'documentTypes'));
    }

    /**
     * Update the specified document in storage
     */
    public function update(Request $request, Document $document)
    {
        $this->authorize('manage-documents');

        // Only creator, admin, or mayor can update
        if (!Auth::user()->hasAnyRole(['Administrator', 'Mayor']) && $document->created_by !== Auth::id()) {
            abort(403, 'Unauthorized to update this document.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'document_type' => ['required', 'string'],
            'custom_document_type' => ['nullable', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'forward_to_department' => ['nullable', 'exists:departments,id'],
        ]);

        // If document_type is "Others", use the custom_document_type value
        if ($validated['document_type'] === 'Others') {
            if (empty($validated['custom_document_type'])) {
                return back()->withInput()
                    ->withErrors(['custom_document_type' => 'Please specify the document type.']);
            }
            $validated['document_type'] = trim($validated['custom_document_type']);
        }

        DB::beginTransaction();
        try {
            // Check if document is being forwarded to another department
            if (!empty($validated['forward_to_department']) && 
                $validated['forward_to_department'] != $document->department_id) {
                
                $oldDepartment = $document->department;
                $newDepartmentId = $validated['forward_to_department'];
                $newDepartment = Department::findOrFail($newDepartmentId);
                
                // Update to forwarded department
                $validated['department_id'] = $newDepartmentId;
                $document->update($validated);
                
                // Create status log for forwarding
                DocumentStatusLog::createLog(
                    $document->id,
                    Auth::id(),
                    $document->status,
                    'Forwarded',
                    "Forwarded from {$oldDepartment->name} to {$newDepartment->name}"
                );
                
                // Update status back to indicate forwarding
                $document->update(['status' => 'Under Review']);
                
                // Get current user info
                $forwarder = Auth::user();
                $forwarderRole = $forwarder->roles->first()->name ?? 'User';
                
                // Notify ALL users in the new department with specific details
                $this->notificationService->notifyDepartmentUsers(
                    $newDepartmentId,
                    'Document Received - Forwarded to ' . $newDepartment->name,
                    "Document '{$document->title}' ({$document->document_number}) was forwarded to {$newDepartment->name} by {$forwarder->name} ({$forwarderRole}) from {$oldDepartment->name}. Please review and take action.",
                    'info',
                    $document->id
                );
                
                DB::commit();
                return redirect()->route('documents.show', $document)
                    ->with('success', "Document updated and forwarded to {$newDepartment->name} successfully!");
            }
            
            // Normal update without forwarding
            $document->update($validated);
            
            DB::commit();
            return redirect()->route('documents.show', $document)
                ->with('success', 'Document updated successfully!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update document: ' . $e->getMessage()]);
        }
    }

    /**
     * Update document status
     */
    public function updateStatus(Request $request, Document $document)
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
            'remarks' => ['nullable', 'string'],
            'forward_to_department' => ['nullable', 'exists:departments,id'],
        ]);

        $oldStatus = $document->status;
        $oldDepartment = $document->department;

        DB::beginTransaction();
        try {
            // Check if document is being forwarded to another department
            if (!empty($validated['forward_to_department']) && 
                $validated['forward_to_department'] != $document->department_id) {
                
                $newDepartmentId = $validated['forward_to_department'];
                $newDepartment = Department::findOrFail($newDepartmentId);
                
                // Get current user info
                $forwarder = Auth::user();
                $forwarderRole = $forwarder->roles->first()->name ?? 'User';
                
                // Check if both status change and forwarding are happening
                $isStatusChange = $validated['status'] !== 'Forwarded' && $validated['status'] !== $oldStatus;
                
                if ($isStatusChange) {
                    // Create a single combined log entry for both actions
                    $combinedRemarks = "Approved and forwarded from {$oldDepartment->name} to {$newDepartment->name}";
                    if (!empty($validated['remarks'])) {
                        $combinedRemarks .= ". " . $validated['remarks'];
                    }
                    
                    DocumentStatusLog::createLog(
                        $document->id,
                        Auth::id(),
                        $oldStatus,
                        $validated['status'], // Status will be "Approved"
                        $combinedRemarks
                    );
                    
                    // Notify document creator about status change
                    $this->notificationService->notifyStatusUpdate(
                        $document,
                        $document->created_by,
                        $validated['status']
                    );
                } else {
                    // Only forwarding, no status change
                    $forwardRemarks = "Forwarded from {$oldDepartment->name} to {$newDepartment->name}";
                    if (!empty($validated['remarks'])) {
                        $forwardRemarks .= ". " . $validated['remarks'];
                    }
                    
                    DocumentStatusLog::createLog(
                        $document->id,
                        Auth::id(),
                        $oldStatus,
                        'Forwarded',
                        $forwardRemarks
                    );
                }
                
                // Update department and status to Forwarded
                $document->update([
                    'status' => 'Forwarded',
                    'department_id' => $newDepartmentId,
                ]);
                
                // Notify document creator about forwarding
                $this->notificationService->notifyStatusUpdate(
                    $document,
                    $document->created_by,
                    'Forwarded'
                );
                
                // Notify ALL users in the new department with specific details
                $statusText = ($validated['status'] !== 'Forwarded' && $validated['status'] !== $oldStatus) 
                    ? "Status changed to {$validated['status']} and " 
                    : "";
                    
                $this->notificationService->notifyDepartmentUsers(
                    $newDepartmentId,
                    'Document Received - Forwarded to ' . $newDepartment->name,
                    "Document '{$document->title}' ({$document->document_number}) was {$statusText}forwarded to {$newDepartment->name} by {$forwarder->name} ({$forwarderRole}) from {$oldDepartment->name}. Please review and take action.",
                    'info',
                    $document->id
                );
                
                DB::commit();
                
                $message = ($validated['status'] !== 'Forwarded' && $validated['status'] !== $oldStatus)
                    ? "Document status changed to {$validated['status']} and forwarded to {$newDepartment->name} successfully!"
                    : "Document forwarded to {$newDepartment->name} successfully!";
                    
                return back()->with('success', $message);
            }
            
            // Normal status update without forwarding
            $updates = [
                'status' => $validated['status'],
            ];

            // If status is Approved and NOT forwarding, auto-archive (document is finished)
            if ($validated['status'] === 'Approved') {
                $updates['archived_at'] = now();
            }

            $document->update($updates);

            // Log the status change
            DocumentStatusLog::createLog(
                $document->id,
                Auth::id(),
                $oldStatus,
                $validated['status'],
                $validated['remarks'] ?? null
            );

            // Notify document creator
            $this->notificationService->notifyStatusUpdate(
                $document,
                $document->created_by,
                $validated['status']
            );

            DB::commit();

            $message = $validated['status'] === 'Approved' 
                ? 'Document approved and archived successfully!' 
                : 'Document status updated successfully!';

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update status: ' . $e->getMessage()]);
        }
    }

    /**
     * Set document as priority
     */
    public function setPriority(Document $document)
    {
        $this->authorize('set-priority');

        $document->update(['is_priority' => !$document->is_priority]);

        if ($document->is_priority) {
            // Notify all relevant users
            $this->notificationService->notifyPriorityDocument(
                $document,
                $document->created_by
            );

            // Notify department users
            $this->notificationService->notifyDepartmentUsers(
                $document->department_id,
                'Priority Document Alert',
                "Document '{$document->title}' has been marked as PRIORITY!",
                'warning',
                $document->id
            );
        }

        $message = $document->is_priority ? 'Document marked as priority.' : 'Priority flag removed.';
        return back()->with('success', $message);
    }

    /**
     * Archive document
     */
    public function archive(Document $document)
    {
        $this->authorize('archive-documents');

        DB::beginTransaction();
        try {
            // Save the old status BEFORE updating
            $oldStatus = $document->status;
            
            $document->update([
                'status' => 'Archived',
                'archived_at' => now(),
            ]);

            // Log the archiving with the correct old status
            DocumentStatusLog::createLog(
                $document->id,
                Auth::id(),
                $oldStatus,
                'Archived',
                'Document archived'
            );

            // Notify document creator
            $this->notificationService->notifyDocumentArchived(
                $document,
                $document->created_by
            );

            DB::commit();

            return back()->with('success', 'Document archived successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to archive document: ' . $e->getMessage()]);
        }
    }

    /**
     * Approve/Verify a document (Admin only)
     */
    public function approve(Document $document)
    {
        if (!Auth::user()->hasRole('Administrator')) {
            abort(403, 'Only administrators can verify documents.');
        }

        if ($document->status !== 'Pending Verification') {
            return back()->withErrors(['error' => 'This document is not pending verification.']);
        }

        DB::beginTransaction();
        try {
            // Update status to Approved and auto-archive (document is finished)
            $document->update([
                'status' => 'Approved',
                'archived_at' => now()
            ]);

            // Log the approval
            DocumentStatusLog::createLog(
                $document->id,
                Auth::id(),
                'Pending Verification',
                'Approved',
                'Document approved and archived by administrator'
            );

            // Notify document creator
            $this->notificationService->notifyStatusUpdate(
                $document,
                $document->created_by,
                'Approved'
            );

            DB::commit();

            return back()->with('success', 'Document approved and archived successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to approve document: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject a document (Admin only)
     */
    public function rejectDocument(Request $request, Document $document)
    {
        if (!Auth::user()->hasRole('Administrator')) {
            abort(403, 'Only administrators can reject documents.');
        }

        if ($document->status !== 'Pending Verification') {
            return back()->withErrors(['error' => 'This document is not pending verification.']);
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        DB::beginTransaction();
        try {
            // Update status to Rejected
            $document->update(['status' => 'Rejected']);

            // Log the rejection
            DocumentStatusLog::createLog(
                $document->id,
                Auth::id(),
                'Pending Verification',
                'Rejected',
                'Rejected by administrator: ' . $validated['rejection_reason']
            );

            // Notify document creator
            Notification::createNotification(
                $document->created_by,
                'Document Rejected',
                "Your document '{$document->title}' was rejected. Reason: " . $validated['rejection_reason'],
                'danger',
                $document->id
            );

            DB::commit();

            return back()->with('success', 'Document rejected and creator notified.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to reject document: ' . $e->getMessage()]);
        }
    }

    /**
     * Print QR code
     */
    public function printQRCode(Document $document)
    {
        $qrCodePath = $this->qrCodeService->generatePrintableQRCode($document);
        
        return view('documents.print-qr', compact('document', 'qrCodePath'));
    }

    /**
     * Display document timeline
     */
    public function timeline(Document $document)
    {
        // Check if user has access to this document
        $user = Auth::user();
        
        // LGU Staff, Administrators, and Mayor can view all document timelines (needed for tracking)
        // Department Heads can only view documents in their department
        if (!$user->hasRole('Administrator') && !$user->hasRole('Mayor') && !$user->hasRole('LGU Staff')) {
            if ($user->hasRole('Department Head') && $document->department_id != $user->department_id) {
                abort(403, 'Unauthorized to view this document timeline.');
            }
        }
        
        return view('documents.timeline', compact('document'));
    }

    /**
     * Delete the specified document
     */
    public function destroy(Document $document)
    {
        // Only administrators can delete
        if (!Auth::user()->hasRole('Administrator')) {
            abort(403, 'Unauthorized to delete documents.');
        }

        // Delete QR code file
        if ($document->qr_code_path) {
            $this->qrCodeService->deleteQRCode($document->qr_code_path);
        }

        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', 'Document deleted successfully!');
    }
}

