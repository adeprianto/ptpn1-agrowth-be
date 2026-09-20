<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrainingRealization\StoreDetailRequest;
use App\Http\Resources\TrainingRealizationDetailResource;
use App\Models\Employees;
use App\Models\TrainingRealizationDetails;
use App\Models\TrainingRealizations;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrainingRealizationDetailController extends Controller
{
    use ApiResponse;

    public function index(Request $request, TrainingRealizations $trainingRealization): JsonResponse
    {
        $details = $trainingRealization->details()
            ->whereIn('entity_id', $request->user()->accessibleEntityIds())
            ->orderBy('employee_name')
            ->get();

        return $this->success(
            TrainingRealizationDetailResource::collection($details),
            'Daftar peserta berhasil diambil'
        );
    }

    public function store(StoreDetailRequest $request, TrainingRealizations $trainingRealization): JsonResponse
    {
        $data = $request->validated();

        // isi otomatis snapshot dari data pegawai bila employee_id diberikan
        if (! empty($data['employee_id'])) {
            $employee = Employees::with(['positionTitle', 'entity.parent'])->find($data['employee_id']);

            if ($employee) {
                $data['employee_name'] ??= $employee->name;
                $data['position_title_id'] ??= $employee->position_title_id;
                $data['entity_id'] ??= $employee->entity_id;
                $data['employee_position'] ??= $employee->positionTitle?->name;
                $data['employee_bod_level'] ??= $employee->positionTitle?->level_bod?->value;
                $data['employee_unit'] ??= $employee->entity?->name;
                $data['employee_region'] ??= $employee->entity?->parent?->name;
            }
        }

        $data['training_id'] ??= $trainingRealization->training_id;
        $data['training_start_date'] ??= $trainingRealization->training_start_date?->toDateString();
        $data['training_end_date'] ??= $trainingRealization->training_end_date?->toDateString();

        $detail = DB::transaction(function () use ($trainingRealization, $data) {
            $detail = $trainingRealization->details()->create($data);
            $trainingRealization->recalculateTotals();

            return $detail;
        });

        return $this->success(
            new TrainingRealizationDetailResource($detail),
            'Peserta berhasil ditambahkan',
            201
        );
    }

    public function update(
        StoreDetailRequest $request,
        TrainingRealizations $trainingRealization,
        TrainingRealizationDetails $detail
    ): JsonResponse {
        abort_unless(
            $detail->training_realization_id === $trainingRealization->id,
            404,
            'Peserta tidak ditemukan pada realisasi ini.'
        );

        DB::transaction(function () use ($detail, $request, $trainingRealization) {
            $detail->update($request->validated());
            $trainingRealization->recalculateTotals();
        });

        return $this->success(
            new TrainingRealizationDetailResource($detail->fresh()),
            'Peserta berhasil diperbarui'
        );
    }

    public function destroy(
        TrainingRealizations $trainingRealization,
        TrainingRealizationDetails $detail
    ): JsonResponse {
        abort_unless(
            $detail->training_realization_id === $trainingRealization->id,
            404,
            'Peserta tidak ditemukan pada realisasi ini.'
        );

        DB::transaction(function () use ($detail, $trainingRealization) {
            $detail->delete();
            $trainingRealization->recalculateTotals();
        });

        return $this->success(null, 'Peserta berhasil dihapus');
    }
}