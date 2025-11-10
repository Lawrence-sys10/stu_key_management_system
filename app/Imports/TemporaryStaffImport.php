<?php

namespace App\Imports;

use App\Models\TemporaryStaff;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;

class TemporaryStaffImport implements ToCollection, WithHeadingRow
{
    private $importedCount = 0;
    private $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                // Normalize column names
                $normalizedRow = $this->normalizeRow($row);
                
                // Validate row data
                $validator = Validator::make($normalizedRow, [
                    'name' => 'required|string|max:255',
                    'phone' => 'required|string|max:20|unique:temporary_staff,phone',
                    'id_number' => 'nullable|string|max:50',
                    'dept' => 'nullable|string|max:100',
                ]);

                if ($validator->fails()) {
                    $this->errors[] = "Row " . ($index + 2) . ": " . implode(', ', $validator->errors()->all());
                    continue;
                }

                // Create temporary staff
                TemporaryStaff::create($normalizedRow);
                $this->importedCount++;

            } catch (\Exception $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }

    private function normalizeRow($row)
    {
        $normalized = [];
        
        $mappings = [
            'name' => ['name', 'full_name', 'visitor_name'],
            'phone' => ['phone', 'phone_number', 'mobile', 'contact'],
            'id_number' => ['id_number', 'id', 'visitor_id', 'identification'],
            'dept' => ['dept', 'department', 'company', 'organization'],
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

    public function getImportedCount()
    {
        return $this->importedCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
