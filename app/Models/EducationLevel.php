<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationLevel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_education_levels';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id'];
}
