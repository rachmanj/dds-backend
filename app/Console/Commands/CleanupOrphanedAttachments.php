<?php

namespace App\Console\Commands;

use App\Models\InvoiceAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanedAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attachments:cleanup 
                           {--dry-run : Show what would be deleted without actually deleting}
                           {--days=30 : Files older than this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up orphaned attachment files that have no database records';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $days = (int) $this->option('days');
        $disk = Storage::disk('attachments');

        $this->info("Starting attachment cleanup...");
        $this->info("Mode: " . ($dryRun ? 'DRY RUN' : 'ACTUAL DELETION'));
        $this->info("Looking for files older than {$days} days");

        // Get all invoice directories
        $invoiceDirectories = $disk->directories('invoices');
        $totalFiles = 0;
        $orphanedFiles = 0;
        $deletedFiles = 0;
        $freedSpace = 0;

        foreach ($invoiceDirectories as $invoiceDir) {
            // Extract invoice ID from directory path
            $invoiceId = basename($invoiceDir);

            if (!is_numeric($invoiceId)) {
                continue;
            }

            $attachmentDir = $invoiceDir . '/attachments';

            if (!$disk->exists($attachmentDir)) {
                continue;
            }

            $files = $disk->files($attachmentDir);

            foreach ($files as $filePath) {
                $totalFiles++;

                // Check if file is older than specified days
                $fileTime = $disk->lastModified($filePath);
                $daysOld = (time() - $fileTime) / (60 * 60 * 24);

                if ($daysOld < $days) {
                    continue;
                }

                // Check if there's a corresponding database record
                $attachment = InvoiceAttachment::where('file_path', $filePath)->first();

                if (!$attachment) {
                    $orphanedFiles++;
                    $fileSize = $disk->size($filePath);
                    $freedSpace += $fileSize;

                    $this->warn("Orphaned file: {$filePath} (" . $this->formatFileSize($fileSize) . ")");

                    if (!$dryRun) {
                        $disk->delete($filePath);
                        $deletedFiles++;
                        $this->info("  → Deleted");
                    }
                }
            }

            // Clean up empty directories
            if (!$dryRun && $disk->exists($attachmentDir)) {
                $remainingFiles = $disk->files($attachmentDir);
                if (empty($remainingFiles)) {
                    $disk->deleteDirectory($attachmentDir);
                    $this->info("Cleaned up empty directory: {$attachmentDir}");

                    // Also clean up parent invoice directory if empty
                    $invoiceDirFiles = $disk->allFiles($invoiceDir);
                    if (empty($invoiceDirFiles)) {
                        $disk->deleteDirectory($invoiceDir);
                        $this->info("Cleaned up empty invoice directory: {$invoiceDir}");
                    }
                }
            }
        }

        // Summary
        $this->newLine();
        $this->info("=== Cleanup Summary ===");
        $this->info("Total files scanned: {$totalFiles}");
        $this->info("Orphaned files found: {$orphanedFiles}");

        if ($dryRun) {
            $this->info("Files that would be deleted: {$orphanedFiles}");
            $this->info("Space that would be freed: " . $this->formatFileSize($freedSpace));
            $this->comment("Run without --dry-run to actually delete the files");
        } else {
            $this->info("Files deleted: {$deletedFiles}");
            $this->info("Space freed: " . $this->formatFileSize($freedSpace));
        }

        return Command::SUCCESS;
    }

    /**
     * Format file size to human readable format
     */
    private function formatFileSize(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
}
