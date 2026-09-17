<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EntityOperational\StoreEntityOperationalRequest;
use App\Http\Requests\EntityOperational\UpdateEntityOperationalRequest;
use App\Http\Resources\EntityOperationalResource;
use App\Models\EntityOperational;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EntityOperationalController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = EntityOperational::query()->with(['entity', 'operationalCategory', 'businessType']);

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->input('entity_id'));
        }

        if ($request->filled('operational_category_id')) {
            $query->where('operational_category_id', $request->input('operational_category_id'));
        }

        if ($request->filled('business_type_id')) {
            $query->where('business_type_id', $request->input('business_type_id'));
        }

        if ($request->filled('search')) {
            $query->where('code', 'like', '%' . $request->input('search') . '%');
        }

        $query->orderBy('code');

        $entityOperationals = $query->paginate((int) $request->input('per_page', 15));

        return $this->successPaginated($entityOperationals, EntityOperationalResource::class, 'Data entity operational berhasil diambil');
    }

    public function show(EntityOperational $entityOperational): JsonResponse
    {
        $entityOperational->load(['entity', 'operationalCategory', 'businessType', 'commodity']);

        return $this->success(new EntityOperationalResource($entityOperational), 'Detail entity operational berhasil diambil');
    }

    public function store(StoreEntityOperationalRequest $request): JsonResponse
    {
        $entityOperational = EntityOperational::create($request->validated());
        $entityOperational->load(['entity', 'operationalCategory', 'businessType']);

        return $this->success(new EntityOperationalResource($entityOperational), 'Entity operational berhasil dibuat', 201);
    }

    public function update(UpdateEntityOperationalRequest $request, EntityOperational $entityOperational): JsonResponse
    {
        $entityOperational->update($request->validated());
        $entityOperational->load(['entity', 'operationalCategory', 'businessType', 'commodity']);

        return $this->success(new EntityOperationalResource($entityOperational), 'Entity operational berhasil diperbarui');
    }

    public function destroy(EntityOperational $entityOperational): JsonResponse
    {
        // commodity ikut terhapus otomatis (cascadeOnDelete di migration)
        $entityOperational->delete();

        return $this->success(null, 'Entity operational berhasil dihapus, beserta data komoditas terkait (jika ada)');
    }
}