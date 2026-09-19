<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrainingRealization\StoreTrainingRealizationRequest;
use App\Http\Requests\TrainingRealization\UpdateTrainingRealizationRequest;
use App\Http\Resources\TrainingRealizationResource;
use App\Models\TrainingRealizations;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingRealizationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = TrainingRealizations::with('training.organizer');

        if ($trainingId = $request->query('training_id')) {
            $query->where('training_id', $trainingId);
        }

        if ($startDate = $request->query('start_date')) {
            $query->whereDate('training_start_date', '>=', $startDate);
        }

        if ($endDate = $request->query('end_date')) {
            $query->whereDate('training_end_date', '<=', $endDate);
        }

        // hanya realisasi yang punya peserta dari entity yang boleh diakses user
        $accessibleIds = $request->user()->accessibleEntityIds();
        $query->whereHas('details', fn ($q) => $q->whereIn('entity_id', $accessibleIds));

        if ($search = $request->query('search')) {
            $query->where('training_name', 'like', "%{$search}%");
        }

        $realizations = $query
            ->orderByDesc('training_start_date')
            ->paginate($request->integer('per_page', 20));

        return $this->success(
            TrainingRealizationResource::collection($realizations),
            'Daftar realisasi pelatihan berhasil diambil'
        );
    }

    public function store(StoreTrainingRealizationRequest $request): JsonResponse
    {
        $realization = TrainingRealizations::create($request->validated());
        $realization->load('training.organizer');

        return $this->success(
            new TrainingRealizationResource($realization),
            'Realisasi pelatihan berhasil dibuat',
            201
        );
    }

    public function show(TrainingRealizations $trainingRealization): JsonResponse
    {
        $trainingRealization->load(['training.organizer', 'details']);

        return $this->success(
            new TrainingRealizationResource($trainingRealization),
            'Detail realisasi pelatihan berhasil diambil'
        );
    }

    public function update(
        UpdateTrainingRealizationRequest $request,
        TrainingRealizations $trainingRealization
    ): JsonResponse {
        $trainingRealization->update($request->validated());
        $trainingRealization->load('training.organizer');

        return $this->success(
            new TrainingRealizationResource($trainingRealization),
            'Realisasi pelatihan berhasil diperbarui'
        );
    }

    public function destroy(TrainingRealizations $trainingRealization): JsonResponse
    {
        // detail ikut terhapus lewat cascadeOnDelete
        $trainingRealization->delete();

        return $this->success(null, 'Realisasi pelatihan berhasil dihapus');
    }
}