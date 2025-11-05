<?php

namespace App\Imports;

use App\Models\HrStaff;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;

class HrStaffImport implements ToCollection, WithHeadingRow
{
    private $updateExisting;
    private $importedCount = 0;
    private $updatedCount = 0;
    private $errors = [];

    public function __construct($updateExisting = true)
    {
        $this->updateExisting = $updateExisting;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                // Normalize column names
                $normalizedRow = $this->normalizeRow($row);
                
                // Validate row data
                $validator = Validator::make($normalizedRow, [
                    'staff_id' => 'required|string|max:50',
                    'name' => 'required|string|max:255',
                    'phone' => 'required|string|max:20',
                    'status' => 'required|in:active,inactive',
                    'dept' => 'nullable|string|max:100',
                    'email' => 'nullable|email|max:255',
                ]);

                if ($validator->fails()) {
                    $this->errors[] = "Row " . ($index + 2) . ": " . implode(', ', $validator->errors()->all());
                    continue;
                }

                // Process the row
                $this->processRow($normalizedRow);

            } catch (\Exception $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }

    private function normalizeRow($row)
    {
        $normalized = [];
        
        // Handle different column name variations
        $mappings = [
            'staff_id' => ['staff_id', 'staffid', 'employee_id', 'empid'],
            'name' => ['name', 'full_name', 'employee_name'],
            'phone' => ['phone', 'phone_number', 'mobile', 'contact'],
            'dept' => ['dept', 'department', 'unit'],
            'email' => ['email', 'email_address'],
            'status' => ['status', 'active_status'],
        ];

        foreach ($mappings as $field => $possibleNames) {
            foreach ($possibleNames as $possibleName) {
                if (isset($row[$possibleName]) && !empty($row[$possibleName])) {
                    $normalized[$field] = $row[$possibleName];
                    break;
                }
            }
            
            if (!isset($normalized[$field])) {
                $normalized[$field] = null;
            }
        }

        return $normalized;
    }

    private function processRow($data)
    {
        $existingStaff = HrStaff::where('staff_id', $data['staff_id'])->first();

        if ($existingStaff) {
            if ($this->updateExisting) {
                $existingStaff->update([
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'dept' => $data['dept'],
                    'email' => $data['email'],
                    'status' => $data['status'],
                    'synced_at' => now(),
                ]);
                $this->updatedCount++;
            }
        } else {
            HrStaff::create([
                'staff_id' => $data['staff_id'],
                'name' => $data['name'],
                'phone' => $data['phone'],
                'dept' => $data['dept'],
                'email' => $data['email'],
                'status' => $data['status'],
                'source' => 'csv',
                'synced_at' => now(),
            ]);
            $this->importedCount++;
        }
    }

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getUpdatedCount()
    {
        return $this->updatedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
