<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingRealizations extends Model
{
    use HasFactory;

    protected $table = 'training_realizations';

    protected $fillable = [
        'training_id',
        'learning_method',
        'learning_city',
        'learning_location',
        'total_participants',
        'year',
        'month',
        'start_date',
        'end_date',
        'duration_days',
        'learning_hours_per_day',
        'total_experiental_learning_hours',
        'total_social_learning_hours',
        'total_formal_learning_hours',
        'total_duration_learning_hours',
        'total_learning_cost',
        'total_transport_cost',
        'total_perdiem_cost',
        'total_travel_expense_cost',
        'total_cost',
        'financing_category',
        'cost_allocation',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_participants' => 'integer',
        'year' => 'integer',
        'month' => 'integer',
        'duration_days' => 'integer',
        'learning_hours_per_day' => 'integer',
        'total_experiental_learning_hours' => 'integer',
        'total_social_learning_hours' => 'integer',
        'total_formal_learning_hours' => 'integer',
        'total_duration_learning_hours' => 'integer',
        'total_learning_cost' => 'integer',
        'total_transport_cost' => 'integer',
        'total_perdiem_cost' => 'integer',
        'total_travel_expense_cost' => 'integer',
        'total_cost' => 'integer',
    ];

    /**
     * Kolom total_* tidak boleh null di database dan tidak dikirim client,
     * jadi realisasi baru (yang belum punya detail) dimulai dari nol.
     */
    protected $attributes = [
        'total_participants' => 0,
        'total_experiental_learning_hours' => 0,
        'total_social_learning_hours' => 0,
        'total_formal_learning_hours' => 0,
        'total_duration_learning_hours' => 0,
        'total_learning_cost' => 0,
        'total_transport_cost' => 0,
        'total_perdiem_cost' => 0,
        'total_travel_expense_cost' => 0,
        'total_cost' => 0,
    ];

    public function training()
    {
        return $this->belongsTo(Trainings::class, 'training_id');
    }

    public function details()
    {
        return $this->hasMany(TrainingRealizationDetails::class, 'training_realization_id');
    }

    /**
     * Ganti seluruh peserta realisasi ini dengan daftar baru, lalu hitung ulang
     * totalnya. Dipakai saat laporan disimpan dari form: yang dikirim form
     * selalu daftar peserta yang lengkap, jadi yang lama dihapus semua dan
     * dibuat ulang — lebih sederhana daripada membandingkan satu per satu.
     *
     * Periode tiap peserta (tahun, bulan, tanggal, durasi) disalin dari
     * realisasinya, sedangkan total jam dan total biaya dijumlahkan di sini.
     *
     * @param  array<int, array<string, int>>  $participants
     */
    public function syncDetails(array $participants): void
    {
        $this->details()->delete();

        foreach ($participants as $participant) {
            $this->details()->create([
                ...$participant,
                'year' => $this->year,
                'month' => $this->month,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'duration_days' => $this->duration_days,
                'learning_hours_per_day' => $this->learning_hours_per_day,
                'duration_learning_hours' => $participant['experiental_learning_hours']
                    + $participant['social_learning_hours']
                    + $participant['formal_learning_hours'],
                'total_cost' => $participant['learning_cost']
                    + $participant['transport_cost']
                    + $participant['perdiem_cost']
                    + $participant['travel_expense_cost'],
            ]);
        }

        $this->recalculateTotals();
    }

    /**
     * Ringkasan realisasi selalu turunan dari detail peserta, tidak pernah
     * diisi manual. Dipanggil setiap kali detail ditambah/diubah/dihapus.
     */
    public function recalculateTotals(): void
    {
        $details = $this->details()->get();

        $this->update([
            'total_participants' => $details->count(),
            'total_experiental_learning_hours' => (int) $details->sum('experiental_learning_hours'),
            'total_social_learning_hours' => (int) $details->sum('social_learning_hours'),
            'total_formal_learning_hours' => (int) $details->sum('formal_learning_hours'),
            'total_duration_learning_hours' => (int) $details->sum('duration_learning_hours'),
            'total_learning_cost' => (int) $details->sum('learning_cost'),
            'total_transport_cost' => (int) $details->sum('transport_cost'),
            'total_perdiem_cost' => (int) $details->sum('perdiem_cost'),
            'total_travel_expense_cost' => (int) $details->sum('travel_expense_cost'),
            'total_cost' => (int) $details->sum('total_cost'),
        ]);
    }
}
