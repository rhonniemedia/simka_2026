<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AsnPosition extends Model
{
    use HasUuids;

    // Pastikan nama tabel didefinisikan secara eksplisit jika tidak sesuai standar penamaan jamak/tunggal Laravel
    protected $table = 'staff_asn_positions';

    protected $guarded = ['id'];
}
