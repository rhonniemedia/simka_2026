<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonnelType extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_personnel_types';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id'];

    public function data()
    {
        // Sesuaikan 'personel_type_id' dengan nama kolom foreign key di tabel staff_data
        return $this->hasMany(Data::class, 'personnel_id');
    }
}
