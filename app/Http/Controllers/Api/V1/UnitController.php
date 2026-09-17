<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UnitListResource;
use App\Models\Entities;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Entities::query()->where('type', 'UNIT')->with([
            'parent:id,code,name',
            'entityOperationals.operationalCategory',
            'entityOperationals.businessType',
        ]);

        if ($regionalId = $request->query('regional_id')) {
            $query->where('parent_id', $regionalId);
        }

        if ($operationalCategoryId = $request->query('operational_category_id')) {
            $query->whereHas('entityOperationals', function ($q) use ($operationalCategoryId) {
                $q->where('operational_category_id', $operationalCategoryId);
            });
        }

        if ($businessTypeId = $request->query('business_type_id')) {
            $query->whereHas('entityOperationals', function ($q) use ($businessTypeId) {
                $q->where('business_type_id', $businessTypeId);
            });
        }

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $units = $query->orderBy('name')->paginate($request->integer('per_page', 15));

        return $this->success(UnitListResource::collection($units),  'Daftar unit berhasil diambil');
    }

    public function summary(): JsonResponse
    {
        $totalUnit = Entities::where('type', 'UNIT')->count();

        $totalPabrik = Entities::where('type', 'UNIT')
        ->whereHas('entityOperationals.operationalCategory', fn ($q) => $q->where('code', 'PABRIK'))
        ->count();
        
        $totalKebun = Entities::where('type', 'UNIT')
        ->whereHas('entityOperationals.operationalCategory', fn ($q) => $q->where('code', 'KEBUN'))
        ->count();

        return $this->success([
            'total_unit' => $totalUnit,
            'total_pabrik' => $totalPabrik,
            'total_kebun' => $totalKebun,
            'total_karyawan' => 0,
        ], 'Ringkasan unit berhasil diambil');
    }
}
