<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Competency\StoreCompetencyRequest;
use App\Http\Requests\Competency\UpdateCompetencyRequest;
use App\Http\Resources\CompetencyResource;
use App\Models\Competencies;
use App\Traits\ApiResponse;
use App\Traits\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompetencyController extends Controller
{
    use ApiResponse;
    use ListQuery;

    /** Kolom yang boleh dipakai mengurutkan (nama dari frontend -> kolom database). */
    private const SORTABLE = [
        'code' => 'competencies.code',
        'name' => 'competencies.name',
        // id kolom Rumpun di tabel frontend sama dengan nama parameter filternya
        'competency_group_id' => 'competency_groups.name',
        'skills_count' => 'skills_count',
        'knowledge_count' => 'knowledge_count',
    ];

    // GET /api/v1/competencies
    public function index(Request $request): JsonResponse
    {
        $query = Competencies::query()
            ->select('competencies.*')
            ->join('competency_groups', 'competency_groups.id', '=', 'competencies.competency_group_id')
            ->with('group')
            ->withCount(['skills', 'knowledge']);

        // Daftar centang rumpun di modal filter — boleh lebih dari satu.
        $this->applyInFilter($query, $request, 'competency_group_id', 'competencies.competency_group_id');

        // Kotak cari di bawah judul kolom.
        $this->applyLike($query, $request, 'code', 'competencies.code');

        // Nama dicari di nama Indonesia maupun Inggrisnya.
        $name = trim((string) $request->query('name', ''));
        if ($name !== '') {
            $query->where(fn ($q) => $q->where('competencies.name', 'like', "%{$name}%")
                ->orWhere('competencies.name_en', 'like', "%{$name}%"));
        }

        // Pencarian umum: kode, nama, definisi, atau salah satu skill/knowledge/spesialisasi.
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('competencies.code', 'like', "%{$search}%")
                    ->orWhere('competencies.name', 'like', "%{$search}%")
                    ->orWhere('competencies.name_en', 'like', "%{$search}%")
                    ->orWhere('competencies.definition', 'like', "%{$search}%")
                    ->orWhereHas('items', fn ($item) => $item->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('sort')) {
            $this->applySort($query, $request, self::SORTABLE, 'competencies.code');
        } else {
            $query->orderBy('competency_groups.name')->orderBy('competencies.code');
        }

        $competencies = $query->paginate($request->integer('per_page', 20));

        return $this->successPaginated($competencies, CompetencyResource::class, 'Daftar kompetensi berhasil diambil');
    }

    // POST /api/v1/competencies — kompetensi + skill/knowledge/spesialisasi + level 1-6 sekaligus
    public function store(StoreCompetencyRequest $request): JsonResponse
    {
        $competency = DB::transaction(function () use ($request) {
            $competency = Competencies::create($request->competencyAttributes());
            $competency->syncItems($request->itemLists());
            $competency->syncLevels($request->levelDescriptions());

            return $competency;
        });

        return $this->success($this->detail($competency), 'Kompetensi berhasil ditambahkan', 201);
    }

    // GET /api/v1/competencies/{competency}
    public function show(Competencies $competency): JsonResponse
    {
        return $this->success($this->detail($competency), 'Detail kompetensi berhasil diambil');
    }

    // PUT /api/v1/competencies/{competency} — skill/knowledge/spesialisasi lama diganti daftar baru
    public function update(UpdateCompetencyRequest $request, Competencies $competency): JsonResponse
    {
        DB::transaction(function () use ($request, $competency) {
            $competency->update($request->competencyAttributes());
            $competency->syncItems($request->itemLists());
            $competency->syncLevels($request->levelDescriptions());
        });

        return $this->success($this->detail($competency), 'Kompetensi berhasil diperbarui');
    }

    // DELETE /api/v1/competencies/{competency} — butir & levelnya ikut terhapus (cascade)
    public function destroy(Competencies $competency): JsonResponse
    {
        if ($competency->positionTitles()->exists()) {
            return $this->error(
                'Kompetensi ini masih dipakai jabatan. Lepaskan dari jabatan tersebut terlebih dahulu.',
                409
            );
        }

        $competency->delete();

        return $this->success(null, 'Kompetensi berhasil dihapus');
    }

    private function detail(Competencies $competency): CompetencyResource
    {
        return new CompetencyResource($competency->load(['group', 'items', 'levels']));
    }
}
