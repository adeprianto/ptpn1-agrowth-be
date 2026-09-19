<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PositionTitle\StorePositionTitleRequest;
use App\Http\Requests\PositionTitle\UpdatePositionTitleRequest;
use App\Http\Resources\PositionTitleResource;
use App\Models\PositionTitle;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PositionTitleController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = PositionTitle::with(['jobGroup', 'jobFunction']);
        
        if ($jobGroupId = $request->query('job_group_id')) {
            $query->where('job_group_id', $jobGroupId);
        }
        
        if ($jobFunctionId = $request->query('job_function_id')) {
            $query->where('job_function_id', $jobFunctionId);
        }
        
        if ($levelBod = $request->query('level_bod')) {
            $query->where('level_bod', $levelBod);
        }
        
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $postionTitles = $query->orderBy('name')->paginate($request->integer('per_page', 20));

        return $this->success(PositionTitleResource::collection($postionTitles), 'Daftar position title berhasil diambil');
    }

    public function store(StorePositionTitleRequest $request): JsonResponse
    {
        $positionTitle = PositionTitle::create($request->validated());
        $positionTitle->load(['jobGroup', 'jobFunction']);

        return $this->success(new PositionTitleResource($positionTitle), 'Position title berhasil dibuat', 201);
    }

    public function show(PositionTitle $positionTitle): JsonResponse
    {
        $positionTitle->load(['jobGroup', 'jobFunction']);

        return $this->success(new PositionTitleResource($positionTitle), 'Detail position title berhasil diambil');
    }

    public function update(UpdatePositionTitleRequest $request, PositionTitle $positionTitle): JsonResponse
    {
        $positionTitle->update($request->validated());
        $positionTitle->load(['jobGroup', 'jobFunction']);

        return $this->success(new PositionTitleResource($positionTitle), 'Position title berhasil diperbarui');
    }

    public function destroy(PositionTitle $positionTitle): JsonResponse
    {
        try {
            $positionTitle->delete();
        } catch (QueryException $e) {
            return $this->error('Position title tidak bisa dihapus karena masih dipakai di data lain', 409);
        }

        return $this->success(null, 'Position title berhasil dihapus');
    }
}
