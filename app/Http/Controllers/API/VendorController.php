<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class VendorController extends Controller
{
    public function index() {
        $vendor = Vendor::all();
        return response()->json([
            'success' => true,
            'message' => 'List vendor berhasil ditampilkan',
            'data' => $vendor,
        ], Response::HTTP_OK);
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'klasifikasi' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], Response::HTTP_BAD_REQUEST);
        }

        $vendor = Vendor::create([
            'nama' => $request->nama,
            'klasifikasi' => $request->klasifikasi,
            'is_lpp' => $request->klasifikasi == 'lpp' ? 'true' : 'false',
            'telp' => $request->telp,
            'email' => $request->email,
            'website' => $request->website,
            'kota' => $request->kota,
            'alamat' => $request->alamat,
            'nama_pic' => $request->nama_pic,
            'telp_pic' => $request->telp_pic,
            'email_pic' => $request->email_pic,
            'jabatan_pic' => $request->jabatan_pic,
        ]);

        $vendor->save();

        return response()->json([
            'success' => true,
            'message' => 'Vendor berhasil ditambahkan',
            'data' => $vendor
        ], Response::HTTP_CREATED);
    }

    public function show(string $id) {
        $vendor = Vendor::where('id', $id)->first();

        if (is_null($vendor)) {
            return response()->json([
                'success' => true,
                'message' => 'Vendor tidak ditemukan',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Vendor berhasil ditemukan',
            'data' => $vendor,
        ], Response::HTTP_OK);
    }

    public function update(Request $request, string $id) {
        $vendor = Vendor::where('id', $id)->first();

        if (is_null($vendor)) {
            return response()->json([
                'success' => true,
                'message' => 'Vendor tidak ditemukan',
            ], Response::HTTP_NOT_FOUND);
        }

        $vendor->nama = $request->nama ?? $vendor->nama;
        $vendor->klasifikasi = $request->klasifikasi ?? $vendor->klasifikasi;
        $vendor->is_lpp = $request->is_lpp ?? $vendor->is_lpp;
        $vendor->telp = $request->telp ?? $vendor->telp;
        $vendor->email = $request->email ?? $vendor->email;
        $vendor->website = $request->website ?? $vendor->website;
        $vendor->kota = $request->kota ?? $vendor->kota;
        $vendor->alamat = $request->alamat ?? $vendor->alamat;
        $vendor->nama_pic = $request->nama_pic ?? $vendor->nama_pic;
        $vendor->telp_pic = $request->telp_pic ?? $vendor->telp_pic;
        $vendor->email_pic = $request->email_pic ?? $vendor->email_pic;
        $vendor->jabatan_pic = $request->jabatan_pic ?? $vendor->jabatan_pic;
        $vendor->save();

        return response()->json([
            'success' => true,
            'message' => 'Vendor berhasil diupdate',
            'data' => $vendor
        ], Response::HTTP_OK);
    }

    public function destroy(string $id) {
        $vendor = Vendor::where('id', $id)->first();

        if (is_null($vendor)) {
            return response()->json([
                'success' => true,
                'message' => 'Vendor tidak ditemukan',
            ], Response::HTTP_NOT_FOUND);
        }

        $vendor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vendor berhasil dihapus',
        ], Response::HTTP_OK);
    }
}
