<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EmployeeSheetImport implements WithMultipleSheets, WithChunkReading
{
    public EmployeeImport $employeeImport;

    public function __construct()
    {
        $this->employeeImport = new EmployeeImport();
    }

    public function sheets(): array
    {
        // Instance disimpan sebagai properti, bukan `new` di dalam method ini.
        // sheets() dipanggil ulang setiap chunk - kalau bikin objek baru terus,
        // daftar NIK yang sudah diproses dan angka penghitungnya akan hilang.
        return [
            'employee' => $this->employeeImport,
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}