<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entity\StoreEntityRequest;
use App\Http\Requests\Entity\UpdateEntityRequest;
use App\Http\Resources\EntityResource;
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
        $query = Entities::query()->withCount('children');

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

    // GET /api/v1/entities/tree - struktur HO > Regional > Unit sekaligus
    public function tree(): JsonResponse
    {
        $roots = Entities::query()
            ->whereNull('parent_id')
            ->with('children.children')
            ->orderBy('name')
            ->get();

        return $this->success(EntityResource::collection($roots), 'Struktur hierarki entity berhasil diambil');
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