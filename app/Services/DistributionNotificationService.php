<?php

namespace App\Services;

use App\Models\Distribution;
use Illuminate\Support\Facades\Log;

class DistributionNotificationService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function sendCreatedNotification(Distribution $distribution)
    {
        try {
            // Load necessary relationships
            $distribution->load(['originDepartment', 'destinationDepartment', 'creator', 'type']);

            // Log the notification (in a real implementation, you would send emails)
            Log::info('Distribution created notification', [
                'distribution_id' => $distribution->id,
                'distribution_number' => $distribution->distribution_number,
                'creator' => $distribution->creator->name,
                'origin' => $distribution->originDepartment->name,
                'destination' => $distribution->destinationDepartment->name
            ]);

            // TODO: Implement actual email sending
            // Mail::to($destinationUsers)->send(new DistributionCreatedMail($distribution));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send distribution created notification', [
                'distribution_id' => $distribution->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendSentNotification(Distribution $distribution)
    {
        try {
            // Load necessary relationships
            $distribution->load(['originDepartment', 'destinationDepartment', 'creator', 'type']);

            // Log the notification
            Log::info('Distribution sent notification', [
                'distribution_id' => $distribution->id,
                'distribution_number' => $distribution->distribution_number,
                'sent_at' => $distribution->sent_at,
                'destination' => $distribution->destinationDepartment->name
            ]);

            // TODO: Implement actual email sending to destination department users
            // $destinationUsers = $distribution->destinationDepartment->users;
            // Mail::to($destinationUsers)->send(new DistributionSentMail($distribution));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send distribution sent notification', [
                'distribution_id' => $distribution->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendReceivedNotification(Distribution $distribution)
    {
        try {
            // Load necessary relationships
            $distribution->load(['originDepartment', 'destinationDepartment', 'creator', 'type']);

            // Log the notification
            Log::info('Distribution received notification', [
                'distribution_id' => $distribution->id,
                'distribution_number' => $distribution->distribution_number,
                'received_at' => $distribution->received_at,
                'origin' => $distribution->originDepartment->name
            ]);

            // TODO: Implement actual email sending to origin department users
            // $originUsers = $distribution->originDepartment->users;
            // Mail::to($originUsers)->send(new DistributionReceivedMail($distribution));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send distribution received notification', [
                'distribution_id' => $distribution->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendCompletedNotification(Distribution $distribution)
    {
        try {
            // Load necessary relationships
            $distribution->load(['originDepartment', 'destinationDepartment', 'creator', 'type']);

            // Log the notification
            Log::info('Distribution completed notification', [
                'distribution_id' => $distribution->id,
                'distribution_number' => $distribution->distribution_number,
                'completed_at' => now(),
                'origin' => $distribution->originDepartment->name,
                'destination' => $distribution->destinationDepartment->name
            ]);

            // TODO: Implement actual email sending to all involved users
            // $allUsers = $distribution->originDepartment->users->merge($distribution->destinationDepartment->users);
            // Mail::to($allUsers)->send(new DistributionCompletedMail($distribution));

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send distribution completed notification', [
                'distribution_id' => $distribution->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendVerificationReminderNotification(Distribution $distribution, string $type = 'sender')
    {
        try {
            // Load necessary relationships
            $distribution->load(['originDepartment', 'destinationDepartment', 'creator', 'type']);

            // Log the notification
            Log::info('Distribution verification reminder notification', [
                'distribution_id' => $distribution->id,
                'distribution_number' => $distribution->distribution_number,
                'reminder_type' => $type,
                'status' => $distribution->status
            ]);

            // TODO: Implement actual email sending based on reminder type
            if ($type === 'sender') {
                // Send to origin department users
            } else {
                // Send to destination department users
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send distribution verification reminder notification', [
                'distribution_id' => $distribution->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function sendDiscrepancyNotification(Distribution $distribution, array $discrepancyDetails)
    {
        try {
            // Load necessary relationships
            $distribution->load(['originDepartment', 'destinationDepartment', 'creator', 'type', 'receiverVerifier']);

            // Count discrepancies by type
            $missingCount = collect($discrepancyDetails)->where('status', 'missing')->count();
            $damagedCount = collect($discrepancyDetails)->where('status', 'damaged')->count();

            // Log the notification
            Log::info('Distribution discrepancy notification', [
                'distribution_id' => $distribution->id,
                'distribution_number' => $distribution->distribution_number,
                'verified_by' => $distribution->receiverVerifier?->name,
                'missing_documents' => $missingCount,
                'damaged_documents' => $damagedCount,
                'total_discrepancies' => count($discrepancyDetails),
                'receiver_notes' => $distribution->receiver_verification_notes
            ]);

            // Create detailed discrepancy summary for notification
            $discrepancySummary = [];
            foreach ($discrepancyDetails as $discrepancy) {
                $discrepancySummary[] = [
                    'type' => $discrepancy['status'],
                    'document' => $discrepancy['document_type'] . ' ID: ' . $discrepancy['document_id'],
                    'notes' => $discrepancy['notes'] ?? 'No additional notes'
                ];
            }

            // TODO: Implement actual email sending to origin department users and creator
            // $originUsers = $distribution->originDepartment->users;
            // $creator = $distribution->creator;
            // 
            // $notificationData = [
            //     'distribution_number' => $distribution->distribution_number,
            //     'receiver_department' => $distribution->destinationDepartment->name,
            //     'verified_by' => $distribution->receiverVerifier?->name,
            //     'verification_date' => $distribution->receiver_verified_at,
            //     'missing_count' => $missingCount,
            //     'damaged_count' => $damagedCount,
            //     'discrepancy_summary' => $discrepancySummary,
            //     'receiver_notes' => $distribution->receiver_verification_notes
            // ];
            //
            // // Send to origin department users
            // Mail::to($originUsers)->send(new DistributionDiscrepancyMail($notificationData));
            // 
            // // Send separate notification to creator if not in origin department
            // if (!$originUsers->contains('id', $creator->id)) {
            //     Mail::to($creator)->send(new DistributionDiscrepancyMail($notificationData));
            // }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send distribution discrepancy notification', [
                'distribution_id' => $distribution->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
