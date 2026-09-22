<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreVendorRequest;
use App\Http\Requests\Vendor\UpdateVendorRequest;
use App\Http\Resources\VendorResource;
use App\Models\Vendor;
use App\Traits\ApiResponse;
use App\Traits\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    use ApiResponse;
    use ListQuery;

    /** Kolom yang boleh dipakai mengurutkan (nama dari frontend -> kolom database). */
    private const SORTABLE = [
        'name' => 'name',
        'classification' => 'classification',
        'city' => 'city',
        'phone' => 'phone',
        'status' => 'status',
    ];

    // GET /api/v1/vendors
    public function index(Request $request): JsonResponse
    {
        $query = Vendor::query();

        if ($request->filled('is_lpp')) {
            $query->where('is_lpp', $request->boolean('is_lpp'));
        }

        // Pencarian gabungan nama + kota + email
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Kotak cari di bawah judul kolom
        $this->applyLike($query, $request, 'name', 'name');
        $this->applyLike($query, $request, 'city', 'city');
        $this->applyLike($query, $request, 'phone', 'phone');
        $this->applyLike($query, $request, 'email', 'email');

        // Daftar centang di modal filter — boleh lebih dari satu nilai.
        // `status` dibaca lewat queryList, bukan `if ($status = ...)`, karena
        // status "0" (non-aktif) bernilai falsy dan akan terlewat.
        $this->applyInFilter($query, $request, 'classification', 'classification');

        $statuses = $this->queryList($request, 'status');
        if ($statuses !== []) {
            $query->whereIn('status', array_map(
                static fn ($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                $statuses,
            ));
        }

        $this->applySort($query, $request, self::SORTABLE, 'name');

        $vendors = $query->paginate($request->integer('per_page', 20));

        return $this->successPaginated($vendors, VendorResource::class, 'Daftar penyelenggara pelatihan berhasil diambil');
    }

    // POST /api/v1/vendors
    public function store(StoreVendorRequest $request): JsonResponse
    {
        $vendor = Vendor::create($request->validatedWithFlags());
//        $vendor->loadCount('trainings');

        return $this->success(new VendorResource($vendor), 'Penyelenggara pelatihan berhasil dibuat', 201);
    }

    // GET /api/v1/vendors/{organizer}
    public function show(Vendor $vendor): JsonResponse
    {
//        $vendor->loadCount('trainings');

        return $this->success(new VendorResource($vendor), 'Detail penyelenggara berhasil diambil');
    }

    // PUT /api/v1/vendors/{organizer}
    public function update(UpdateVendorRequest $request, Vendor $vendor): JsonResponse
    {
        $vendor->update($request->validatedWithFlags());
//        $vendor->loadCount('trainings');

        return $this->success(new VendorResource($vendor), 'Penyelenggara pelatihan berhasil diperbarui');
    }

    // DELETE /api/v1/vendors/{organizer}
    public function destroy(Vendor $vendor): JsonResponse
    {
//        if ($vendor->trainings()->exists()) {
//            return $this->error(
//                'Penyelenggara pelatihan tidak bisa dihapus karena masih dipakai di data pelatihan. Nonaktifkan saja lewat Edit.',
//                409
//            );
//        }

        $vendor->delete();

        return $this->success(null, 'Penyelenggara pelatihan berhasil dihapus');
    }
}
