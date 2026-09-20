<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commodity\UpsertCommodityRequest;
use App\Http\Resources\CommodityResource;
use App\Models\EntityOperational;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class CommodityController extends Controller
{
    use ApiResponse;

    // GET /entity-operationals/{entityOperational}/commodity
    public function show(EntityOperational $entityOperational): JsonResponse
    {
        $commodity = $entityOperational->commodity;

        if (!$commodity) {
            return $this->error('Data komoditas untuk entity operational ini belum diisi.', 404);
        }

        return $this->success(new CommodityResource($commodity), 'Detail komoditas berhasil diambil');
    }

    // PUT /entity-operationals/{entityOperational}/commodity — create kalau belum ada, update kalau sudah ada
    public function upsert(UpsertCommodityRequest $request, EntityOperational $entityOperational): JsonResponse
    {
        $commodity = $entityOperational->commodity()->updateOrCreate(
            ['entity_operational_id' => $entityOperational->id],
            $request->validated()
        );

        return $this->success(new CommodityResource($commodity), 'Data komoditas berhasil disimpan');
    }

    // DELETE /entity-operationals/{entityOperational}/commodity
    public function destroy(EntityOperational $entityOperational): JsonResponse
    {
        if (!$entityOperational->commodity) {
            return $this->error('Data komoditas untuk entity operational ini belum ada.', 404);
        }

        $entityOperational->commodity()->delete();

        return $this->success(null, 'Data komoditas berhasil dihapus');
    }
}