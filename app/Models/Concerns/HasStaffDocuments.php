<?php

namespace App\Models\Concerns;

use App\Models\Data;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Services\Staff\StaffDocumentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

/**
 * Dipakai model riwayat yang boleh punya berkas bertaut
 * (GradeHistory, PeriodicSalaryHistory, AsnPositionHistory,
 *  EducationHistory, FamilyRelation, StaffStatusHistory).
 *
 * Syarat model: punya kolom `staff_id`, dan nama tabelnya terdaftar di
 * Relation::morphMap() (AppServiceProvider) serta DocumentCategory::LINKABLE_TABLES.
 *
 * @mixin Model
 * @property string $staff_id
 * @method static void deleting(\Closure $callback) // <-- Tambahkan baris ini
 */
trait HasStaffDocuments
{
    protected static function bootHasStaffDocuments(): void
    {
        // Berkas ikut dihapus lunak (soft delete) saat data induknya dihapus,
        // supaya tidak menjadi yatim dan masih bisa dipulihkan.
        static::deleting(function (Model $model) {
            $model->documents()->get()->each->delete();
        });
    }

    /** Berkas yang ditautkan ke baris ini. */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'linked_record');
    }

    /** Label yang tampil di dropdown "Data terkait" dan kolom "Terkait dengan". */
    abstract public function documentLabel(): string;

    /**
     * Baris yang belum punya berkas kategori tertentu.
     * Contoh: PeriodicSalaryHistory::withoutDocumentOf('sk_berkala')
     */
    public function scopeWithoutDocumentOf(Builder $query, string $categoryCode): Builder
    {
        return $query->whereDoesntHave('documents', function ($documents) use ($categoryCode) {
            $documents->whereHas('category', fn($category) => $category->where('code', $categoryCode));
        });
    }

    /**
     * Simpan berkas dan tautkan ke baris ini.
     * Contoh: $history->attachDocument($request->file('file'), 'sk_pangkat');
     */
    public function attachDocument(UploadedFile $file, string $categoryCode, array $meta = []): Document
    {
        $category = DocumentCategory::where('code', $categoryCode)->firstOrFail();
        $staff = Data::findOrFail($this->staff_id);

        return app(StaffDocumentService::class)->store($staff, $category, $file, $this, $meta);
    }

    protected function formatDocDate($value): string
    {
        return $value ? Carbon::parse($value)->format('d-m-Y') : '-';
    }
}
