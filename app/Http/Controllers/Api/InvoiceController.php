<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices filtered by user's department location
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $userDepartment = $user->department;

            if (!$userDepartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'User department not found'
                ], 400);
            }

            $query = Invoice::with(['supplier', 'type'])
                ->where('cur_loc', $userDepartment->location_code);

            // Apply filters
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('faktur_no', 'like', "%{$search}%")
                        ->orWhere('po_no', 'like', "%{$search}%");
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('date_from')) {
                $query->whereDate('invoice_date', '>=', $request->input('date_from'));
            }

            if ($request->has('date_to')) {
                $query->whereDate('invoice_date', '<=', $request->input('date_to'));
            }

            $perPage = $request->input('per_page', 15);
            $invoices = $query->latest('invoice_date')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $invoices
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoices',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified invoice
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $userDepartment = $user->department;

            $invoice = Invoice::with(['supplier', 'type', 'additionalDocuments'])
                ->where('id', $id)
                ->where('cur_loc', $userDepartment->location_code)
                ->first();

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found or not in your location'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get invoices available for distribution (with attached documents info)
     */
    public function forDistribution(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $userDepartment = $user->department;

            if (!$userDepartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'User department not found'
                ], 400);
            }

            $query = Invoice::with(['supplier', 'type', 'additionalDocuments'])
                ->where('cur_loc', $userDepartment->location_code)
                ->where('status', '!=', 'cancelled'); // Exclude cancelled invoices

            // Apply search filter
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('faktur_no', 'like', "%{$search}%")
                        ->orWhere('po_no', 'like', "%{$search}%");
                });
            }

            $invoices = $query->latest('invoice_date')->get();

            // Add additional info for distribution
            $invoices->each(function ($invoice) use ($userDepartment) {
                $attachedDocs = $invoice->additionalDocuments;
                $invoice->attached_documents_count = $attachedDocs->count();
                $invoice->attached_documents_in_location = $attachedDocs->where('cur_loc', $userDepartment->location_code)->count();
                $invoice->has_location_mismatch = $invoice->attached_documents_count > $invoice->attached_documents_in_location;
            });

            return response()->json([
                'success' => true,
                'data' => $invoices
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoices for distribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
