<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompetencyGroup\StoreCompetencyGroupRequest;
use App\Http\Requests\CompetencyGroup\UpdateCompetencyGroupRequest;
use App\Http\Resources\CompetencyGroupResource;
use App\Models\CompetencyGroups;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CompetencyGroupController extends Controller
{
    use ApiResponse;

    // GET /api/v1/competency-groups — semua rumpun (jumlahnya puluhan, tanpa halaman)
    public function index(): JsonResponse
    {
        $groups = CompetencyGroups::query()
            ->withCount('competencies')
            ->orderBy('name')
            ->get();

        return $this->success(CompetencyGroupResource::collection($groups), 'Daftar rumpun kompetensi berhasil diambil');
    }

    // POST /api/v1/competency-groups
    public function store(StoreCompetencyGroupRequest $request): JsonResponse
    {
        $group = CompetencyGroups::create($request->validated())->loadCount('competencies');

        return $this->success(new CompetencyGroupResource($group), 'Rumpun kompetensi berhasil ditambahkan', 201);
    }

    // PUT /api/v1/competency-groups/{competencyGroup}
    public function update(UpdateCompetencyGroupRequest $request, CompetencyGroups $competencyGroup): JsonResponse
    {
        $competencyGroup->update($request->validated());

        return $this->success(
            new CompetencyGroupResource($competencyGroup->loadCount('competencies')),
            'Rumpun kompetensi berhasil diperbarui'
        );
    }

    // DELETE /api/v1/competency-groups/{competencyGroup} — hanya rumpun yang sudah kosong
    public function destroy(CompetencyGroups $competencyGroup): JsonResponse
    {
        if ($competencyGroup->competencies()->exists()) {
            return $this->error(
                'Rumpun ini masih memiliki kompetensi. Pindahkan atau hapus kompetensinya terlebih dahulu.',
                409
            );
        }

        $competencyGroup->delete();

        return $this->success(null, 'Rumpun kompetensi berhasil dihapus');
    }
}
