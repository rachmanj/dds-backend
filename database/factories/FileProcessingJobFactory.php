<?php

namespace Database\Factories;

use App\Models\FileProcessingJob;
use App\Models\InvoiceAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileProcessingJobFactory extends Factory
{
    protected $model = FileProcessingJob::class;

    public function definition(): array
    {
        return [
            'file_id' => InvoiceAttachment::factory(),
            'job_type' => $this->faker->randomElement(['watermark', 'thumbnail']),
            'status' => $this->faker->randomElement(['pending', 'processing', 'completed', 'failed']),
            'attempts' => $this->faker->numberBetween(0, 3),
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
            'attempts' => 0,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'failed',
            'error_message' => 'Processing failed',
        ]);
    }
}
