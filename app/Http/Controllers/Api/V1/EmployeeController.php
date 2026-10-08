<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BodLevel;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Parameter filter yang menyentuh kolom tabel relasi, jadi butuh join
     * walaupun yang dihitung hanya jumlah barisnya.
     */
    private const JOINED_FILTERS = ['posisi', 'operasional', 'job_group_id', 'job_function_id', 'level_bod', 'regional_id'];

    // GET /api/v1/employees
    public function index(Request $request): JsonResponse
    {
        // Join ke 6 tabel relasi membuat `count(*)` paginasi ~15x lebih lambat
        // (±190 ms vs ±11 ms untuk 19 ribu pegawai). Semuanya belongsTo, jadi
        // tidak mengubah jumlah baris; total cukup dihitung dari `employees`
        // saja kecuali ada filter yang memang menyentuh tabel relasi.
        $needsJoins = collect(self::JOINED_FILTERS)->contains(fn (string $param) => $request->filled($param));
        $total = $this->filteredQuery($request, $needsJoins)->count();

        $query = $this->filteredQuery($request, true)
            ->select('employees.*')
            ->with([
                'positionTitle.jobGroup',
                'positionTitle.jobFunction',
                'entity:id,parent_id,type,code,name',
                'entity.parent:id,type,code,name',
                'entityOperational.operationalCategory',
                'entityOperational.businessType',
            ]);

        $this->applySort($query, $request, $this->sortableColumns(), 'employees.name');

        $employees = $query->paginate($request->integer('per_page', 20), ['*'], 'page', null, $total);

        return $this->successPaginated($employees, EmployeeResource::class, 'Daftar pegawai berhasil diambil');
    }

    /**
     * Pegawai dalam cakupan akun, dengan seluruh filter dari request.
     *
     * @param  bool  $withJoins  pasang join ke tabel relasi (untuk mengurutkan
     *   dan menampilkan); dipaksa true kalau ada filter yang membutuhkannya
     */
    private function filteredQuery(Request $request, bool $withJoins): Builder
    {
        $query = Employees::query();

        if ($withJoins) {
            // join dipakai untuk menyaring dan mengurutkan kolom milik relasi;
            // semuanya belongsTo, jadi tidak menggandakan baris
            $query
                ->leftJoin('position_titles', 'position_titles.id', '=', 'employees.position_title_id')
                ->leftJoin('job_groups', 'job_groups.id', '=', 'position_titles.job_group_id')
                ->leftJoin('job_functions', 'job_functions.id', '=', 'position_titles.job_function_id')
                ->leftJoin('entities', 'entities.id', '=', 'employees.entity_id')
                // induk entity; untuk pegawai Unit, inilah regionalnya
                ->leftJoin('entities as regionals', 'regionals.id', '=', 'entities.parent_id')
                ->leftJoin('entity_operationals', 'entity_operationals.id', '=', 'employees.entity_operational_id');
        }

        $this->scopeToUser($query, $request);

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

        return $query;
    }

    /**
     * Batasi ke pegawai di entity yang boleh dilihat akun. Akun Head Office
     * melihat semuanya, jadi filternya dilewati (lihat User::hasFullAccess).
     */
    private function scopeToUser(Builder $query, Request $request): void
    {
        $user = $request->user();

        if (! $user->hasFullAccess()) {
            $query->whereIn('employees.entity_id', $user->accessibleEntityIds());
        }
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
     *
     * Butuh ±12 query (±230 ms) padahal isinya hanya berubah saat data
     * pegawai diimpor ulang, jadi hasilnya di-cache per cakupan akun.
     * ImportEmployees menaikkan versi data supaya cache lama tidak terpakai.
     */
    public function filterOptions(Request $request): JsonResponse
    {
        $user = $request->user();
        $scope = $user->hasFullAccess() ? 'all' : md5(implode(',', $user->accessibleEntityIds()));
        $version = Cache::get(Employees::DATA_VERSION_CACHE_KEY, 0);

        $options = Cache::remember(
            "employees:filter-options:v{$version}:{$scope}",
            now()->addHour(),
            fn () => $this->buildFilterOptions($user),
        );

        return $this->success($options, 'Pilihan filter pegawai berhasil diambil');
    }

    /** @return array<string, mixed> isi filter-options, dalam bentuk array biasa supaya bisa di-cache */
    private function buildFilterOptions($user): array
    {
        $scoped = fn () => $user->hasFullAccess()
            ? Employees::query()
            : Employees::query()->whereIn('entity_id', $user->accessibleEntityIds());

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

        return collect([
            'entities' => $entities,
            'regionals' => $regionals,
            'operasional' => $operasional,
            'job_groups' => $jobGroups,
            'job_functions' => $jobFunctions,
            'level_bod' => $levelBod,
            'golongan_phdp' => $this->distinctValues($scoped(), 'golongan_phdp'),
            'person_grade' => $this->distinctValues($scoped(), 'person_grade'),
        ])->toArray();
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
        $query = Employees::query()->join('entities', 'entities.id', '=', 'employees.entity_id');
        $this->scopeToUser($query, $request);

        $counts = $query
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

    /**
     * GET /api/v1/employees/random-sample?total=100&levels[1]=10&levels[4]=40
     *
     * Pegawai acak untuk laporan Program Budaya, yang pesertanya (umumnya
     * daring) tidak tercatat satu per satu. `levels` = jumlah per level BOD;
     * sisa `total` yang belum dibagi diambil acak dari semua level.
     *
     * Kalau sebuah level kekurangan pegawai, kekurangannya ikut diambil dari
     * level lain supaya jumlah peserta tetap `total` — jumlah inilah yang
     * dipakai di laporan. Frontend membandingkan level hasilnya dengan
     * permintaan untuk memberi tahu kekurangan tersebut.
     */
    public function randomSample(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'total' => ['required', 'integer', 'min:1', 'max:5000'],
            'levels' => ['nullable', 'array'],
            'levels.*' => ['integer', 'min:0'],
        ], [
            'total.required' => 'Jumlah peserta wajib diisi.',
            'total.min' => 'Jumlah peserta minimal 1.',
            'total.max' => 'Jumlah peserta maksimal 5.000.',
        ]);

        $total = (int) $validated['total'];
        $levels = collect($validated['levels'] ?? [])
            ->map(fn ($count) => (int) $count)
            ->filter(fn (int $count) => $count > 0);

        $unknown = $levels->keys()->reject(fn ($level) => BodLevel::tryFrom((int) $level) !== null);
        if ($unknown->isNotEmpty()) {
            return $this->error('Level BOD tidak dikenal: '.$unknown->implode(', '), 422);
        }

        if ($levels->sum() > $total) {
            return $this->error('Jumlah peserta per level melebihi jumlah peserta.', 422);
        }

        $base = function () use ($request) {
            $query = Employees::query()
                ->leftJoin('position_titles', 'position_titles.id', '=', 'employees.position_title_id');
            $this->scopeToUser($query, $request);

            return $query;
        };

        $pickedIds = collect();
        foreach ($levels as $level => $count) {
            $pickedIds = $pickedIds->merge(
                $base()->where('position_titles.level_bod', (int) $level)
                    ->inRandomOrder()
                    ->limit($count)
                    ->pluck('employees.id')
            );
        }

        // sisa yang belum dibagi + kekurangan level di atas
        $remaining = $total - $pickedIds->count();
        if ($remaining > 0) {
            $pickedIds = $pickedIds->merge(
                $base()->whereNotIn('employees.id', $pickedIds)
                    ->inRandomOrder()
                    ->limit($remaining)
                    ->pluck('employees.id')
            );
        }

        $employees = Employees::query()
            ->select('employees.*')
            ->leftJoin('position_titles', 'position_titles.id', '=', 'employees.position_title_id')
            ->whereIn('employees.id', $pickedIds)
            ->with(['positionTitle', 'entity:id,parent_id,type,code,name', 'entity.parent:id,type,code,name'])
            // level tanpa BOD ditaruh paling akhir
            ->orderByRaw('position_titles.level_bod is null')
            ->orderBy('position_titles.level_bod')
            ->orderBy('employees.name')
            ->get();

        return $this->success(
            EmployeeResource::collection($employees),
            'Sampel pegawai acak berhasil diambil'
        );
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
