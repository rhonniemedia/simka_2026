<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeGroup extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_grade_groups';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id'];

    public function grades()
    {
        return $this->hasMany(Grade::class, 'grade_group_id');
    }
}
