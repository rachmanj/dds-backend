<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Invoice;
use App\Models\InvoiceAttachment;
use App\Models\FileWatermark;
use App\Models\FileProcessingJob;
use App\Jobs\ProcessFileWatermark;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FileManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->invoice = Invoice::factory()->create(['created_by' => $this->user->id]);
        Sanctum::actingAs($this->user);

        Storage::fake('local');
    }

    public function test_user_can_upload_file_with_watermarking()
    {
        Queue::fake();

        $file = UploadedFile::fake()->create('test-document.pdf', 1000, 'application/pdf');

        $response = $this->postJson("/api/file-management/invoices/{$this->invoice->id}/upload", [
            'file' => $file,
            'watermark_enabled' => true
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'id',
                    'file_name',
                    'file_path',
                    'file_size',
                    'mime_type',
                    'processing_status'
                ]
            ]);

        // Verify file was stored
        Storage::assertExists($response->json('data.file_path'));

        // Verify watermarking job was queued
        Queue::assertPushed(ProcessFileWatermark::class);
    }

    public function test_file_upload_without_watermarking()
    {
        Queue::fake();

        $file = UploadedFile::fake()->create('test-document.pdf', 1000, 'application/pdf');

        $response = $this->postJson("/api/file-management/invoices/{$this->invoice->id}/upload", [
            'file' => $file,
            'watermark_enabled' => false
        ]);

        $response->assertStatus(200);

        // Verify watermarking job was NOT queued
        Queue::assertNotPushed(ProcessFileWatermark::class);
    }

    public function test_file_upload_validation()
    {
        // Test without file
        $response = $this->postJson("/api/file-management/invoices/{$this->invoice->id}/upload", []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        // Test with invalid file type
        $file = UploadedFile::fake()->create('malicious.exe', 1000, 'application/x-executable');
        $response = $this->postJson("/api/file-management/invoices/{$this->invoice->id}/upload", [
            'file' => $file
        ]);
        $response->assertStatus(422);
    }

    public function test_user_can_get_processing_status()
    {
        $attachment = InvoiceAttachment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'processing_status' => 'processing'
        ]);

        $response = $this->getJson("/api/file-management/attachments/{$attachment->id}/processing-status");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'processing_status' => 'processing'
                ]
            ]);
    }

    public function test_user_can_apply_watermark_manually()
    {
        Queue::fake();

        $attachment = InvoiceAttachment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'processing_status' => 'completed'
        ]);

        $response = $this->postJson("/api/file-management/attachments/{$attachment->id}/watermark", [
            'watermark_text' => 'CONFIDENTIAL'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Watermarking job queued successfully'
            ]);

        Queue::assertPushed(ProcessFileWatermark::class);
    }

    public function test_user_can_download_original_file()
    {
        $attachment = InvoiceAttachment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'file_path' => 'test-file.pdf'
        ]);

        // Create fake file
        Storage::put($attachment->file_path, 'fake file content');

        $response = $this->getJson("/api/file-management/invoices/{$this->invoice->id}/attachments/{$attachment->id}/download?watermarked=false");

        $response->assertStatus(200);
    }

    public function test_user_can_download_watermarked_file()
    {
        $attachment = InvoiceAttachment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'file_path' => 'test-file.pdf'
        ]);

        $watermark = FileWatermark::factory()->create([
            'original_file_id' => $attachment->id,
            'watermarked_path' => 'watermarked/test-file.pdf'
        ]);

        // Create fake files
        Storage::put($attachment->file_path, 'original content');
        Storage::put($watermark->watermarked_path, 'watermarked content');

        $response = $this->getJson("/api/file-management/invoices/{$this->invoice->id}/attachments/{$attachment->id}/download?watermarked=true");

        $response->assertStatus(200);
    }

    public function test_user_can_get_watermark_details()
    {
        $attachment = InvoiceAttachment::factory()->create([
            'invoice_id' => $this->invoice->id
        ]);

        $watermark = FileWatermark::factory()->create([
            'original_file_id' => $attachment->id,
            'watermark_text' => 'CONFIDENTIAL',
            'watermarked_path' => 'watermarked/test-file.pdf'
        ]);

        $response = $this->getJson("/api/file-management/attachments/{$attachment->id}/watermark");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'watermark_text' => 'CONFIDENTIAL',
                    'watermarked_path' => 'watermarked/test-file.pdf'
                ]
            ]);
    }

    public function test_user_can_remove_watermark()
    {
        $attachment = InvoiceAttachment::factory()->create([
            'invoice_id' => $this->invoice->id
        ]);

        $watermark = FileWatermark::factory()->create([
            'original_file_id' => $attachment->id,
            'watermarked_path' => 'watermarked/test-file.pdf'
        ]);

        Storage::put($watermark->watermarked_path, 'watermarked content');

        $response = $this->deleteJson("/api/file-management/attachments/{$attachment->id}/watermark");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Watermark removed successfully'
            ]);

        // Verify watermark record was deleted
        $this->assertDatabaseMissing('file_watermarks', [
            'id' => $watermark->id
        ]);

        // Verify watermarked file was deleted
        Storage::assertMissing($watermark->watermarked_path);
    }

    public function test_user_can_get_processing_jobs()
    {
        FileProcessingJob::factory()->count(3)->create([
            'job_type' => 'watermark',
            'status' => 'pending'
        ]);

        $response = $this->getJson('/api/file-management/processing-jobs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'file_id',
                        'job_type',
                        'status',
                        'attempts',
                        'created_at'
                    ]
                ]
            ]);
    }

    public function test_user_can_retry_failed_processing_job()
    {
        Queue::fake();

        $job = FileProcessingJob::factory()->create([
            'status' => 'failed',
            'job_type' => 'watermark',
            'attempts' => 1
        ]);

        $response = $this->postJson("/api/file-management/processing-jobs/{$job->id}/retry");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Processing job retried successfully'
            ]);

        // Verify job was queued again
        Queue::assertPushed(ProcessFileWatermark::class);
    }

    public function test_user_can_get_file_statistics()
    {
        // Create test data
        InvoiceAttachment::factory()->count(5)->create();
        FileWatermark::factory()->count(3)->create();
        FileProcessingJob::factory()->count(2)->create(['status' => 'completed']);
        FileProcessingJob::factory()->create(['status' => 'failed']);

        $response = $this->getJson('/api/file-management/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'total_files',
                    'watermarked_files',
                    'processing_jobs',
                    'storage_usage'
                ]
            ]);
    }

    public function test_watermarking_job_processes_correctly()
    {
        $attachment = InvoiceAttachment::factory()->create([
            'invoice_id' => $this->invoice->id,
            'file_path' => 'test-file.pdf',
            'processing_status' => 'pending'
        ]);

        // Create fake original file
        Storage::put($attachment->file_path, 'original file content');

        // Process the watermarking job
        $job = new ProcessFileWatermark($attachment->id);
        $job->handle(app(\App\Services\WatermarkService::class));

        // Verify watermark record was created
        $this->assertDatabaseHas('file_watermarks', [
            'original_file_id' => $attachment->id
        ]);

        // Verify attachment status was updated
        $attachment->refresh();
        $this->assertTrue($attachment->processing_status === 'completed');
    }

    public function test_unauthorized_user_cannot_access_file_management()
    {
        $this->user->tokens()->delete();

        $response = $this->getJson('/api/file-management/statistics');
        $response->assertStatus(401);
    }

    public function test_user_cannot_access_files_from_other_invoices()
    {
        $otherUser = User::factory()->create();
        $otherInvoice = Invoice::factory()->create(['created_by' => $otherUser->id]);
        $otherAttachment = InvoiceAttachment::factory()->create([
            'invoice_id' => $otherInvoice->id
        ]);

        $response = $this->getJson("/api/file-management/attachments/{$otherAttachment->id}/processing-status");

        // Should return 404 or 403 depending on authorization logic
        $this->assertContains($response->status(), [403, 404]);
    }
}
