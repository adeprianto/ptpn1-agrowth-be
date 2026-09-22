<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EntityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Regional\StoreRegionalRequest;
use App\Http\Requests\Regional\UpdateRegionalRequest;
use App\Http\Resources\RegionalResource;
use App\Http\Resources\UnitListResource;
use App\Models\Employees;
use App\Models\Entities;
use App\Traits\ApiResponse;
use App\Traits\ListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegionalController extends Controller
{
    use ApiResponse;
    use ListQuery;

    /** Kolom yang boleh dipakai mengurutkan (nama dari frontend -> kolom database). */
    private const SORTABLE = [
        'name' => 'entities.name',
        'code' => 'entities.code',
        'jumlah_unit' => 'jumlah_unit',
        'jumlah_karyawan' => 'jumlah_karyawan',
    ];

    // GET /api/v1/regionals
    public function index(Request $request): JsonResponse
    {
        $query = $this->withTotals(
            Entities::query()
                ->where('type', 'REGIONAL')
                ->whereIn('id', $request->user()->accessibleEntityIds())
        );

        // Pencarian gabungan nama + kode
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Kotak cari di bawah judul kolom
        $this->applyLike($query, $request, 'name', 'entities.name');
        $this->applyLike($query, $request, 'code', 'entities.code');

        $this->applySort($query, $request, self::SORTABLE, 'entities.code');

        $regionals = $query->paginate($request->integer('per_page', 15));

        return $this->successPaginated($regionals, RegionalResource::class, 'Daftar regional berhasil diambil');
    }

    // GET /api/v1/regionals/summary - 3 card di atas tabel
    public function summary(Request $request): JsonResponse
    {
        $accessibleIds = $request->user()->accessibleEntityIds();

        $regionalIds = Entities::where('type', 'REGIONAL')->whereIn('id', $accessibleIds)->pluck('id');
        $unitIds = Entities::where('type', 'UNIT')->whereIn('parent_id', $regionalIds)->pluck('id');

        return $this->success([
            'total_regional' => $regionalIds->count(),
            'total_unit' => $unitIds->count(),
            'total_karyawan' => Employees::whereIn('entity_id', $regionalIds->merge($unitIds))->count(),
        ], 'Ringkasan regional berhasil diambil');
    }

    // GET /api/v1/regionals/{regional}
    public function show(Entities $regional): JsonResponse
    {
        abort_unless($regional->type === 'REGIONAL', 404, 'Regional tidak ditemukan.');

        return $this->success($this->showResource($regional->id), 'Detail regional berhasil diambil');
    }

    // GET /api/v1/regionals/{regional}/units - tabel Struktur Unit di Detail Regional
    public function units(Request $request, Entities $regional): JsonResponse
    {
        abort_unless($regional->type === 'REGIONAL', 404, 'Regional tidak ditemukan.');

        $query = Entities::query()
            ->where('type', 'UNIT')
            ->where('parent_id', $regional->id)
            ->with(['operationals.operationalCategory', 'operationals.businessType'])
            ->addSelect([
                'entities.*',
                'jumlah_karyawan' => Employees::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('employees.entity_id', 'entities.id'),
            ]);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $units = $query->orderBy('name')->paginate($request->integer('per_page', 6));

        return $this->successPaginated($units, UnitListResource::class, 'Daftar unit regional berhasil diambil');
    }

    // POST /api/v1/regionals - hanya akun Head Office yang boleh menambah regional
    public function store(StoreRegionalRequest $request): JsonResponse
    {
        abort_unless(
            $request->user()->entity?->type === 'HEAD_OFFICE',
            403,
            'Hanya akun Head Office yang dapat menambah regional.'
        );

        $headOffice = Entities::where('type', 'HEAD_OFFICE')->first();

        abort_unless($headOffice !== null, 422, 'Entity Head Office belum ada, regional tidak bisa dibuat.');

        $regional = Entities::create([
            ...$request->validated(),
            'parent_id' => $headOffice->id,
            'type' => 'REGIONAL',
            'level' => EntityType::REGIONAL->defaulLevel(),
        ]);

        return $this->success($this->showResource($regional->id), 'Regional berhasil dibuat', 201);
    }

    // PUT /api/v1/regionals/{regional}
    public function update(UpdateRegionalRequest $request, Entities $regional): JsonResponse
    {
        abort_unless($regional->type === 'REGIONAL', 404, 'Regional tidak ditemukan.');

        $regional->update($request->validated());

        return $this->success($this->showResource($regional->id), 'Regional berhasil diperbarui');
    }

    // DELETE /api/v1/regionals/{regional}
    public function destroy(Entities $regional): JsonResponse
    {
        abort_unless($regional->type === 'REGIONAL', 404, 'Regional tidak ditemukan.');

        if ($regional->children()->exists()) {
            return $this->error('Regional tidak bisa dihapus karena masih memiliki unit di bawahnya.', 409);
        }

        if (Employees::where('entity_id', $regional->id)->exists()) {
            return $this->error('Regional tidak bisa dihapus karena masih memiliki pegawai.', 409);
        }

        if ($regional->users()->exists()) {
            return $this->error('Regional tidak bisa dihapus karena masih memiliki user.', 409);
        }

        $regional->operationals()->delete();
        $regional->delete();

        return $this->success(null, 'Regional berhasil dihapus');
    }

    private function showResource(int $id): RegionalResource
    {
        $regional = $this->withTotals(Entities::query()->whereKey($id))
            ->with('parent:id,code,name,type')
            ->first();

        return new RegionalResource($regional);
    }

    // jumlah_unit = anak langsung; jumlah_karyawan = pegawai di regional itu sendiri + semua unit di bawahnya
    private function withTotals(Builder $query): Builder
    {
        return $query
            ->withCount(['children as jumlah_unit' => fn ($q) => $q->where('type', 'UNIT')])
            ->addSelect([
                // pegawai yang ditempatkan langsung di kantor regional saja
                'jumlah_karyawan_kantor' => Employees::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('employees.entity_id', 'entities.id'),

                'jumlah_karyawan' => Employees::query()
                    ->selectRaw('count(*)')
                    ->where(function ($q) {
                        $q->whereColumn('employees.entity_id', 'entities.id')
                            ->orWhereIn('employees.entity_id', Entities::query()
                                ->from('entities as units')
                                ->select('units.id')
                                ->whereColumn('units.parent_id', 'entities.id'));
                    }),
            ]);
    }
}
