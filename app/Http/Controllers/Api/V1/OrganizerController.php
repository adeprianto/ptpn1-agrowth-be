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

    public function index(Request $request): JsonResponse
    {
        $query = Organizers::withCount('trainings');

        if ($request->filled('is_ptpn_group')) {
            $query->where('is_ptpn_group', $request->boolean('is_ptpn_group'));
        }

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $organizers = $query->orderBy('name')->paginate($request->integer('per_page', 20));

        return $this->success(OrganizerResource::collection($organizers), 'Daftar penyelenggara berhasil diambil');
    }

    public function store(StoreOrganizerRequest $request): JsonResponse
    {
        $organizer = Organizers::create($request->validated());

        return $this->success(new OrganizerResource($organizer), 'Penyelenggara berhasil dibuat', 201);
    }

    public function show(Organizers $organizer): JsonResponse
    {
        $organizer->loadCount('trainings');

        return $this->success(new OrganizerResource($organizer), 'Detail penyelenggara berhasil diambil');
    }

    public function update(UpdateOrganizerRequest $request, Organizers $organizer): JsonResponse
    {
        $organizer->update($request->validated());

        return $this->success(new OrganizerResource($organizer), 'Penyelenggara berhasil diperbarui');
    }

    public function destroy(Organizers $organizer): JsonResponse
    {
        if ($organizer->trainings()->exists()) {
            return $this->error('Penyelenggara tidak bisa dihapus karena masih dipakai di data pelatihan', 409);
        }

        $organizer->delete();

        return $this->success(null, 'Penyelenggara berhasil dihapus');
    }
}