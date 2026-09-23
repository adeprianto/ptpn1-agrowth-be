<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employees;
use App\Models\Entities;
use App\Models\EntityOperational;
use App\Models\JobFunctions;
use App\Models\JobGroups;
use App\Models\PositionTitle;
use App\Traits\ApiResponse;
use App\Traits\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    use ApiResponse;
    use ListQuery;

    /**
     * Regional tempat seorang pegawai bernaung, sebagai ekspresi SQL.
     *
     * Kolom sumbernya berbeda per tipe entity, jadi tidak bisa ditulis sebagai
     * satu nama kolom biasa:
     * - pegawai Unit     -> nama entity induknya
     * - selain itu       -> nama entity-nya sendiri (Regional, atau Head Office)
     */
    private const REGIONAL_NAME_SQL =
        "CASE WHEN entities.type = 'UNIT' THEN regionals.name ELSE entities.name END";

    /**
     * Kolom yang boleh dipakai mengurutkan, dipetakan dari nama yang dikirim
     * frontend (= id kolom tabel) ke kolom database.
     *
     * Method, bukan const, karena salah satu nilainya berupa ekspresi SQL.
     */
    private function sortableColumns(): array
    {
        return [
            'nik' => 'employees.nik',
            'name' => 'employees.name',
            'regional' => DB::raw(self::REGIONAL_NAME_SQL),
            'entity' => 'entities.name',
            'operasional' => 'entity_operationals.code',
            'posisi' => 'position_titles.name',
            'job_group' => 'job_groups.name',
            'job_function' => 'job_functions.name',
            'level' => 'position_titles.level_bod',
            'golongan_phdp' => 'employees.golongan_phdp',
            'person_grade' => 'employees.person_grade',
        ];
    }

    // GET /api/v1/employees
    public function index(Request $request): JsonResponse
    {
        $query = Employees::query()
            // join dipakai untuk menyaring dan mengurutkan kolom milik relasi;
            // semuanya belongsTo, jadi tidak menggandakan baris
            ->select('employees.*')
            ->leftJoin('position_titles', 'position_titles.id', '=', 'employees.position_title_id')
            ->leftJoin('job_groups', 'job_groups.id', '=', 'position_titles.job_group_id')
            ->leftJoin('job_functions', 'job_functions.id', '=', 'position_titles.job_function_id')
            ->leftJoin('entities', 'entities.id', '=', 'employees.entity_id')
            // induk entity; untuk pegawai Unit, inilah regionalnya
            ->leftJoin('entities as regionals', 'regionals.id', '=', 'entities.parent_id')
            ->leftJoin('entity_operationals', 'entity_operationals.id', '=', 'employees.entity_operational_id')
            ->with([
                'positionTitle.jobGroup',
                'positionTitle.jobFunction',
                'entity:id,parent_id,type,code,name',
                'entity.parent:id,type,code,name',
                'entityOperational.operationalCategory',
                'entityOperational.businessType',
            ])
            ->whereIn('employees.entity_id', $request->user()->accessibleEntityIds());

        // Pencarian gabungan nama + NIK, dipakai tabel karyawan di halaman detail entity
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('employees.name', 'like', "%{$search}%")
                    ->orWhere('employees.nik', 'like', "%{$search}%");
            });
        }

        // Kotak cari di bawah judul kolom
        $this->applyLike($query, $request, 'nik', 'employees.nik');
        $this->applyLike($query, $request, 'name', 'employees.name');
        $this->applyLike($query, $request, 'posisi', 'position_titles.name');

        // Daftar centang di modal filter — boleh lebih dari satu nilai
        // Daftar NIK persis (bukan "mengandung") — dipakai impor peserta dari Excel
        $this->applyInFilter($query, $request, 'niks', 'employees.nik');
        $this->applyInFilter($query, $request, 'entity_id', 'employees.entity_id');
        $this->applyInFilter($query, $request, 'status', 'employees.status');
        $this->applyInFilter($query, $request, 'golongan_phdp', 'employees.golongan_phdp');
        $this->applyInFilter($query, $request, 'person_grade', 'employees.person_grade');
        $this->applyInFilter($query, $request, 'operasional', 'entity_operationals.code');
        $this->applyInFilter($query, $request, 'job_group_id', 'position_titles.job_group_id');
        $this->applyInFilter($query, $request, 'job_function_id', 'position_titles.job_function_id');
        $this->applyInFilter($query, $request, 'level_bod', 'position_titles.level_bod');
        $this->applyRegionalFilter($query, $request);

        $this->applySort($query, $request, $this->sortableColumns(), 'employees.name');

        $employees = $query->paginate($request->integer('per_page', 20));

        return $this->successPaginated($employees, EmployeeResource::class, 'Daftar pegawai berhasil diambil');
    }

    /**
     * Filter checklist kolom Regional. Nilainya id entity regional, jadi
     * pencocokannya berbeda tergantung tipe entity pegawai: pegawai Unit
     * dicocokkan lewat induknya, selain itu lewat entity-nya sendiri.
     */
    private function applyRegionalFilter($query, Request $request): void
    {
        $regionalIds = $this->queryList($request, 'regional_id');

        if ($regionalIds === []) {
            return;
        }

        $query->where(function ($outer) use ($regionalIds) {
            $outer
                ->where(fn ($q) => $q->where('entities.type', 'UNIT')
                    ->whereIn('entities.parent_id', $regionalIds))
                ->orWhere(fn ($q) => $q->where('entities.type', '!=', 'UNIT')
                    ->whereIn('entities.id', $regionalIds));
        });
    }

    /**
     * GET /api/v1/employees/filter-options
     *
     * Isi daftar centang di modal filter tabel pegawai. Hanya nilai yang
     * benar-benar dipakai pegawai dalam cakupan akun ini yang dikirim, supaya
     * tidak ada pilihan yang sudah pasti menghasilkan tabel kosong.
     */
    public function filterOptions(Request $request): JsonResponse
    {
        $entityIds = $request->user()->accessibleEntityIds();
        $scoped = fn () => Employees::query()->whereIn('entity_id', $entityIds);

        $entities = Entities::query()
            ->whereIn('id', $scoped()->select('entity_id'))
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'type']);

        $operasional = EntityOperational::query()
            ->whereIn('id', $scoped()->select('entity_operational_id'))
            ->with(['operationalCategory', 'businessType'])
            ->orderBy('code')
            ->get()
            ->map(fn (EntityOperational $row) => [
                // dikirim balik apa adanya sebagai nilai filter `operasional`
                'key' => $row->code,
                'jenis' => $row->operationalCategory ? [
                    'id' => $row->operationalCategory->id,
                    'code' => $row->operationalCategory->code,
                    'name' => $row->operationalCategory->name,
                ] : null,
                'komoditas' => $row->businessType ? [
                    'id' => $row->businessType->id,
                    'code' => $row->businessType->code,
                    'name' => $row->businessType->name,
                ] : null,
            ])
            ->values();

        // Id regional tiap pegawai: Unit lewat induknya, selain itu entity-nya sendiri
        $regionalIds = Entities::query()
            ->toBase()
            ->whereIn('id', $scoped()->select('entity_id'))
            ->selectRaw("DISTINCT CASE WHEN type = 'UNIT' THEN parent_id ELSE id END as regional_id")
            ->pluck('regional_id')
            ->filter()
            ->values();

        $regionals = Entities::query()
            ->whereIn('id', $regionalIds)
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'type']);

        $positionTitleIds = $scoped()->select('position_title_id');

        $jobGroups = JobGroups::query()
            ->whereIn('id', PositionTitle::query()->whereIn('id', $positionTitleIds)->select('job_group_id'))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $jobFunctions = JobFunctions::query()
            ->whereIn('id', PositionTitle::query()->whereIn('id', $positionTitleIds)->select('job_function_id'))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        // level_bod di-cast ke enum BodLevel oleh model, jadi diambil sebagai
        // nilai mentah lewat query builder supaya tetap berupa angka
        $levelBod = PositionTitle::query()
            ->toBase()
            ->whereIn('id', $positionTitleIds)
            ->whereNotNull('level_bod')
            ->distinct()
            ->orderBy('level_bod')
            ->pluck('level_bod')
            ->map(fn ($level) => (int) $level)
            ->values();

        return $this->success([
            'entities' => $entities,
            'regionals' => $regionals,
            'operasional' => $operasional,
            'job_groups' => $jobGroups,
            'job_functions' => $jobFunctions,
            'level_bod' => $levelBod,
            'golongan_phdp' => $this->distinctValues($scoped(), 'golongan_phdp'),
            'person_grade' => $this->distinctValues($scoped(), 'person_grade'),
        ], 'Pilihan filter pegawai berhasil diambil');
    }

    /** Nilai unik sebuah kolom teks, tanpa yang kosong. */
    private function distinctValues($query, string $column)
    {
        return $query
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->values();
    }

    // GET /api/v1/employees/summary - 4 card di atas tabel (total + per tipe penempatan)
    public function summary(Request $request): JsonResponse
    {
        $counts = Employees::query()
            ->join('entities', 'entities.id', '=', 'employees.entity_id')
            ->whereIn('employees.entity_id', $request->user()->accessibleEntityIds())
            ->selectRaw('entities.type, count(*) as total')
            ->groupBy('entities.type')
            ->pluck('total', 'type');

        return $this->success([
            'total_karyawan' => (int) $counts->sum(),
            'total_head_office' => (int) ($counts['HEAD_OFFICE'] ?? 0),
            'total_regional' => (int) ($counts['REGIONAL'] ?? 0),
            'total_unit' => (int) ($counts['UNIT'] ?? 0),
        ], 'Ringkasan pegawai berhasil diambil');
    }

    // GET /api/v1/employees/{employee}
    public function show(Employees $employee): JsonResponse
    {
        abort_unless(
            in_array($employee->entity_id, request()->user()->accessibleEntityIds(), true),
            403,
            'Anda tidak memiliki akses ke data pegawai ini.'
        );

        $employee->load([
            'positionTitle.jobGroup',
            'positionTitle.jobFunction',
            'entity.parent',
            'entityOperational.businessType',
            'trainingRealizationDetails' => fn ($q) => $q->orderByDesc('start_date'),
            'trainingRealizationDetails.realization.training.vendor',
        ]);

        return $this->success(new EmployeeResource($employee), 'Detail pegawai berhasil diambil');
    }
}
