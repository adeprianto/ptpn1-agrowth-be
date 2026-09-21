<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employees;
use App\Models\Entities;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeadOfficeController extends Controller
{
    use ApiResponse;

    // GET /api/v1/head-office - kartu hub & halaman detail Head Office
    public function show(Request $request): JsonResponse
    {
        $headOffice = Entities::where('type', 'HEAD_OFFICE')->first();

        abort_unless($headOffice !== null, 404, 'Entity Head Office belum ada.');

        abort_unless(
            in_array($headOffice->id, $request->user()->accessibleEntityIds(), true),
            403,
            'Anda tidak memiliki akses ke data Head Office.'
        );

        $regionalIds = Entities::where('type', 'REGIONAL')
            ->where('parent_id', $headOffice->id)
            ->pluck('id');

        $unitCount = Entities::where('type', 'UNIT')->whereIn('parent_id', $regionalIds)->count();

        return $this->success([
            'id' => $headOffice->id,
            'code' => $headOffice->code,
            'name' => $headOffice->name,
            'status' => $headOffice->status,
            // pegawai yang ditempatkan langsung di Head Office
            'jumlah_karyawan' => Employees::where('entity_id', $headOffice->id)->count(),
            'jumlah_regional' => $regionalIds->count(),
            'jumlah_unit' => $unitCount,
        ], 'Detail Head Office berhasil diambil');
    }
}
