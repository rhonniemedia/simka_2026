<?php

namespace App\Models;

use Carbon\Carbon;

use App\Models\Concerns\HasStaffDocuments;
use Illuminate\Database\Eloquent\Casts\Attribute; // Tambahkan ini
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt; // Tambahkan ini

class PeriodicSalaryHistory extends Model
{
    use HasFactory, HasUuids, HasStaffDocuments;

    protected $table = 'staff_periodic_salary_histories';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id'];

    // Relasi ke data pegawai
    public function staff()
    {
        return $this->belongsTo(Data::class, 'staff_id');
    }

    /**
     * Accessor & Mutator untuk Gaji Pokok (base_salary)
     * Otomatis melakukan enkripsi dan dekripsi ke kolom 'base_salary_encrypted'
     */
    protected function baseSalary(): Attribute
    {
        return Attribute::make(
            // Accessor (Get): Menarik data dari DB, otomatis didekripsi
            get: function ($value, $attributes) {
                if (!empty($attributes['base_salary_encrypted'])) {
                    try {
                        return Crypt::decryptString($attributes['base_salary_encrypted']);
                    } catch (\Exception $e) {
                        return null; // Fallback jika data tidak valid / kunci app berubah
                    }
                }
                return null;
            },

            // Mutator (Set): Menyimpan data dari form, otomatis dienkripsi
            set: fn($value) => [
                'base_salary_encrypted' => !empty($value) ? Crypt::encryptString($value) : null
            ],
        );
    }

    public function documentLabel(): string
    {
        $label = 'Gaji Berkala - TMT ' . $this->formatDocDate($this->effective_date);

        return $this->decree_number ? $label . ' (SK ' . $this->decree_number . ')' : $label;
    }

    protected function masaKerja(): Attribute
    {
        return Attribute::get(fn() => $this->serviceInfo()['masa_kerja']);
    }

    protected function tmtBerikut(): Attribute
    {
        return Attribute::get(fn() => $this->serviceInfo()['tmt_berikut']);
    }

    protected function tahunKe(): Attribute
    {
        return Attribute::get(fn() => $this->serviceInfo()['tahun_ke']);
    }

    /**
     * Masa kerja saat TMT berkala, TMT berikutnya (+2 tahun), dan tahun ke-.
     * Butuh relasi `staff`. Setelah masa kerja lebih dari 31 tahun tidak ada berkala berikutnya.
     */
    private function serviceInfo(): array
    {
        $startDate = $this->staff?->prior_service_period_effective_date;

        if (! $this->effective_date || ! $startDate) {
            return ['masa_kerja' => '-', 'tmt_berikut' => null, 'tahun_ke' => '-'];
        }

        $tmt    = Carbon::parse($this->effective_date);
        $months = max(0, (int) Carbon::parse($startDate)->diffInMonths($tmt));
        $years  = intdiv($months, 12);

        $info = [
            'masa_kerja'  => sprintf('%02d Tahun %02d Bulan', $years, $months % 12),
            'tmt_berikut' => null,
            'tahun_ke'    => null,
        ];

        if ($years <= 31) {
            $info['tmt_berikut'] = $tmt->copy()->addYears(2)->format('Y-m-d');
            $info['tahun_ke']    = $years + 2;
        }

        return $info;
    }
}
