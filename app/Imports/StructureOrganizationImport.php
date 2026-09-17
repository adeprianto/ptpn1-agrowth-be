<?php

namespace App\Imports;

use App\Models\Entities;
use App\Models\JobFunctions;
use App\Models\StructureOrganization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StrukturOrganisasiImport implements ToCollection, WithHeadingRow
{
    /** @var array<string, int> code entity => id */
    private array $entityMap = [];

    /** @var array<string, int> code job function => id */
    private array $functionMap = [];

    public function __construct()
    {
        $this->entityMap = Entities::pluck('id', 'code')->all();
        $this->functionMap = JobFunctions::pluck('id', 'code')->all();
    }

    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            $idMap = $this->buildIdMapAndWarnDuplicates($rows);

            // Pass 1: insert/update semua node tanpa parent_id dulu
            foreach ($rows as $row) {
                if (empty($row['code']) || empty($row['nama_organisasi'])) {
                    continue;
                }

                $structure = StructureOrganization::updateOrCreate(
                    ['code' => $row['code']],
                    [
                        'name'        => $row['nama_organisasi'],
                        'level'       => $this->normalizeLevel($row['level'] ?? null, $row['code']),
                        'entity_id'   => $this->resolveEntityId($row['entity_code'] ?? null),
                        'function_id' => $this->resolveFunctionId($row['function_code'] ?? null),
                    ]
                );

                if (!empty($row['id'])) {
                    $idMap[$row['id']] = $structure->id;
                }
            }

            // Pass 2: sambungkan parent_id berdasarkan mapping ID Excel -> id DB
            foreach ($rows as $row) {
                if (empty($row['code']) || empty($row['parent_id'])) {
                    continue;
                }

                $parentDbId = $idMap[$row['parent_id']] ?? null;

                if (!$parentDbId) {
                    Log::warning("Struktur Organisasi import: Parent Id '{$row['parent_id']}' untuk Code '{$row['code']}' tidak ditemukan.");
                    continue;
                }

                StructureOrganization::where('code', $row['code'])
                    ->update(['parent_id' => $parentDbId]);
            }
        });
    }

    private function buildIdMapAndWarnDuplicates(Collection $rows): array
    {
        $seen = [];
        $duplicates = [];

        foreach ($rows as $row) {
            $excelId = $row['id'] ?? null;
            if (empty($excelId)) {
                continue;
            }
            if (isset($seen[$excelId])) {
                $duplicates[$excelId] = true;
            }
            $seen[$excelId] = true;
        }

        if (!empty($duplicates)) {
            Log::warning('Struktur Organisasi import: ditemukan ID duplikat di Excel, baris terakhir akan dipakai untuk mapping parent.', [
                'duplicate_ids' => array_keys($duplicates),
            ]);
        }

        return [];
    }

    private function normalizeLevel($value, string $code): string
    {
        $level = (string) (int) $value;

        if (!in_array($level, ['1', '2', '3'], true)) {
            throw new \Exception("Level tidak valid untuk Code '{$code}': '{$value}'. Harus 1, 2, atau 3.");
        }

        return $level;
    }

    private function resolveEntityId(?string $code): ?int
    {
        if (empty($code)) {
            return null;
        }

        if (!isset($this->entityMap[$code])) {
            Log::warning("Struktur Organisasi import: Entity Code '{$code}' tidak ditemukan di tabel entities.");
            return null;
        }

        return $this->entityMap[$code];
    }

    private function resolveFunctionId(?string $code): ?int
    {
        if (empty($code)) {
            return null;
        }

        if (!isset($this->functionMap[$code])) {
            Log::warning("Struktur Organisasi import: Function Code '{$code}' tidak ditemukan di tabel job_functions.");
            return null;
        }

        return $this->functionMap[$code];
    }
}