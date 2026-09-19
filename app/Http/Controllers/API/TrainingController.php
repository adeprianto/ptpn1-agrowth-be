<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class TrainingController extends Controller
{
    public function index() {
        $training = Training::all();
        return response()->json([
            'success' => true,
            'message' => 'List pelatihan berhasil ditampilkan',
            'data' => $training,
        ], Response::HTTP_OK);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'vendor_id' => 'required',
            'nama' => 'required',
            'jenis_psdm' => 'required',
            'kompetensi' => 'required',
            'bidang' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $training = Training::create([
            'vendor_id' => $request->vendor_id,
            'nama' => $request->nama,
            'jenis_psdm' => $request->jenis_psdm,
            'kompetensi' => $request->kompetensi,
            'bidang' => $request->bidang,
            'deskripsi' => $request->deskripsi,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data pelatihan berhasil ditambahkan',
            'data' => $training,
        ], Response::HTTP_CREATED);
    }

    public function show(string $id) {
        $training = Training::where('id', $id)->first();

        if (is_null($training)) {
            return response()->json([
                'success' => true,
                'message' => 'Data pelatihan tidak ditemukan',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data pelatihan berhasil ditemukan',
            'data' => $training,
        ], Response::HTTP_OK);
    }

    public function update(Request $request, string $id) {
        $training = Training::where('id', $id)->first();

        if (is_null($training)) {
            return response()->json([
                'success' => true,
                'message' => 'Data pelatihan tidak ditemukan',
            ], Response::HTTP_NOT_FOUND);
        }

        $training->vendor_id = $request->vendor_id ?? $training->vendor_id;
        $training->nama = $request->nama ?? $training->nama;
        $training->jenis_psdm = $request->jenis_psdm ?? $training->jenis_psdm;
        $training->kompetensi = $request->kompetensi ?? $training->kompetensi;
        $training->bidang = $request->bidang ?? $training->bidang;
        $training->deskripsi = $request->deskripsi ?? $training->deskripsi;
        $training->save();

        return response()->json([
            'success' => true,
            'message' => 'Data pelatihan berhasil diubah',
            'data' => $training,
        ], Response::HTTP_OK);
    }

    public function destroy(string $id) {
        $training = Training::where('id', $id)->first();

        if (is_null($training)) {
            return response()->json([
                'success' => true,
                'message' => 'Data pelatihan tidak ditemukan',
            ], Response::HTTP_NOT_FOUND);
        }

        $training->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data pelatihan berhasil dihapus',
        ], Response::HTTP_OK);
    }
}
