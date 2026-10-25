<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreApp extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'core_apps';

    protected $guarded = ['id'];
}
