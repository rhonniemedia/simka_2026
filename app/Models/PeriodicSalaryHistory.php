<?php

namespace App\Models;

use Carbon\Carbon;

use App\Models\Concerns\HasStaffDocuments;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PeriodicSalaryHistory extends Model
{
    use HasFactory, HasUuids, HasStaffDocuments;

    protected $table = 'staff_periodic_salary_histories';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id'];

    /** Cache hasil serviceInfo() per instance, supaya tidak dihitung 3x oleh 3 accessor. */
    private ?array $serviceInfoCache = null;

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
            get: function ($value, $attributes) {
                if (!empty($attributes['base_salary_encrypted'])) {
                    try {
                        return Crypt::decryptString($attributes['base_salary_encrypted']);
                    } catch (\Exception $e) {
                        return null;
                    }
                }
                return null;
            },

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
     * Berkala berikutnya hanya ada jika masa kerja pada berkala berikutnya
     * tidak melebihi batas golongan yang berlaku pada TMT berkala ini.
     */
    private function serviceInfo(): array
    {
        if ($this->serviceInfoCache !== null) {
            return $this->serviceInfoCache;
        }

        $startDate = $this->staff?->prior_service_period_effective_date;

        if (! $this->effective_date || ! $startDate) {
            return $this->serviceInfoCache = ['masa_kerja' => '-', 'tmt_berikut' => null, 'tahun_ke' => '-'];
        }

        $tmt    = Carbon::parse($this->effective_date);
        $months = max(0, (int) Carbon::parse($startDate)->diffInMonths($tmt));
        $years  = intdiv($months, 12);

        $info = [
            'masa_kerja'  => sprintf('%02d Tahun %02d Bulan', $years, $months % 12),
            'tmt_berikut' => null,
            'tahun_ke'    => null,
        ];

        $maxYears  = $this->maxServiceYears();
        $nextYears = $years + 2;

        // Tanpa golongan / tanpa batas => tidak ada berkala berikutnya
        if ($maxYears !== null && $nextYears <= $maxYears) {
            $info['tmt_berikut'] = $tmt->copy()->addYears(2)->format('Y-m-d');
            $info['tahun_ke']    = $nextYears;
        }

        return $this->serviceInfoCache = $info;
    }

    /**
     * Batas masa kerja maksimal berdasarkan golongan pegawai pada TMT berkala ini
     * (logikanya ada di Data::maxPeriodicServiceYearsAt).
     *
     * Eager load agar tidak ada query per baris: with('staff.gradeHistories.grade.group')
     */
    private function maxServiceYears(): ?int
    {
        return $this->staff?->maxPeriodicServiceYearsAt($this->effective_date);
    }
}
