<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    use HasFactory, HasUuids;

    // Menunjuk ke tabel roles di database pusat
    protected $table = 'user_roles';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id'];
}
