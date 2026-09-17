<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessType\StoreBusinessTypeRequest;
use App\Http\Requests\BusinessType\UpdateBusinessTypeRequest;
use App\Http\Resources\BusinessTypeResource;
use App\Models\BusinessTypes;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessTypeController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = BusinessTypes::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $query->orderBy('name');

        $businessTypes = $query->paginate((int) $request->input('per_page', 15));

        return $this->successPaginated($businessTypes, BusinessTypeResource::class, 'Data business type berhasil diambil');
    }

    public function show(BusinessTypes $businessType): JsonResponse
    {
        return $this->success(new BusinessTypeResource($businessType), 'Detail business type berhasil diambil');
    }

    public function store(StoreBusinessTypeRequest $request): JsonResponse
    {
        $businessType = BusinessTypes::create($request->validated());

        return $this->success(new BusinessTypeResource($businessType), 'Business type berhasil dibuat', 201);
    }

    public function update(UpdateBusinessTypeRequest $request, BusinessTypes $businessType): JsonResponse
    {
        $businessType->update($request->validated());

        return $this->success(new BusinessTypeResource($businessType), 'Business type berhasil diperbarui');
    }

    public function destroy(BusinessTypes $businessType): JsonResponse
    {
        if ($businessType->entityOperationals()->exists()) {
            return $this->error(
                'Business type tidak bisa dihapus karena masih digunakan oleh data entity operational.',
                422
            );
        }

        $businessType->delete();

        return $this->success(null, 'Business type berhasil dihapus');
    }
}