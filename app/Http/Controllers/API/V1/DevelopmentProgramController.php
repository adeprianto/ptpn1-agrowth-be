<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DevelopmentProgramResource;
use App\Models\DevelopmentPrograms;
use App\Models\ProgramBatches;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DevelopmentProgramController extends Controller
{
    use ApiResponse;

    // GET /api/v1/development-programs — daftar program beserta ringkasan angkanya
    public function index(Request $request): JsonResponse
    {
        // Angkatan per program dihitung dari angkatan yang boleh dilihat akun.
        $batches = ProgramBatches::query()
            ->select('program_batches.development_program_id')
            ->withTotals()
            ->visibleTo($request->user()->accessibleEntityIds())
            ->get()
            ->groupBy('development_program_id');

        $programs = DevelopmentPrograms::query()->orderBy('id')->get()
            ->each(function (DevelopmentPrograms $program) use ($batches) {
                $rows = $batches->get($program->id, collect());

                $program->batches_count = $rows->count();
                $program->participants_count = (int) $rows->sum('participants_count');
                $program->total_cost = (int) $rows->sum('total_cost');
            });

        return $this->success(
            DevelopmentProgramResource::collection($programs),
            'Daftar program pengembangan berhasil diambil'
        );
    }
}
