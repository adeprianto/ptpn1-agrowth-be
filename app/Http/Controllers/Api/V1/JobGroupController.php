<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobFamily\StoreJobFamilyRequest;
use App\Http\Requests\JobFamily\UpdateJobFamilyRequest;
use App\Http\Requests\JobGroup\StoreJobGroupRequest;
use App\Http\Requests\JobGroup\UpdateJobGroupRequest;
use App\Http\Resources\JobFamilyResource;
use App\Http\Resources\JobGroupResource;
use App\Models\JobGroups;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobGroupController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = JobGroups::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
            });
        }

        $jobGroups = $query->orderBy('code')->get();

        return $this->success(JobGroupResource::collection($jobGroups), 'Daftar job family berhasil diambil');
    }

    public function store(StoreJobGroupRequest $request): JsonResponse
    {
        $jobGroup = JobGroups::create($request->validated());

        return $this->success(new JobGroupResource($jobGroup), 'Job Group berhasil dibuat', 201);
    }

    public function show(JobGroups $jobGroup): JsonResponse
    {
        return $this->success(new JobGroupResource($jobGroup), 'Detail job Group berhasil diambil');
    }

    public function update(UpdateJobGroupRequest $request, JobGroups $jobGroup): JsonResponse
    {
        $jobGroup->update($request->validated());

        return $this->success(new JobGroupResource($jobGroup), 'Job Group berhasil diperbarui');
    }

    public function destroy(JobGroups $jobGroup): JsonResponse
    {
        try {
            $jobGroup->delete();
        } catch (QueryException $e) {
            return $this->error('Job Group tidak bisa dihapus karena masih dipakai di position title lain', 409);
        }

        return $this->success(null, 'Job Group berhasil dihapus');
    }
}
