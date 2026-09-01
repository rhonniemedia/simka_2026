<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute; // Tambahkan ini
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt; // Tambahkan ini

class PeriodicSalaryHistory extends Model
{
    use HasFactory, HasUuids;

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
}
