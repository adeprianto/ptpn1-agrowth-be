<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProgramBatch\StoreProgramBatchRequest;
use App\Http\Requests\ProgramBatch\UpdateProgramBatchRequest;
use App\Http\Resources\ProgramBatchResource;
use App\Models\ProgramBatches;
use App\Traits\ApiResponse;
use App\Traits\ListQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgramBatchController extends Controller
{
    use ApiResponse;
    use ListQuery;

    /** Kolom yang boleh dipakai mengurutkan (nama dari frontend -> kolom database). */
    private const SORTABLE = [
        'program' => 'development_programs.id',
        'batch_number' => 'program_batches.batch_number',
        'sector' => 'program_batches.sector',
        'location' => 'program_batches.location',
        'start_date' => 'program_batches.pre_learning_start',
        'offline_class' => 'program_batches.offline_class_start',
        'cost_amount' => 'program_batches.cost_amount',
        // alias dari ProgramBatches::scopeWithTotals()
        'participants_count' => 'participants_count',
        'total_cost' => 'total_cost',
    ];

    /** Tahun angkatan = tahun tahap pertamanya (pre-learning). */
    private const YEAR_COLUMN = 'year(program_batches.pre_learning_start)';

    // GET /api/v1/program-batches
    public function index(Request $request): JsonResponse
    {
        $query = ProgramBatches::query()
            ->select('program_batches.*')
            ->join('development_programs', 'development_programs.id', '=', 'program_batches.development_program_id')
            ->withTotals()
            ->with('program')
            ->visibleTo($request->user()->accessibleEntityIds());

        // Daftar centang di modal filter / tab program — boleh lebih dari satu nilai.
        $this->applyInFilter($query, $request, 'development_program_id', 'program_batches.development_program_id');
        $this->applyInFilter($query, $request, 'sector', 'program_batches.sector');
        $this->applyInFilter($query, $request, 'location', 'program_batches.location');

        $years = $this->queryList($request, 'year');
        if ($years !== []) {
            $query->whereIn(DB::raw(self::YEAR_COLUMN), array_map('intval', $years));
        }

        // Kotak cari di bawah judul kolom.
        $this->applyLike($query, $request, 'batch_number', 'program_batches.batch_number');

        // Cari angkatan berdasarkan pesertanya: NIK atau nama karyawan.
        $participant = trim((string) $request->query('participant', ''));
        if ($participant !== '') {
            $query->whereHas('participants', function ($p) use ($participant) {
                $p->where('employee_nik', 'like', "%{$participant}%")
                    ->orWhereHas('employee', fn ($e) => $e->where('name', 'like', "%{$participant}%"));
            });
        }

        if ($request->filled('sort')) {
            $this->applySort($query, $request, self::SORTABLE, 'program_batches.pre_learning_start');
        } else {
            // tanpa pilihan urutan: angkatan terbaru di atas
            $query->orderByDesc('program_batches.pre_learning_start')->orderBy('development_programs.id');
        }

        $batches = $query->paginate($request->integer('per_page', 20));

        return $this->successPaginated($batches, ProgramBatchResource::class, 'Daftar angkatan berhasil diambil');
    }

    // GET /api/v1/program-batches/options — isi daftar centang filter kolom
    // Pilihan bidang tidak di sini: nilainya tetap (App\Enums\ProgramBatchSector).
    public function options(): JsonResponse
    {
        $distinct = fn (string $column) => ProgramBatches::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->values();

        return $this->success([
            'locations' => $distinct('location'),
            'years' => ProgramBatches::query()
                ->whereNotNull('pre_learning_start')
                ->selectRaw(self::YEAR_COLUMN.' as year')
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year')
                ->map(fn ($year) => (int) $year)
                ->values(),
        ], 'Pilihan filter angkatan berhasil diambil');
    }

    // POST /api/v1/program-batches — angkatan + seluruh pesertanya sekaligus
    public function store(StoreProgramBatchRequest $request): JsonResponse
    {
        $batch = DB::transaction(function () use ($request) {
            $batch = ProgramBatches::create($request->batchAttributes());
            $batch->syncParticipants($request->participantNiks());

            return $batch;
        });

        return $this->success($this->detail($batch), 'Angkatan berhasil ditambahkan', 201);
    }

    // GET /api/v1/program-batches/{programBatch}
    public function show(ProgramBatches $programBatch): JsonResponse
    {
        return $this->success($this->detail($programBatch), 'Detail angkatan berhasil diambil');
    }

    // PUT /api/v1/program-batches/{programBatch} — daftar peserta lama diganti dengan yang dikirim
    public function update(UpdateProgramBatchRequest $request, ProgramBatches $programBatch): JsonResponse
    {
        DB::transaction(function () use ($request, $programBatch) {
            $programBatch->update($request->batchAttributes());
            $programBatch->syncParticipants($request->participantNiks());
        });

        return $this->success($this->detail($programBatch), 'Perubahan angkatan berhasil disimpan');
    }

    // DELETE /api/v1/program-batches/{programBatch} — pesertanya ikut terhapus (cascade)
    public function destroy(ProgramBatches $programBatch): JsonResponse
    {
        $programBatch->delete();

        return $this->success(null, 'Angkatan berhasil dihapus');
    }

    /** Angkatan lengkap dengan program dan pesertanya, untuk respons detail. */
    private function detail(ProgramBatches $batch): ProgramBatchResource
    {
        $batch->load([
            'program',
            'vendor',
            'participants.employee.positionTitle',
            'participants.employee.entity.parent',
        ]);

        // Peserta yang ada di data karyawan diurutkan per nama, sisanya per NIK di bawahnya.
        $participants = $batch->participants
            ->sortBy(fn ($p) => [$p->employee === null, $p->employee?->name ?? $p->employee_nik])
            ->values();

        $batch->setRelation('participants', $participants);
        $batch->participants_count = $participants->count();
        $batch->total_cost = $batch->totalCost($participants->count());

        return new ProgramBatchResource($batch);
    }
}
