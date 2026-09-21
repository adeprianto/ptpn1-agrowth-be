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

    // GET /api/v1/organizations
    public function index(Request $request): JsonResponse
    {
        // dibatasi cakupan akun lewat entity pemilik departemen
        $query = Organization::with(['organizationType', 'entity', 'jobFunction', 'parent'])
            ->whereIn('entity_id', $request->user()->accessibleEntityIds());

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

        return $this->successPaginated($organizations, OrganizationResource::class, 'Daftar struktur organisasi berhasil diambil');
    }

    // GET /api/v1/organizations/tree - pohon departemen dalam satu entity
    public function tree(Request $request): JsonResponse
    {
        $query = Organization::with(['organizationType', 'jobFunction'])
            ->whereIn('entity_id', $request->user()->accessibleEntityIds());

        if ($entityId = $request->query('entity_id')) {
            $query->where('entity_id', $entityId);
        }

        $organizations = $query->orderBy('level')->orderBy('name')->get();

        return $this->success($this->buildTree($organizations, null), 'Struktur organisasi (tree) berhasil diambil.');
    }

    // GET /api/v1/organizations/{organization}
    public function show(Organization $organization): JsonResponse
    {
        $organization->load(['organizationType', 'entity', 'jobFunction', 'parent'])
            ->loadCount('children');

        return $this->success(new OrganizationResource($organization), 'Detail organisasi berhasil diambil');
    }

    // POST /api/v1/organizations
    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $data = $request->validated();

        abort_unless(
            in_array((int) $data['entity_id'], $request->user()->accessibleEntityIds(), true),
            403,
            'Anda tidak memiliki akses ke entity tersebut.'
        );

        $organization = Organization::create($data);
        $organization->load(['organizationType', 'entity', 'jobFunction', 'parent']);

        return $this->success(new OrganizationResource($organization), 'Organisasi berhasil dibuat', 201);
    }

    // PUT /api/v1/organizations/{organization}
    public function update(UpdateOrganizationRequest $request, Organization $organization): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['entity_id'])) {
            abort_unless(
                in_array((int) $data['entity_id'], $request->user()->accessibleEntityIds(), true),
                403,
                'Anda tidak memiliki akses ke entity tersebut.'
            );
        }

        $organization->update($data);
        $organization->load(['organizationType', 'entity', 'jobFunction', 'parent']);

        return $this->success(new OrganizationResource($organization), 'Organisasi berhasil diperbarui');
    }

    // DELETE /api/v1/organizations/{organization}
    public function destroy(Organization $organization): JsonResponse
    {
        if ($organization->children()->exists()) {
            return $this->error('Departemen tidak bisa dihapus karena masih memiliki sub-departemen', 409);
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
                    'name' => $organization->name,
                    'level' => $organization->level,
                    'organization_type' => $organization->organizationType ? [
                        'id' => $organization->organizationType->id,
                        'code' => $organization->organizationType->code,
                        'name' => $organization->organizationType->name,
                    ] : null,
                    'job_function' => $organization->jobFunction ? [
                        'id' => $organization->jobFunction->id,
                        'code' => $organization->jobFunction->code,
                        'name' => $organization->jobFunction->name,
                    ] : null,
                    'children' => $this->buildTree($organizations, $organization->id),
                ];
            })->values()->all();
    }
}
