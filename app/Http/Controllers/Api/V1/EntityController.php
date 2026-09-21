<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entity\StoreEntityRequest;
use App\Http\Requests\Entity\UpdateEntityRequest;
use App\Enums\EntityType;
use App\Http\Resources\EntityResource;
use App\Models\Employees;
use App\Models\Entities;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntityController extends Controller
{
    use ApiResponse;

    // GET /api/v1/entities
    public function index(Request $request): JsonResponse
    {
        // dibatasi cakupan akun: akun Regional/Unit tidak melihat entity di luar hierarkinya
        $query = Entities::query()
            ->withCount('children')
            ->whereIn('id', $request->user()->accessibleEntityIds());

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->input('parent_id'));
        }

        // berguna buat dropdown pilih HO saja
        if ($request->boolean('root_only')) {
            $query->whereNull('parent_id');
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $query->orderBy('level')->orderBy('name');

        $entities = $query->paginate((int) $request->input('per_page', 15));

        return $this->successPaginated($entities, EntityResource::class, 'Data entity berhasil diambil');
    }

    // GET /api/v1/entities/tree - struktur HO > Regional > Unit sekaligus (halaman Struktur Organisasi)
    // Di-scope sesuai akun: akun Regional hanya dapat pohon regionalnya sendiri.
    public function tree(Request $request): JsonResponse
    {
        $accessibleIds = $request->user()->accessibleEntityIds();

        $entities = Entities::query()
            ->whereIn('id', $accessibleIds)
            ->with('operationals.operationalCategory')
            ->addSelect([
                'entities.*',
                'jumlah_karyawan' => Employees::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('employees.entity_id', 'entities.id'),
            ])
            ->orderBy('level')
            ->orderBy('code')
            ->get();

        $byParent = $entities->groupBy(fn ($e) => $e->parent_id ?? 0);

        $build = function (Entities $entity) use (&$build, $byParent): array {
            $children = ($byParent[$entity->id] ?? collect())
                ->map(fn ($child) => $build($child))
                ->values();

            return [
                'id' => $entity->id,
                'parent_id' => $entity->parent_id,
                'level' => $entity->level,
                'type' => $entity->type,
                'type_label' => EntityType::tryFrom($entity->type)?->label(),
                'code' => $entity->code,
                'name' => $entity->name,
                'status' => $entity->status,
                'jenis' => $entity->operationals
                    ->pluck('operationalCategory')
                    ->filter()
                    ->unique('id')
                    ->map(fn ($c) => ['id' => $c->id, 'code' => $c->code, 'name' => $c->name])
                    ->values(),
                // pegawai yang ditempatkan langsung di entity ini
                'jumlah_karyawan' => (int) $entity->jumlah_karyawan,
                // termasuk seluruh entity di bawahnya
                'total_karyawan' => (int) $entity->jumlah_karyawan + $children->sum('total_karyawan'),
                'children' => $children,
            ];
        };

        // root = entity yang induknya di luar cakupan akun (HO untuk akun HO, Regional untuk akun Regional)
        $roots = $entities
            ->filter(fn ($e) => $e->parent_id === null || ! in_array($e->parent_id, $accessibleIds, true))
            ->map(fn ($root) => $build($root))
            ->values();

        return $this->success($roots, 'Struktur hierarki entity berhasil diambil');
    }

    // GET /api/v1/entities/{entity}
    public function show(Entities $entity): JsonResponse
    {
        $entity->load(['parent', 'children'])->loadCount('children');

        return $this->success(new EntityResource($entity), 'Detail entity berhasil diambil');
    }

    // POST /api/v1/entities
    public function store(StoreEntityRequest $request): JsonResponse
    {
        $entity = Entities::create($request->validated());

        return $this->success(new EntityResource($entity), 'Entity berhasil dibuat', 201);
    }

    // PUT/PATCH /api/v1/entities/{entity}
    public function update(UpdateEntityRequest $request, Entities $entity): JsonResponse
    {
        $entity->update($request->validated());

        return $this->success(
            new EntityResource($entity->fresh(['parent', 'children'])),
            'Entity berhasil diperbarui'
        );
    }

    // DELETE /api/v1/entities/{entity}
    public function destroy(Entities $entity): JsonResponse
    {
        if ($entity->children()->exists()) {
            return $this->error('Entity tidak bisa dihapus karena masih memiliki entity anak (child).', 422);
        }

        if ($entity->operationals()->exists()) {
            return $this->error('Entity tidak bisa dihapus karena masih memiliki data fasilitas operasional terkait.', 422);
        }

        if ($entity->users()->exists()) {
            return $this->error('Entity tidak bisa dihapus karena masih memiliki user terkait.', 422);
        }

        $entity->delete();

        return $this->success(null, 'Entity berhasil dihapus');
    }
}