<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\StoreOrganizerRequest;
use App\Http\Requests\Organizer\UpdateOrganizerRequest;
use App\Http\Resources\OrganizerResource;
use App\Models\Organizers;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizerController extends Controller
{
    use ApiResponse;

    // GET /api/v1/organizers
    public function index(Request $request): JsonResponse
    {
        $query = Organizers::withCount('trainings');

        if ($request->filled('is_ptpn_group')) {
            $query->where('is_ptpn_group', $request->boolean('is_ptpn_group'));
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $organizers = $query->orderBy('name')->paginate($request->integer('per_page', 20));

        return $this->successPaginated($organizers, OrganizerResource::class, 'Daftar penyelenggara berhasil diambil');
    }

    // POST /api/v1/organizers
    public function store(StoreOrganizerRequest $request): JsonResponse
    {
        $organizer = Organizers::create($request->validatedWithFlags());
        $organizer->loadCount('trainings');

        return $this->success(new OrganizerResource($organizer), 'Penyelenggara berhasil dibuat', 201);
    }

    // GET /api/v1/organizers/{organizer}
    public function show(Organizers $organizer): JsonResponse
    {
        $organizer->loadCount('trainings');

        return $this->success(new OrganizerResource($organizer), 'Detail penyelenggara berhasil diambil');
    }

    // PUT /api/v1/organizers/{organizer}
    public function update(UpdateOrganizerRequest $request, Organizers $organizer): JsonResponse
    {
        $organizer->update($request->validatedWithFlags());
        $organizer->loadCount('trainings');

        return $this->success(new OrganizerResource($organizer), 'Penyelenggara berhasil diperbarui');
    }

    // DELETE /api/v1/organizers/{organizer}
    public function destroy(Organizers $organizer): JsonResponse
    {
        if ($organizer->trainings()->exists()) {
            return $this->error(
                'Penyelenggara tidak bisa dihapus karena masih dipakai di data pelatihan. Nonaktifkan saja lewat Edit.',
                409
            );
        }

        $organizer->delete();

        return $this->success(null, 'Penyelenggara berhasil dihapus');
    }
}
