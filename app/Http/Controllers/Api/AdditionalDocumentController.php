<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdditionalDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdditionalDocumentController extends Controller
{
    /**
     * Display a listing of additional documents filtered by user's department location
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

            $query = AdditionalDocument::with(['type'])
                ->where('cur_loc', $userDepartment->location_code);

            // Apply filters
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('document_number', 'like', "%{$search}%")
                        ->orWhere('po_no', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%");
                });
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('type_id')) {
                $query->where('type_id', $request->input('type_id'));
            }

            if ($request->has('date_from')) {
                $query->whereDate('document_date', '>=', $request->input('date_from'));
            }

            if ($request->has('date_to')) {
                $query->whereDate('document_date', '<=', $request->input('date_to'));
            }

            $perPage = $request->input('per_page', 15);
            $documents = $query->latest('document_date')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $documents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve additional documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified additional document
     */
    public function show(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $userDepartment = $user->department;

            $document = AdditionalDocument::with(['type'])
                ->where('id', $id)
                ->where('cur_loc', $userDepartment->location_code)
                ->first();

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Additional document not found or not in your location'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $document
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve additional document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get additional documents available for distribution
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

            $query = AdditionalDocument::with(['type'])
                ->where('cur_loc', $userDepartment->location_code)
                ->where('status', '!=', 'cancelled'); // Exclude cancelled documents

            // Apply search filter
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('document_number', 'like', "%{$search}%")
                        ->orWhere('po_no', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%");
                });
            }

            if ($request->has('type_id')) {
                $query->where('type_id', $request->input('type_id'));
            }

            $documents = $query->latest('document_date')->get();

            return response()->json([
                'success' => true,
                'data' => $documents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve additional documents for distribution',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
