<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OperationalCategory\StoreOperationalCategoryRequest;
use App\Http\Requests\OperationalCategory\UpdateOperationalCategoryRequest;
use App\Http\Resources\OperationalCategoryResource;
use App\Models\OperationalCategories;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperationalCategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = OperationalCategories::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $query->orderBy('name');

        $categories = $query->paginate((int) $request->input('per_page', 15));

        return $this->successPaginated($categories, OperationalCategoryResource::class, 'Data operational category berhasil diambil');
    }

    public function show(OperationalCategories $operationalCategory): JsonResponse
    {
        return $this->success(new OperationalCategoryResource($operationalCategory), 'Detail operational category berhasil diambil');
    }

    public function store(StoreOperationalCategoryRequest $request): JsonResponse
    {
        $category = OperationalCategories::create($request->validated());

        return $this->success(new OperationalCategoryResource($category), 'Operational category berhasil dibuat', 201);
    }

    public function update(UpdateOperationalCategoryRequest $request, OperationalCategories $operationalCategory): JsonResponse
    {
        $operationalCategory->update($request->validated());

        return $this->success(new OperationalCategoryResource($operationalCategory), 'Operational category berhasil diperbarui');
    }

    public function destroy(OperationalCategories $operationalCategory): JsonResponse
    {
        if ($operationalCategory->entityOperationals()->exists()) {
            return $this->error(
                'Operational category tidak bisa dihapus karena masih digunakan oleh data entity operational.',
                422
            );
        }

        $operationalCategory->delete();

        return $this->success(null, 'Operational category berhasil dihapus');
    }
}