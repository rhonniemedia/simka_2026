<?php

namespace App\Models;

use App\Enums\Staff\DocumentGroup;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentCategory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'staff_document_categories';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'group'     => DocumentGroup::class,
            'is_active' => 'boolean',
            'requires_document_number' => 'boolean',
        ];
    }
}
