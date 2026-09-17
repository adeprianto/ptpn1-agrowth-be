<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobFamily\StoreJobFamilyRequest;
use App\Http\Requests\JobFamily\UpdateJobFamilyRequest;
use App\Http\Resources\JobFamilyResource;
use App\Models\JobFamilies;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobFamilyController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = JobFamilies::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
            });
        }

        $jobFamilies = $query->orderBy('code')->get();

        return $this->success(JobFamilyResource::collection($jobFamilies), 'Daftar job family berhasil diambil');
    }

    public function store(StoreJobFamilyRequest $request): JsonResponse
    {
        $jobFamily = JobFamilies::create($request->validated());

        return $this->success(new JobFamilyResource($jobFamily), 'Job family berhasil dibuat', 201);
    }

    public function show(JobFamilies $jobFamily): JsonResponse
    {
        return $this->success(new JobFamilyResource($jobFamily), 'Detail job family berhasil diambil');
    }

    public function update(UpdateJobFamilyRequest $request, JobFamilies $jobFamily): JsonResponse
    {
        $jobFamily->update($request->validated());

        return $this->success(new JobFamilyResource($jobFamily), 'Job family berhasil diperbarui');
    }

    public function destroy(JobFamilies $jobFamily): JsonResponse
    {
        try {
            $jobFamily->delete();
        } catch (QueryException $e) {
            return $this->error('Job family tidak bisa dihapus karena masih dipakai di position title lain', 409);
        }

        return $this->success(null, 'Job family berhasil dihapus');
    }
}
