<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreSchool extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'core_schools';

    // Karena menggunakan UUID sebagai primary key
    protected $keyType = 'string';
    public $incrementing = false;

    protected $guarded = ['id'];
}
