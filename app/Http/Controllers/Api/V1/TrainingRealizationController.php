<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrainingRealization\StoreTrainingRealizationRequest;
use App\Http\Requests\TrainingRealization\UpdateTrainingRealizationRequest;
use App\Http\Resources\TrainingRealizationResource;
use App\Models\Entities;
use App\Models\TrainingRealizations;
use App\Models\Trainings;
use App\Traits\ApiResponse;
use App\Traits\ListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainingRealizationController extends Controller
{
    use ApiResponse;
    use ListQuery;

    // GET /api/v1/training-realizations
    public function index(Request $request): JsonResponse
    {
        $query = TrainingRealizations::with(['training.vendor', 'costAllocationEntity'])->withCount('details');

        if ($trainingId = $request->query('training_id')) {
            $query->where('training_id', $trainingId);
        }

        foreach (['year', 'month', 'learning_method', 'financing_category', 'cost_allocation_entity_id'] as $filter) {
            if ($value = $request->query($filter)) {
                $query->where($filter, $value);
            }
        }

        if ($startDate = $request->query('start_date')) {
            $query->whereDate('start_date', '>=', $startDate);
        }

        if ($endDate = $request->query('end_date')) {
            $query->whereDate('end_date', '<=', $endDate);
        }

        if ($search = $request->query('search')) {
            $query->whereHas('training', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $this->filterByHrDevelopmentType($query, $request);
        $this->scopeToAccessibleEntities($query, $request);

        $realizations = $query
            ->orderByDesc('start_date')
            ->paginate($request->integer('per_page', 20));

        return $this->successPaginated(
            $realizations,
            TrainingRealizationResource::class,
            'Daftar realisasi pelatihan berhasil diambil'
        );
    }

    // GET /api/v1/training-realizations/summary
    public function summary(Request $request): JsonResponse
    {
        $query = TrainingRealizations::query();
        $this->filterByHrDevelopmentType($query, $request);
        $this->scopeToAccessibleEntities($query, $request);

        return $this->success([
            'total_cost' => (int) (clone $query)->sum('total_cost'),
            'total_learning_hours' => (int) (clone $query)->sum('total_duration_learning_hours'),
            'total_participants' => (int) (clone $query)->sum('total_participants'),
        ], 'Ringkasan realisasi pelatihan berhasil diambil');
    }

    // GET /api/v1/training-realizations/cost-allocations — pilihan dropdown Alokasi Biaya
    //
    // Sengaja tidak dibatasi cakupan akun: akun Regional pun boleh mencatat
    // biaya yang ditanggung Head Office atau regional lain.
    public function costAllocations(): JsonResponse
    {
        $entities = Entities::query()
            ->whereIn('type', ['HEAD_OFFICE', 'REGIONAL'])
            ->where('status', 'ACTIVE')
            // Head Office di urutan pertama, lalu Regional 1, 2, dst.
            ->orderByRaw("type = 'HEAD_OFFICE' desc")
            ->orderBy('code')
            ->get(['id', 'type', 'code', 'name']);

        return $this->success($entities, 'Pilihan alokasi biaya berhasil diambil');
    }

    // POST /api/v1/training-realizations — laporan + seluruh pesertanya sekaligus
    public function store(StoreTrainingRealizationRequest $request): JsonResponse
    {
        if ($locked = $this->rejectIfTrainingInactive(Trainings::find($request->validated('training_id')))) {
            return $locked;
        }

        $realization = DB::transaction(function () use ($request) {
            $realization = TrainingRealizations::create($request->realizationAttributes());
            $realization->syncDetails($request->participantDetails());

            return $realization;
        });

        $realization->load(['training.vendor', 'costAllocationEntity'])->loadCount('details');

        return $this->success(
            new TrainingRealizationResource($realization),
            'Realisasi pelatihan berhasil dibuat',
            201
        );
    }

    // GET /api/v1/training-realizations/{trainingRealization}
    public function show(TrainingRealizations $trainingRealization): JsonResponse
    {
        $trainingRealization->load([
            'training.vendor',
            'costAllocationEntity',
            'details.employee.positionTitle',
            'details.employee.entity.parent',
        ])->loadCount('details');

        return $this->success(
            new TrainingRealizationResource($trainingRealization),
            'Detail realisasi pelatihan berhasil diambil'
        );
    }

    // PUT /api/v1/training-realizations/{trainingRealization} — peserta lama diganti daftar baru
    public function update(
        UpdateTrainingRealizationRequest $request,
        TrainingRealizations $trainingRealization
    ): JsonResponse {
        if ($locked = $this->rejectIfTrainingInactive($trainingRealization->training)) {
            return $locked;
        }

        DB::transaction(function () use ($trainingRealization, $request) {
            $trainingRealization->update($request->realizationAttributes());
            $trainingRealization->syncDetails($request->participantDetails());
        });

        $trainingRealization->load(['training.vendor', 'costAllocationEntity'])->loadCount('details');

        return $this->success(
            new TrainingRealizationResource($trainingRealization),
            'Realisasi pelatihan berhasil diperbarui'
        );
    }

    // DELETE /api/v1/training-realizations/{trainingRealization} — pesertanya ikut terhapus
    public function destroy(TrainingRealizations $trainingRealization): JsonResponse
    {
        if ($locked = $this->rejectIfTrainingInactive($trainingRealization->training)) {
            return $locked;
        }

        DB::transaction(function () use ($trainingRealization) {
            $trainingRealization->details()->delete();
            $trainingRealization->delete();
        });

        return $this->success(null, 'Realisasi pelatihan berhasil dihapus');
    }

    /**
     * Laporan milik pelatihan non-aktif dikunci: tidak bisa ditambah, diubah,
     * maupun dihapus. Mengembalikan respons 409 kalau terkunci, atau null
     * kalau pelatihannya aktif dan proses boleh dilanjutkan.
     */
    private function rejectIfTrainingInactive(?Trainings $training): ?JsonResponse
    {
        if ($training && ! $training->status) {
            return $this->error(
                'Pelatihan ini non-aktif, sehingga laporannya tidak dapat ditambah, diubah, maupun dihapus.',
                409
            );
        }

        return null;
    }

    /**
     * Saring berdasarkan jenis pengembangan SDM milik pelatihannya.
     *
     * - `hr_development_type`         : hanya jenis ini (mis. halaman Program Budaya)
     * - `exclude_hr_development_type` : semua kecuali jenis ini (mis. halaman PSDM).
     *   Realisasi tanpa pelatihan tetap ikut di sini, supaya tidak hilang dari semua halaman.
     */
    private function filterByHrDevelopmentType(Builder $query, Request $request): void
    {
        $only = $this->queryList($request, 'hr_development_type');
        if ($only !== []) {
            $query->whereHas('training', fn ($q) => $q->whereIn('hr_development_type', $only));
        }

        $except = $this->queryList($request, 'exclude_hr_development_type');
        if ($except !== []) {
            $query->whereDoesntHave('training', fn ($q) => $q->whereIn('hr_development_type', $except));
        }
    }

    /**
     * Realisasi hanya boleh dilihat kalau ada peserta dari entity yang dicakup
     * akun ini. Realisasi yang belum punya peserta tetap ditampilkan supaya
     * data yang baru dibuat tidak langsung hilang dari daftar.
     */
    private function scopeToAccessibleEntities(Builder $query, Request $request): void
    {
        $accessibleIds = $request->user()->accessibleEntityIds();

        $query->where(function ($q) use ($accessibleIds) {
            $q->whereDoesntHave('details')
                ->orWhereHas('details.employee', fn ($e) => $e->whereIn('entity_id', $accessibleIds));
        });
    }
}
