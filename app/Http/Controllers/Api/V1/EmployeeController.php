<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employees;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    use ApiResponse;

    // GET /api/v1/employees
    public function index(Request $request): JsonResponse
    {
        $query = Employees::with([
            'positionTitle.jobGroup',
            'positionTitle.jobFunction',
            'entity:id,parent_id,type,code,name',
            'entity.parent:id,type,code,name',
            'entityOperational.businessType',
        ])->whereIn('entity_id', $request->user()->accessibleEntityIds());

        if ($entityId = $request->query('entity_id')) {
            $query->where('entity_id', $entityId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // filter turunan lewat jabatan
        if ($jobGroupId = $request->query('job_group_id')) {
            $query->whereHas('positionTitle', fn ($q) => $q->where('job_group_id', $jobGroupId));
        }

        if ($jobFunctionId = $request->query('job_function_id')) {
            $query->whereHas('positionTitle', fn ($q) => $q->where('job_function_id', $jobFunctionId));
        }

        if ($levelBod = $request->query('level_bod')) {
            $query->whereHas('positionTitle', fn ($q) => $q->where('level_bod', $levelBod));
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('name')->paginate($request->integer('per_page', 20));

        return $this->successPaginated($employees, EmployeeResource::class, 'Daftar pegawai berhasil diambil');
    }

    // GET /api/v1/employees/summary - 4 card di atas tabel (total + per tipe penempatan)
    public function summary(Request $request): JsonResponse
    {
        $counts = Employees::query()
            ->join('entities', 'entities.id', '=', 'employees.entity_id')
            ->whereIn('employees.entity_id', $request->user()->accessibleEntityIds())
            ->selectRaw('entities.type, count(*) as total')
            ->groupBy('entities.type')
            ->pluck('total', 'type');

        return $this->success([
            'total_karyawan' => (int) $counts->sum(),
            'total_head_office' => (int) ($counts['HEAD_OFFICE'] ?? 0),
            'total_regional' => (int) ($counts['REGIONAL'] ?? 0),
            'total_unit' => (int) ($counts['UNIT'] ?? 0),
        ], 'Ringkasan pegawai berhasil diambil');
    }

    // GET /api/v1/employees/{employee}
    public function show(Employees $employee): JsonResponse
    {
        abort_unless(
            in_array($employee->entity_id, request()->user()->accessibleEntityIds(), true),
            403,
            'Anda tidak memiliki akses ke data pegawai ini.'
        );

        $employee->load([
            'positionTitle.jobGroup',
            'positionTitle.jobFunction',
            'entity.parent',
            'entityOperational.businessType',
            'trainingRealizationDetails' => fn ($q) => $q->orderByDesc('training_start_date'),
            'trainingRealizationDetails.realization:id,training_name',
            'trainingRealizationDetails.training.organizer',
        ]);

        return $this->success(new EmployeeResource($employee), 'Detail pegawai berhasil diambil');
    }
}
