<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationHistory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_education_histories';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id'];
}
