<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationType\StoreOrganizationTypeRequest;
use App\Http\Requests\OrganizationType\UpdateOrganizationTypeRequest;
use App\Http\Resources\OrganizationTypeResource;
use App\Models\OrganizationType;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationTypeController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = OrganizationType::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
            });
        }

        $organizationType = $query->orderBy('code')->get();

        return $this->success(OrganizationTypeResource::collection($organizationType), 'Daftar tipe organisasi berhasil diambil');
    }

    public function store(StoreOrganizationTypeRequest $request): JsonResponse
    {
        $organizationType = OrganizationType::create($request->validated());

        return $this->success(new OrganizationTypeResource($organizationType), 'Tipe organisasi berhasil dibuat', 201);
    }

    public function show(OrganizationType $organizationType): JsonResponse
    {
        return $this->success(new OrganizationTypeResource($organizationType), 'Detail tipe organisasi berhasil diambil');
    }

    public function update(UpdateOrganizationTypeRequest $request, OrganizationType $organizationType): JsonResponse
    {
        $organizationType->update($request->validated());

        return $this->success(new OrganizationTypeResource($organizationType), 'Tipe organisasi berhasil diperbarui');
    }

    public function destroy(OrganizationType $organizationType): JsonResponse
    {
        try {
            $organizationType->delete();
        } catch (QueryException $e) {
            // organizations.organization_type_id pakai restrictOnDelete()
            return $this->error('Tipe organisasi tidak bisa dihapus karena masih dipakai oleh struktur organisasi', 409);
        }

        return $this->success(null, 'Tipe organisasi berhasil dihapus');
    }
}
