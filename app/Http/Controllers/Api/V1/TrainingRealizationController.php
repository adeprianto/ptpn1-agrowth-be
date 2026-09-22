<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrainingRealization\StoreTrainingRealizationRequest;
use App\Http\Requests\TrainingRealization\UpdateTrainingRealizationRequest;
use App\Http\Resources\TrainingRealizationResource;
use App\Models\TrainingRealizations;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingRealizationController extends Controller
{
    use ApiResponse;

    // GET /api/v1/training-realizations
    public function index(Request $request): JsonResponse
    {
        $query = TrainingRealizations::with('training.vendor')->withCount('details');

        if ($trainingId = $request->query('training_id')) {
            $query->where('training_id', $trainingId);
        }

        foreach (['year', 'month', 'learning_method', 'financing_category', 'cost_allocation'] as $filter) {
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

    // POST /api/v1/training-realizations
    public function store(StoreTrainingRealizationRequest $request): JsonResponse
    {
        // kolom total_* tidak ikut dikirim: nilainya nol sampai ada detail peserta
        $realization = TrainingRealizations::create($request->validated());
        $realization->load('training.vendor')->loadCount('details');

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
            'details.employee.positionTitle',
            'details.employee.entity',
        ])->loadCount('details');

        return $this->success(
            new TrainingRealizationResource($trainingRealization),
            'Detail realisasi pelatihan berhasil diambil'
        );
    }

    // PUT /api/v1/training-realizations/{trainingRealization}
    public function update(
        UpdateTrainingRealizationRequest $request,
        TrainingRealizations $trainingRealization
    ): JsonResponse {
        $trainingRealization->update($request->validated());
        $trainingRealization->load('training.vendor')->loadCount('details');

        return $this->success(
            new TrainingRealizationResource($trainingRealization),
            'Realisasi pelatihan berhasil diperbarui'
        );
    }

    // DELETE /api/v1/training-realizations/{trainingRealization}
    public function destroy(TrainingRealizations $trainingRealization): JsonResponse
    {
        if ($trainingRealization->details()->exists()) {
            return $this->error(
                'Realisasi tidak bisa dihapus karena masih memiliki peserta. Hapus dulu daftar pesertanya.',
                409
            );
        }

        $trainingRealization->delete();

        return $this->success(null, 'Realisasi pelatihan berhasil dihapus');
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
