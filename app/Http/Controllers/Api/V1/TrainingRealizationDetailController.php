<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrainingRealization\StoreDetailRequest;
use App\Http\Requests\TrainingRealization\UpdateDetailRequest;
use App\Http\Resources\TrainingRealizationDetailResource;
use App\Models\TrainingRealizationDetails;
use App\Models\TrainingRealizations;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainingRealizationDetailController extends Controller
{
    use ApiResponse;

    // GET /api/v1/training-realizations/{trainingRealization}/details
    public function index(Request $request, TrainingRealizations $trainingRealization): JsonResponse
    {
        $accessibleIds = $request->user()->accessibleEntityIds();

        $details = $trainingRealization->details()
            ->with(['employee.positionTitle', 'employee.entity'])
            ->whereHas('employee', fn ($q) => $q->whereIn('entity_id', $accessibleIds))
            ->join('employees', 'employees.id', '=', 'training_realization_details.employee_id')
            ->orderBy('employees.name')
            ->select('training_realization_details.*')
            ->paginate($request->integer('per_page', 20));

        return $this->successPaginated(
            $details,
            TrainingRealizationDetailResource::class,
            'Daftar peserta realisasi pelatihan berhasil diambil'
        );
    }

    // GET /api/v1/training-realizations/{trainingRealization}/details/{detail}
    public function show(
        TrainingRealizations $trainingRealization,
        TrainingRealizationDetails $detail
    ): JsonResponse {
        $this->ensureBelongsTo($trainingRealization, $detail);

        $detail->load(['employee.positionTitle', 'employee.entity']);

        return $this->success(
            new TrainingRealizationDetailResource($detail),
            'Detail peserta berhasil diambil'
        );
    }

    // POST /api/v1/training-realizations/{trainingRealization}/details
    public function store(StoreDetailRequest $request, TrainingRealizations $trainingRealization): JsonResponse
    {
        $data = $this->withRealizationDefaults($request->validated(), $trainingRealization);

        $detail = DB::transaction(function () use ($trainingRealization, $data) {
            $detail = $trainingRealization->details()->create($data);
            $trainingRealization->recalculateTotals();

            return $detail;
        });

        $detail->load(['employee.positionTitle', 'employee.entity']);

        return $this->success(
            new TrainingRealizationDetailResource($detail),
            'Peserta berhasil ditambahkan',
            201
        );
    }

    // PUT /api/v1/training-realizations/{trainingRealization}/details/{detail}
    public function update(
        UpdateDetailRequest $request,
        TrainingRealizations $trainingRealization,
        TrainingRealizationDetails $detail
    ): JsonResponse {
        $this->ensureBelongsTo($trainingRealization, $detail);

        DB::transaction(function () use ($detail, $request, $trainingRealization) {
            $detail->update($request->validated());
            $trainingRealization->recalculateTotals();
        });

        $detail->refresh()->load(['employee.positionTitle', 'employee.entity']);

        return $this->success(
            new TrainingRealizationDetailResource($detail),
            'Peserta berhasil diperbarui'
        );
    }

    // DELETE /api/v1/training-realizations/{trainingRealization}/details/{detail}
    public function destroy(
        TrainingRealizations $trainingRealization,
        TrainingRealizationDetails $detail
    ): JsonResponse {
        $this->ensureBelongsTo($trainingRealization, $detail);

        DB::transaction(function () use ($detail, $trainingRealization) {
            $detail->delete();
            $trainingRealization->recalculateTotals();
        });

        return $this->success(null, 'Peserta berhasil dihapus');
    }

    /**
     * Periode peserta umumnya sama dengan realisasinya, jadi kalau tidak
     * dikirim client nilainya diambil dari realisasi induk.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withRealizationDefaults(array $data, TrainingRealizations $realization): array
    {
        $data['year'] ??= $realization->year;
        $data['month'] ??= $realization->month;
        $data['start_date'] ??= $realization->start_date?->toDateString();
        $data['end_date'] ??= $realization->end_date?->toDateString();
        $data['duration_days'] ??= $realization->duration_days;
        $data['learning_hours_per_day'] ??= $realization->learning_hours_per_day;

        return $data;
    }

    private function ensureBelongsTo(
        TrainingRealizations $realization,
        TrainingRealizationDetails $detail
    ): void {
        abort_unless(
            $detail->training_realization_id === $realization->id,
            404,
            'Peserta tidak ditemukan pada realisasi ini.'
        );
    }
}
