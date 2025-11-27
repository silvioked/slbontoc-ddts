<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentStatusLog;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScanController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Show the QR code scanner page
     */
    public function index(Request $request)
    {
        // If coming from QR code scan with document number
        if ($request->filled('document')) {
            $document = Document::where('document_number', $request->document)
                ->with(['creator', 'department', 'statusLogs.updatedBy'])
                ->first();

            if ($document) {
                return view('scan.index', compact('document'));
            } else {
                return view('scan.index')->withErrors(['error' => 'Document not found.']);
            }
        }

        return view('scan.index');
    }

    /**
     * Process QR code scan
     */
    public function scan(Request $request)
    {
        $validated = $request->validate([
            'document_number' => ['required', 'string'],
        ]);

        // Find document by document number
        $document = Document::where('document_number', $validated['document_number'])
            ->with(['creator', 'department', 'statusLogs.updatedBy'])
            ->first();

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found.',
            ], 404);
        }

        // Check if user has permission to view this document
        $user = Auth::user();
        
        // LGU Staff can scan and view all documents for tracking purposes
        // Department Heads can only view documents in their department
        if (!$user->hasRole('Administrator') && !$user->hasRole('LGU Staff')) {
            if ($user->hasRole('Department Head') && $document->department_id !== $user->department_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This document is not assigned to your department.',
                ], 403);
            }
        }

        $autoStatusUpdated = false;
        if (
            $user->can('update status') &&
            in_array($document->status, ['Forwarded', 'Pending'])
        ) {
            DB::beginTransaction();
            try {
                $oldStatus = $document->status;
                $document->update(['status' => 'Received']);

                DocumentStatusLog::createLog(
                    $document->id,
                    $user->id,
                    $oldStatus,
                    'Received',
                    'Automatically marked as Received via QR scan'
                );

                $this->notificationService->notifyStatusUpdate(
                    $document,
                    $document->created_by,
                    'Received'
                );

                DB::commit();
                $autoStatusUpdated = true;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Failed to auto-mark document as received via scan', [
                    'document_id' => $document->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Document found successfully.',
            'document' => [
                'id' => $document->id,
                'document_number' => $document->document_number,
                'title' => $document->title,
                'description' => $document->description,
                'document_type' => $document->document_type,
                'status' => $document->status,
                'is_priority' => $document->is_priority,
                'department' => $document->department ? $document->department->name : 'N/A',
                'created_by' => $document->creator ? $document->creator->name : 'Unknown',
                'created_at' => $document->created_at->format('M d, Y h:i A'),
            ],
            'redirect_url' => route('documents.show', $document),
            'auto_status_updated' => $autoStatusUpdated,
        ]);
    }

    /**
     * Quick status update via scan
     */
    public function quickUpdate(Request $request)
    {
        $validated = $request->validate([
            'document_id' => ['required', 'exists:documents,id'],
            'status' => ['required', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $document = Document::findOrFail($validated['document_id']);
        $oldStatus = $document->status;

        DB::beginTransaction();
        try {
            // Update document status
            $document->update([
                'status' => $validated['status'],
            ]);

            // Log the status change
            DocumentStatusLog::createLog(
                $document->id,
                Auth::id(),
                $oldStatus,
                $validated['status'],
                $validated['remarks'] ?? 'Updated via QR scan'
            );

            // Notify document creator
            $this->notificationService->notifyStatusUpdate(
                $document,
                $document->created_by,
                $validated['status']
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document status updated successfully!',
                'document' => [
                    'status' => $document->status,
                    'updated_at' => $document->updated_at->format('M d, Y h:i A'),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update document status.',
            ], 500);
        }
    }
}

