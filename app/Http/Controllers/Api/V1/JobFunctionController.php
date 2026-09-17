<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobFunction\StoreJobFunctionRequest;
use App\Http\Requests\JobFunction\UpdateJobFunctionRequest;
use App\Http\Resources\JobFunctionResource;
use App\Models\JobFunctions;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobFunctionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = JobFunctions::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
            });
        }

        $jobFunctions = $query->orderBy('code')->get();

        return $this->success(JobFunctionResource::collection($jobFunctions), 'Daftar job function berhasil diambil');
    }

    public function store(StoreJobFunctionRequest $request): JsonResponse
    {
        $jobFunction = JobFunctions::create($request->validated());

        return $this->success(new JobFunctionResource($jobFunction), 'Job function berhasil dibuat', 201);
    }

    public function show(JobFunctions $jobFunction): JsonResponse
    {
        return $this->success(new JobFunctionResource($jobFunction), 'Detail job function berhasil diambil');
    }

    public function update(UpdateJobFunctionRequest $request, JobFunctions $jobFunction): JsonResponse
    {
        $jobFunction->update($request->validated());

        return $this->success(new JobFunctionResource($jobFunction), 'Job function berhasil diperbarui');
    }

    public function destroy(JobFunctions $jobFunction): JsonResponse
    {
        try {
            $jobFunction->delete();
        } catch (QueryException $e) {
            return $this->error('Job function tidak bisa dihapus karena masih dipakai di struktur organisasi lain', 409);
        }

        return $this->success(null, 'Job function berhasil dihapus');
    }
}
