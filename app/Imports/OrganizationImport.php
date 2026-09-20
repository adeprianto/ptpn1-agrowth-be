<?php

namespace App\Imports;

use App\Models\Entities;
use App\Models\JobFunctions;
use App\Models\Organization;
use App\Models\OrganizationType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class OrganizationImport implements ToCollection, WithHeadingRow
{
    private array $entityMap = [];
    private array $functionMap = [];
    private array $typeMap = [];

    public function collection(Collection $rows)
    {
        $this->entityMap = Entities::pluck('id', 'code')->all();
        $this->functionMap = JobFunctions::pluck('id', 'code')->all();

        DB::transaction(function () use ($rows) {
            // ID pada sheet (ORG0001) hanya penanda internal Excel untuk
            // menyambungkan parent-anak, bukan kolom di database.
            $idMap = [];

            // Tahap 1: simpan semua node dulu tanpa parent_id
            foreach ($rows as $row) {
                $code = trim((string) ($row['code'] ?? ''));
                $name = trim((string) ($row['nama_organisasi'] ?? ''));

                if ($code === '' || $name === '') {
                    continue;
                }

                $entityId = $this->resolveEntityId($row['entity_code'] ?? null, $code);

                if (! $entityId) {
                    Log::warning("Organization import: baris '{$code}' dilewati karena entity tidak ditemukan.");
                    continue;
                }

                $organization = Organization::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'level' => (int) ($row['level'] ?? 0),
                        'organization_type_id' => $this->resolveTypeId($row['type'] ?? null),
                        'entity_id' => $entityId,
                        'job_function_id' => $this->resolveFunctionId($row['function_code'] ?? null),
                    ]
                );

                if (! empty($row['id'])) {
                    $idMap[$row['id']] = $organization->id;
                }
            }

            // Tahap 2: sambungkan parent_id setelah semua node ada
            foreach ($rows as $row) {
                $code = trim((string) ($row['code'] ?? ''));

                if ($code === '' || empty($row['parent_id'])) {
                    continue;
                }

                $parentDbId = $idMap[$row['parent_id']] ?? null;

                if (! $parentDbId) {
                    Log::warning("Organization import: Parent Id '{$row['parent_id']}' untuk '{$code}' tidak ditemukan.");
                    continue;
                }

                Organization::where('code', $code)->update(['parent_id' => $parentDbId]);
            }
        });
    }

    private function resolveTypeId(?string $type): ?int
    {
        $type = trim((string) $type);

        if ($type === '') {
            return null;
        }

        // perbaiki salah ketik di data sumber
        $type = str_ireplace('ADMINISITRATUR', 'ADMINISTRATUR', $type);

        $code = Str::upper(str_replace(' ', '_', $type));

        if (isset($this->typeMap[$code])) {
            return $this->typeMap[$code];
        }

        $organizationType = OrganizationType::firstOrCreate(
            ['code' => $code],
            ['name' => Str::title(Str::lower($type))]
        );

        return $this->typeMap[$code] = $organizationType->id;
    }

    private function resolveEntityId(?string $code, string $orgCode): ?int
    {
        $code = trim((string) $code);

        if ($code === '') {
            return null;
        }

        if (! isset($this->entityMap[$code])) {
            Log::warning("Organization import: Entity Code '{$code}' (organisasi '{$orgCode}') tidak ada di tabel entities.");

            return null;
        }

        return $this->entityMap[$code];
    }

    private function resolveFunctionId(?string $code): ?int
    {
        $code = trim((string) $code);

        if ($code === '') {
            return null;
        }

        return $this->functionMap[$code] ?? null;
    }
}