<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_payroll_settings';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id'];
}
