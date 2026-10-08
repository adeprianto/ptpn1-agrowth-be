<?php

namespace App\Models;

use App\Enums\ProgramBatchSector;
use App\Support\HolidayDates;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProgramBatches extends Model
{
    use HasFactory;

    protected $table = 'program_batches';

    protected $fillable = [
        'development_program_id',
        'batch_number',
        'sector',
        'vendor_id',
        'pre_learning_start',
        'pre_learning_end',
        'offline_class_start',
        'offline_class_end',
        'project_assignment_start',
        'project_assignment_end',
        'holiday_dates',
        'location',
        'cost_amount',
        'service_fee',
    ];

    protected $casts = [
        'batch_number' => 'integer',
        'sector' => ProgramBatchSector::class,
        'pre_learning_start' => 'date',
        'pre_learning_end' => 'date',
        'offline_class_start' => 'date',
        'offline_class_end' => 'date',
        'project_assignment_start' => 'date',
        'project_assignment_end' => 'date',
        'holiday_dates' => 'array',
        'cost_amount' => 'integer',
        'service_fee' => 'integer',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(DevelopmentPrograms::class, 'development_program_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ProgramBatchParticipants::class, 'program_batch_id');
    }

    public function scopeWithTotals(Builder $query): Builder
    {
        $count = '(select count(*) from program_batch_participants p where p.program_batch_id = program_batches.id)';

        return $query->addSelect([
            DB::raw("{$count} as participants_count"),
            DB::raw("coalesce(program_batches.cost_amount, 0) * {$count}
                + coalesce(program_batches.service_fee, 0) as total_cost"),
        ]);
    }

    /**
     * Batasi ke angkatan yang punya peserta dari entity yang boleh dilihat
     * akun. Angkatan yang belum punya peserta tertaut tetap tampil untuk
     * semua — sama seperti aturan laporan realisasi PSDM.
     *
     * @param  array<int, int>  $entityIds
     */
    public function scopeVisibleTo(Builder $query, array $entityIds): Builder
    {
        return $query->where(function ($q) use ($entityIds) {
            $q->whereDoesntHave('participants', fn ($p) => $p->whereNotNull('employee_id'))
                ->orWhereHas('participants.employee', fn ($e) => $e->whereIn('entity_id', $entityIds));
        });
    }

    public function totalCost(int $participants): int
    {
        return (int) $this->cost_amount * $participants + (int) $this->service_fee;
    }

    /**
     * Rentang tanggal ketiga tahap, berurutan.
     *
     * @return array<string, array{0: ?Carbon, 1: ?Carbon}>
     */
    public function stages(): array
    {
        return [
            'pre_learning' => [$this->pre_learning_start, $this->pre_learning_end],
            'offline_class' => [$this->offline_class_start, $this->offline_class_end],
            'project_assignment' => [$this->project_assignment_start, $this->project_assignment_end],
        ];
    }

    /**
     * Hari efektif tiap tahap (hari kalender dikurangi libur di dalam
     * tahap itu); null untuk tahap yang belum dijadwalkan.
     *
     * @return array<string, ?int>
     */
    public function effectiveDays(): array
    {
        return array_map(
            fn (array $range) => HolidayDates::effectiveDays($this->holiday_dates, ...$range),
            $this->stages(),
        );
    }

    /**
     * Samakan peserta angkatan ini dengan daftar NIK `$niks`.
     *
     * Peserta yang sudah ada dibiarkan (termasuk alumni yang NIK-nya tidak
     * ada di data karyawan), yang hilang dari daftar dihapus, dan NIK baru
     * ditambahkan sekaligus ditautkan ke karyawannya.
     *
     * @param  array<int, string>  $niks
     */
    public function syncParticipants(array $niks): void
    {
        $niks = array_values(array_unique(array_filter(array_map('trim', $niks), 'strlen')));

        $this->participants()->whereNotIn('employee_nik', $niks)->delete();

        $added = array_diff($niks, $this->participants()->pluck('employee_nik')->all());

        if ($added !== []) {
            $employees = Employees::whereIn('nik', $added)->pluck('id', 'nik');

            $this->participants()->createMany(array_map(
                static fn (string $nik) => ['employee_nik' => $nik, 'employee_id' => $employees[$nik] ?? null],
                array_values($added),
            ));
        }

        $this->unsetRelation('participants');
    }

    /** Tanggal paling awal angkatan ini; dipakai sebagai tahun angkatan. */
    public function startDate(): ?Carbon
    {
        return $this->pre_learning_start ?? $this->offline_class_start ?? $this->project_assignment_start;
    }

    /** Tanggal paling akhir angkatan ini. */
    public function endDate(): ?Carbon
    {
        return $this->project_assignment_end ?? $this->offline_class_end ?? $this->pre_learning_end;
    }
}
