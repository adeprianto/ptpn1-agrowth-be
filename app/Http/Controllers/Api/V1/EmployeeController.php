<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\Employees;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Employees::with([
            'positionTitle.jobGroup',
            'positionTitle.jobFunction',
            'entity:id,code,name',
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
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('nama')->paginate($request->integer('per_page', 20));

        return $this->success(EmployeeResource::collection($employees), 'Daftar pegawai berhasil diambil');
    }

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
            'entity',
            'entityOperational.businessType',
        ]);

        return $this->success(new EmployeeResource($employee), 'Detail pegawai berhasil diambil');
    }
}