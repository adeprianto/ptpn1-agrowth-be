<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Training\StoreTrainingRequest;
use App\Http\Requests\Training\UpdateTrainingRequest;
use App\Http\Resources\TrainingResource;
use App\Models\Trainings;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Trainings::with('organizer')->withCount('realizations');

        foreach (['activity_type', 'learning_sector', 'learning_type', 'organizer_id'] as $filter) {
            if ($value = $request->query($filter)) {
                $query->where($filter, $value);
            }
        }

        if ($request->filled('is_ptpn_group')) {
            $isPtpn = $request->boolean('is_ptpn_group');
            $query->whereHas('organizer', fn ($q) => $q->where('is_ptpn_group', $isPtpn));
        }

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $trainings = $query->orderBy('name')->paginate($request->integer('per_page', 20));

        return $this->success(TrainingResource::collection($trainings), 'Daftar pelatihan berhasil diambil');
    }

    public function store(StoreTrainingRequest $request): JsonResponse
    {
        $training = Trainings::create($request->validated());
        $training->load('organizer');

        return $this->success(new TrainingResource($training), 'Pelatihan berhasil dibuat', 201);
    }

    public function show(Trainings $training): JsonResponse
    {
        $training->load('organizer')->loadCount('realizations');

        return $this->success(new TrainingResource($training), 'Detail pelatihan berhasil diambil');
    }

    public function update(UpdateTrainingRequest $request, Trainings $training): JsonResponse
    {
        $training->update($request->validated());
        $training->load('organizer');

        return $this->success(new TrainingResource($training), 'Pelatihan berhasil diperbarui');
    }

    public function destroy(Trainings $training): JsonResponse
    {
        if ($training->realizations()->exists()) {
            return $this->error('Pelatihan tidak bisa dihapus karena sudah memiliki data realisasi', 409);
        }

        $training->delete();

        return $this->success(null, 'Pelatihan berhasil dihapus');
    }
}