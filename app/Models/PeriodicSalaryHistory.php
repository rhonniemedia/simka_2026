<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodicSalaryHistory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_periodic_salary_histories';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id'];
}
