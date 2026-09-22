<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EntityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Unit\StoreUnitRequest;
use App\Http\Requests\Unit\UpdateUnitRequest;
use App\Http\Resources\UnitListResource;
use App\Models\Employees;
use App\Models\Entities;
use App\Models\EntityOperational;
use App\Traits\ApiResponse;
use App\Traits\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitController extends Controller
{
    use ApiResponse;
    use ListQuery;

    /** Kolom yang boleh dipakai mengurutkan (nama dari frontend -> kolom database). */
    private const SORTABLE = [
        'name' => 'entities.name',
        'code' => 'entities.code',
        'regional' => 'parents.name',
        'regional_id' => 'parents.name',
        'jumlah_karyawan' => 'jumlah_karyawan',
    ];

    // GET /api/v1/units
    public function index(Request $request): JsonResponse
    {
        $query = Entities::query()
            ->where('entities.type', 'UNIT')
            ->whereIn('entities.id', $request->user()->accessibleEntityIds())
            // join ke induk supaya kolom Regional bisa dicari dan diurutkan
            ->leftJoin('entities as parents', 'parents.id', '=', 'entities.parent_id')
            ->with([
                'parent:id,code,name',
                'operationals.operationalCategory',
                'operationals.businessType',
            ])
            ->addSelect(['entities.*', 'jumlah_karyawan' => $this->employeeCountSubquery()]);

        // Pencarian gabungan nama + kode (kolom dikualifikasi karena tabelnya di-join ke dirinya sendiri)
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('entities.name', 'like', "%{$search}%")
                    ->orWhere('entities.code', 'like', "%{$search}%");
            });
        }

        // Kotak cari di bawah judul kolom
        $this->applyLike($query, $request, 'name', 'entities.name');
        $this->applyLike($query, $request, 'code', 'entities.code');
        $this->applyLike($query, $request, 'regional', 'parents.name');

        // Daftar centang di modal filter — boleh lebih dari satu nilai
        $this->applyInFilter($query, $request, 'regional_id', 'entities.parent_id');

        foreach ([
            'operational_category_id' => 'operational_category_id',
            'business_type_id' => 'business_type_id',
        ] as $param => $column) {
            $values = $this->queryList($request, $param);

            if ($values !== []) {
                $query->whereHas('operationals', fn ($q) => $q->whereIn($column, $values));
            }
        }

        $this->applySort($query, $request, self::SORTABLE, 'entities.name');

        $units = $query->paginate($request->integer('per_page', 15));

        return $this->successPaginated($units, UnitListResource::class, 'Daftar unit berhasil diambil');
    }

    // GET /api/v1/units/summary - 3 card di atas tabel
    public function summary(Request $request): JsonResponse
    {
        $units = Entities::query()
            ->where('type', 'UNIT')
            ->whereIn('id', $request->user()->accessibleEntityIds());

        $countByCategory = fn (string $code) => (clone $units)
            ->whereHas('operationals.operationalCategory', fn ($q) => $q->where('code', $code))
            ->count();

        return $this->success([
            'total_unit' => (clone $units)->count(),
            'total_kebun' => $countByCategory('EST'),
            'total_pabrik' => $countByCategory('FAC'),
            'total_karyawan' => Employees::whereIn('entity_id', (clone $units)->select('id'))->count(),
        ], 'Ringkasan unit berhasil diambil');
    }

    // GET /api/v1/units/{unit}
    public function show(Entities $unit): JsonResponse
    {
        abort_unless($unit->type === 'UNIT', 404, 'Unit tidak ditemukan.');

        return $this->success($this->showResource($unit->id), 'Detail unit berhasil diambil');
    }

    // POST /api/v1/units
    public function store(StoreUnitRequest $request): JsonResponse
    {
        $data = $request->validated();

        abort_unless(
            in_array((int) $data['parent_id'], $request->user()->accessibleEntityIds(), true),
            403,
            'Anda tidak memiliki akses ke regional tersebut.'
        );

        $unit = DB::transaction(function () use ($data) {
            $unit = Entities::create([
                'parent_id' => $data['parent_id'],
                'code' => $data['code'],
                'name' => $data['name'],
                'status' => $data['status'],
                'type' => 'UNIT',
                'level' => EntityType::UNIT->defaulLevel(),
            ]);

            $this->syncOperationals($unit, $data['operationals'] ?? []);

            return $unit;
        });

        return $this->success($this->showResource($unit->id), 'Unit berhasil dibuat', 201);
    }

    // PUT /api/v1/units/{unit}
    public function update(UpdateUnitRequest $request, Entities $unit): JsonResponse
    {
        abort_unless($unit->type === 'UNIT', 404, 'Unit tidak ditemukan.');

        $data = $request->validated();

        if (isset($data['parent_id'])) {
            abort_unless(
                in_array((int) $data['parent_id'], $request->user()->accessibleEntityIds(), true),
                403,
                'Anda tidak memiliki akses ke regional tersebut.'
            );
        }

        DB::transaction(function () use ($unit, $data, $request) {
            $unit->update(collect($data)->only(['parent_id', 'code', 'name', 'status'])->all());

            // operationals hanya disentuh kalau field-nya ikut dikirim
            if ($request->has('operationals')) {
                $this->syncOperationals($unit, $data['operationals'] ?? []);
            }
        });

        return $this->success($this->showResource($unit->id), 'Unit berhasil diperbarui');
    }

    // DELETE /api/v1/units/{unit}
    public function destroy(Entities $unit): JsonResponse
    {
        abort_unless($unit->type === 'UNIT', 404, 'Unit tidak ditemukan.');

        if (Employees::where('entity_id', $unit->id)->exists()) {
            return $this->error('Unit tidak bisa dihapus karena masih memiliki pegawai.', 409);
        }

        if ($unit->users()->exists()) {
            return $this->error('Unit tidak bisa dihapus karena masih memiliki user.', 409);
        }

        // baris operasional + detail komoditasnya ikut terhapus (cascadeOnDelete)
        $unit->delete();

        return $this->success(null, 'Unit berhasil dihapus');
    }

    /**
     * Samakan baris entity_operationals dengan daftar yang dikirim.
     * Pasangan yang sudah ada dipertahankan supaya detail komoditas (luas lahan dll)
     * tidak ikut hilang; yang tidak ada di daftar baru dihapus.
     *
     * @param  array<int, array{operational_category_id: int, business_type_id?: int|null}>  $rows
     */
    private function syncOperationals(Entities $unit, array $rows): void
    {
        $existing = $unit->operationals()->get();
        $keyOf = fn ($categoryId, $businessTypeId) => $categoryId.'-'.($businessTypeId ?? '');

        $keptIds = [];

        foreach ($rows as $row) {
            $key = $keyOf($row['operational_category_id'], $row['business_type_id'] ?? null);
            $match = $existing->first(
                fn ($eo) => $keyOf($eo->operational_category_id, $eo->business_type_id) === $key
            );

            if ($match) {
                $keptIds[] = $match->id;

                continue;
            }

            $created = EntityOperational::create([
                'code' => $this->nextOperationalCode(),
                'entity_id' => $unit->id,
                'operational_category_id' => $row['operational_category_id'],
                'business_type_id' => $row['business_type_id'] ?? null,
            ]);

            $keptIds[] = $created->id;
        }

        $unit->operationals()->whereNotIn('id', $keptIds ?: [0])->delete();
    }

    // kode entity_operationals digenerate sistem: EO0001, EO0002, ...
    private function nextOperationalCode(): string
    {
        $last = EntityOperational::where('code', 'like', 'EO%')
            ->orderByRaw('CAST(SUBSTRING(code, 3) AS UNSIGNED) DESC')
            ->value('code');

        $next = $last ? ((int) substr($last, 2)) + 1 : 1;

        return 'EO'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function showResource(int $id): UnitListResource
    {
        $unit = Entities::query()
            ->whereKey($id)
            ->with([
                'parent:id,code,name',
                'operationals.operationalCategory',
                'operationals.businessType',
            ])
            ->addSelect(['entities.*', 'jumlah_karyawan' => $this->employeeCountSubquery()])
            ->first();

        return new UnitListResource($unit);
    }

    private function employeeCountSubquery()
    {
        return Employees::query()
            ->selectRaw('count(*)')
            ->whereColumn('employees.entity_id', 'entities.id');
    }
}
