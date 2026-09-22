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

    // GET /api/v1/trainings
    public function index(Request $request): JsonResponse
    {
        $query = Trainings::with('vendor')->withCount('realizations');

        foreach (['vendor_id', 'hr_development_type', 'competency_type', 'learning_sector'] as $filter) {
            if ($value = $request->query($filter)) {
                $query->where($filter, $value);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }

        if ($request->filled('is_lpp')) {
            $isLpp = $request->boolean('is_lpp');
            $query->whereHas('vendor', fn ($q) => $q->where('is_lpp', $isLpp));
        }

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $trainings = $query->orderBy('name')->paginate($request->integer('per_page', 20));

        return $this->successPaginated($trainings, TrainingResource::class, 'Daftar pelatihan berhasil diambil');
    }

    // POST /api/v1/trainings
    public function store(StoreTrainingRequest $request): JsonResponse
    {
        $training = Trainings::create($request->validated());
        $training->load('vendor');

        return $this->success(new TrainingResource($training), 'Pelatihan berhasil dibuat', 201);
    }

    // GET /api/v1/trainings/{training}
    public function show(Trainings $training): JsonResponse
    {
        $training->load('vendor')->loadCount('realizations');

        return $this->success(new TrainingResource($training), 'Detail pelatihan berhasil diambil');
    }

    // PUT /api/v1/trainings/{training}
    public function update(UpdateTrainingRequest $request, Trainings $training): JsonResponse
    {
        $training->update($request->validated());
        $training->load('vendor')->loadCount('realizations');

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

        $training->delete();

        return $this->success(null, 'Pelatihan berhasil dihapus');
    }
}
