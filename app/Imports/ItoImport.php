<?php

namespace App\Imports;

use App\Models\AdditionalDocument;
use App\Models\AdditionalDocumentType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ItoImport implements ToModel, WithHeadingRow
{
    use Importable;

    public $itoTypeId;
    public $batchNo;
    protected $checkDuplicates;
    protected $successCount = 0;
    protected $skippedCount = 0;
    protected $errors = [];

    public function __construct($checkDuplicates = false)
    {
        $this->itoTypeId = $this->getItoTypeId();
        $this->batchNo = $this->getBatchNo();
        $this->checkDuplicates = $checkDuplicates;
    }

    public function model(array $row)
    {
        // Check if required fields exist
        if (!isset($row['ito_no']) || empty($row['ito_no'])) {
            $this->errors[] = 'Row skipped: Missing ITO number';
            $this->skippedCount++;
            return null;
        }

        if ($this->checkDuplicates) {
            // Check for existing document with same document_number
            $exists = AdditionalDocument::whereHas('type', function ($query) {
                $query->where('type_name', 'ito');
            })->where('document_number', $row['ito_no'])
                ->exists();

            if ($exists) {
                $this->skippedCount++;
                return null; // Skip this record
            }
        }

        // Default values for optional fields
        $defaults = [
            'ito_date' => null,
            'po_no' => null,
            'project' => null,
            'receive_date' => null,
            'ito_remarks' => null,
            'ito_created_by' => null,
            'grpo_no' => null,
            'origin_whs' => null,
            'destination_whs' => null,
        ];

        // Merge defaults with actual data
        $rowData = array_merge($defaults, array_filter($row, function ($value) {
            return $value !== null && $value !== '';
        }));

        $this->successCount++;
        return new AdditionalDocument([
            'type_id' => $this->itoTypeId,
            'document_number' => $rowData['ito_no'],
            'document_date' => isset($rowData['ito_date']) ? $this->convert_date($rowData['ito_date']) : null,
            'po_no' => $rowData['po_no'] ?? null,
            'project' => $rowData['project'] ?? null,
            'receive_date' => isset($rowData['ito_date']) ? $this->convert_date($rowData['ito_date']) : null,
            'created_by' => Auth::user()->id,
            'remarks' => $rowData['ito_remarks'] ?? null,
            'status' => 'open',
            'cur_loc' => '000HLOG',
            'ito_creator' => $rowData['ito_created_by'] ?? null,
            'grpo_no' => $rowData['grpo_no'] ?? null,
            'origin_wh' => $rowData['origin_whs'] ?? null,
            'destination_wh' => $rowData['destination_whs'] ?? null,
            'batch_no' => $this->batchNo,
        ]);
    }

    private function convert_date($date)
    {
        if ($date) {
            try {
                if (is_string($date)) {
                    // Handle dd-mm-yyyy format
                    if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
                        $year = substr($date, 6, 4);
                        $month = substr($date, 3, 2);
                        $day = substr($date, 0, 2);
                        return $year . '-' . $month . '-' . $day;
                    }

                    // Try to parse with strtotime
                    $timestamp = strtotime($date);
                    if ($timestamp) {
                        return date('Y-m-d', $timestamp);
                    }
                } elseif ($date instanceof \DateTime) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                Log::error('Error converting date: ' . $e->getMessage());
            }
        }
        return null;
    }

    private function getItoTypeId()
    {
        try {
            $ito_type = AdditionalDocumentType::where('type_name', 'ITO')
                ->orWhere('type_name', 'ito')
                ->first();

            if (!$ito_type) {
                Log::error('ITO type not found in AdditionalDocumentType table');
                throw new \Exception('ITO document type not found in database. Please create it first.');
            }

            return $ito_type->id;
        } catch (\Exception $e) {
            Log::error('Error getting ITO type ID: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getBatchNo()
    {
        try {
            $batch_no = AdditionalDocument::max('batch_no') ?? 0;
            return $batch_no + 1;
        } catch (\Exception $e) {
            Log::error('Error getting batch number: ' . $e->getMessage());
            return 1; // Default to 1 if there's an error
        }
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getSkippedCount()
    {
        return $this->skippedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
