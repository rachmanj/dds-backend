<?php

namespace Database\Factories;

use App\Models\FileWatermark;
use App\Models\InvoiceAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileWatermarkFactory extends Factory
{
    protected $model = FileWatermark::class;

    public function definition(): array
    {
        return [
            'original_file_id' => InvoiceAttachment::factory(),
            'watermarked_path' => 'watermarked/' . $this->faker->uuid() . '.pdf',
            'watermark_text' => $this->faker->randomElement([
                'CONFIDENTIAL',
                'INTERNAL USE ONLY',
                'DRAFT',
                'COPY',
                'RESTRICTED'
            ]),
        ];
    }

    public function confidential(): static
    {
        return $this->state(fn(array $attributes) => [
            'watermark_text' => 'CONFIDENTIAL',
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn(array $attributes) => [
            'watermark_text' => 'DRAFT',
        ]);
    }

    public function copy(): static
    {
        return $this->state(fn(array $attributes) => [
            'watermark_text' => 'COPY',
        ]);
    }
}
