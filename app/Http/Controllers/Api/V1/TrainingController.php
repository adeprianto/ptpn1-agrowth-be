<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Training\StoreTrainingRequest;
use App\Http\Requests\Training\UpdateTrainingRequest;
use App\Http\Resources\TrainingResource;
use App\Models\Trainings;
use App\Models\TrainingTags;
use App\Traits\ApiResponse;
use App\Traits\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainingController extends Controller
{
    use ApiResponse;
    use ListQuery;

    /** Kolom yang boleh dipakai mengurutkan (nama dari frontend -> kolom database). */
    private const SORTABLE = [
        'name' => 'trainings.name',
        'vendor' => 'vendors.name',
        'hr_development_type' => 'trainings.hr_development_type',
        'competency_type' => 'trainings.competency_type',
        'learning_sector' => 'trainings.learning_sector',
        'status' => 'trainings.status',
    ];

    // GET /api/v1/trainings
    public function index(Request $request): JsonResponse
    {
        // `vendors` di-join supaya kolom Penyelenggara bisa dicari dan diurutkan
        // lewat nama vendornya, bukan cuma id.
        $query = Trainings::query()
            ->select('trainings.*')
            ->leftJoin('vendors', 'vendors.id', '=', 'trainings.vendor_id')
            ->with(['vendor', 'tags'])
            ->withCount('realizations');

        // Kotak cari di bawah judul kolom.
        $this->applyLike($query, $request, 'name', 'trainings.name');
        $this->applyLike($query, $request, 'vendor', 'vendors.name');

        // Daftar centang di modal filter — boleh lebih dari satu nilai.
        $this->applyInFilter($query, $request, 'vendor_id', 'trainings.vendor_id');
        $this->applyInFilter($query, $request, 'hr_development_type', 'trainings.hr_development_type');
        $this->applyInFilter($query, $request, 'competency_type', 'trainings.competency_type');
        $this->applyInFilter($query, $request, 'learning_sector', 'trainings.learning_sector');

        // `status` dibaca lewat queryList, bukan `if ($status = ...)`, karena
        // status "0" (non-aktif) bernilai falsy dan akan terlewat.
        $statuses = $this->queryList($request, 'status');
        if ($statuses !== []) {
            $query->whereIn('trainings.status', array_map(
                static fn ($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                $statuses,
            ));
        }

        if ($request->filled('is_lpp')) {
            $isLpp = $request->boolean('is_lpp');
            $query->whereHas('vendor', fn ($q) => $q->where('is_lpp', $isLpp));
        }

        // Pencarian umum: nama pelatihan atau salah satu tagnya.
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('trainings.name', 'like', "%{$search}%")
                    ->orWhereHas('tags', fn ($tag) => $tag->where('name', 'like', "%{$search}%"));
            });
        }

        // Kotak cari di bawah judul kolom Tag.
        $this->applyTagSearch($query, $request);
        // Daftar centang di modal filter kolom Tag.
        $this->applyTagFilter($query, $request);

        $this->applySort($query, $request, self::SORTABLE, 'trainings.name');

        $trainings = $query->paginate($request->integer('per_page', 20));

        return $this->successPaginated($trainings, TrainingResource::class, 'Daftar pelatihan berhasil diambil');
    }

    // GET /api/v1/trainings/tags — isi daftar centang filter kolom Tag
    public function tags(): JsonResponse
    {
        $tags = TrainingTags::query()
            ->distinct()
            ->orderBy('name')
            ->pluck('name');

        return $this->success($tags, 'Daftar tag pelatihan berhasil diambil');
    }

    // POST /api/v1/trainings
    public function store(StoreTrainingRequest $request): JsonResponse
    {
        $training = DB::transaction(function () use ($request) {
            $training = Trainings::create($request->trainingAttributes());
            $training->syncTags($request->tags() ?? []);

            return $training;
        });

        $training->load(['vendor', 'tags']);

        return $this->success(new TrainingResource($training), 'Pelatihan berhasil dibuat', 201);
    }

    // GET /api/v1/trainings/{training}
    public function show(Trainings $training): JsonResponse
    {
        $training->load(['vendor', 'tags'])->loadCount('realizations');

        return $this->success(new TrainingResource($training), 'Detail pelatihan berhasil diambil');
    }

    // PUT /api/v1/trainings/{training}
    public function update(UpdateTrainingRequest $request, Trainings $training): JsonResponse
    {
        DB::transaction(function () use ($request, $training) {
            $training->update($request->trainingAttributes());

            // `null` berarti field `tags` tidak dikirim — tag lama dibiarkan.
            $tags = $request->tags();
            if ($tags !== null) {
                $training->syncTags($tags);
            }
        });

        $training->load(['vendor', 'tags'])->loadCount('realizations');

        return $this->success(new TrainingResource($training), 'Pelatihan berhasil diperbarui');
    }

    // DELETE /api/v1/trainings/{training}
    public function destroy(Trainings $training): JsonResponse
    {
        if ($training->realizations()->exists()) {
            return $this->error(
                'Pelatihan tidak bisa dihapus karena sudah memiliki data realisasi. Nonaktifkan saja lewat Edit.',
                409
            );
        }

        // Baris di `training_tags` ikut terhapus lewat foreign key cascade.
        $training->delete();

        return $this->success(null, 'Pelatihan berhasil dihapus');
    }

    /**
     * Kotak cari kolom Tag: pelatihan lolos kalau salah satu tagnya
     * mengandung kata kunci.
     */
    private function applyTagSearch($query, Request $request): void
    {
        $keyword = trim((string) $request->query('tag', ''));

        if ($keyword !== '') {
            $query->whereHas('tags', fn ($q) => $q->where('name', 'like', "%{$keyword}%"));
        }
    }

    /**
     * Daftar centang kolom Tag: pelatihan lolos kalau punya salah satu tag
     * yang dicentang. Menerima `tags[]=a&tags[]=b`.
     */
    private function applyTagFilter($query, Request $request): void
    {
        $tags = $this->queryList($request, 'tags');

        if ($tags !== []) {
            $query->whereHas('tags', fn ($q) => $q->whereIn('name', $tags));
        }
    }
}
