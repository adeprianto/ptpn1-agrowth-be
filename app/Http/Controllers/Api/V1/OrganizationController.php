<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class OrganizationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Organization::with(['organizationType', 'entity', 'jobFunction', 'parent']);

        if ($entityId = $request->query('entity_id')) {
            $query->where('entity_id', $entityId);
        }

        if ($level = $request->query('level')) {
            $query->where('level', $level);
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->query('parent_id'));
        }

        if ($organizationTypeId = $request->query('organization_type_id')) {
            $query->where('organization_type_id', $organizationTypeId);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
            });
        }

        $organizations = $query->orderBy('level')->orderBy('name')->paginate($request->integer('per_page', 15));

        return $this->success(OrganizationResource::collection($organizations), 'Daftar stukrtur organisasi berhasil diambil.');
    }

    public function tree(Request $request): JsonResponse
    {
        $query = Organization::with(['organizationType', 'entity', 'jobFunction']);

        if ($entityId = $request->query('entity_id')) {
            $query->where('entity_id' ,$entityId);
        }

        $organizations = $query->orderBy('level')->orderBy('name')->get();

        return $this->success($this->buildTree($organizations, null), 'Struktur organisasi (tree) berhasil diambil.');
    }

    public function show(Organization $organization): JsonResponse
    {
        $organization->load(['organizationType', 'entity', 'jobFunction', 'parent'])
            ->loadCount('children');

        return $this->success(new OrganizationResource($organization), 'Detail organisasi berhasil diambil');
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $organization = Organization::create($request->validated());
        $organization->load(['organizationType', 'entity', 'jobFunction', 'parent']);

        return $this->success(new OrganizationResource($organization), 'Organisasi berhasil dibuat', 201);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        $organization->update($request->validated());
        $organization->load(['organizationType', 'entity', 'jobFunction', 'parent']);

        return $this->success(new OrganizationResource($organization), 'Organisasi berhasil diperbarui');
    }

    public function destroy(Organization $organization): JsonResponse
    {
        if ($organization->children()->exists()) {
            return $this->error('Organisasi tidak bisa dihapus karena masih memiliki sub-organisasi', 409);
        }

        if ($organization->positionTitles()->exists()) {
            return $this->error('Organisasi tidak bisa dihapus karena masih dipakai di position title', 409);
        }

        $organization->delete();

        return $this->success(null, 'Organisasi berhasil dihapus');
    }

    /**
     * Bangun tree secara in-memory dari collection flat (hindari N+1 query rekursif).
     */
    private function buildTree(Collection $organizations, ?int $parentId): array
    {
        return $organizations
            ->where('parent_id', $parentId)
            ->map(function (Organization $organization) use ($organizations) {
                return [
                    'id' => $organization->id,
                    'code' => $organization->code,
                    'nama' => $organization->nama,
                    'level' => $organization->level,
                    'organization_type' => $organization->organizationType ? [
                        'id' => $organization->organizationType->id,
                        'code' => $organization->organizationType->code,
                        'nama' => $organization->organizationType->nama,
                    ] : null,
                    'entity' => $organization->entity ? [
                        'id' => $organization->entity->id,
                        'code' => $organization->entity->code,
                        'nama' => $organization->entity->nama,
                    ] : null,
                    'job_function' => $organization->jobFunction ? [
                        'id' => $organization->jobFunction->id,
                        'code' => $organization->jobFunction->code,
                        'nama' => $organization->jobFunction->nama,
                    ] : null,
                    'children' => $this->buildTree($organizations, $organization->id),
                ];
            })->values()->all();
    }
}
