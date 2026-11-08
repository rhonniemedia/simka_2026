<?php

namespace App\Models;

use App\Models\Concerns\HasStaffDocuments;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EducationHistory extends Model
{
    use HasFactory, HasUuids, HasStaffDocuments;

    protected $table = 'staff_education_histories';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['id'];

    /**
     * Relasi ke referensi tingkat pendidikan (staff_education_levels)
     */
    public function level()
    {
        // Sesuaikan EducationLevel::class dengan nama model referensi pendidikan Anda.
        // Kolom 'education_level_id' merujuk pada struktur tabel staff_education_histories.
        return $this->belongsTo(EducationLevel::class, 'education_level_id');
    }

    public function documentLabel(): string
    {
        return sprintf('Pendidikan %s - Lulus %s', $this->level?->name ?? '-', $this->formatDocDate($this->graduation_date));
    }

    /**
     * Nama berkas otomatis untuk dokumen milik riwayat pendidikan ini.
     *
     * Rumus : "{Nama kategori} {ALIAS jenjang}"
     * Contoh: "Ijazah S1", "Transkrip Nilai S1", "Ijazah SMA"
     *
     *  - Nama kategori : staff_document_categories.name (dicari lewat $code,
     *    mis. 'ijazah' / 'transkrip_nilai')
     *  - Alias jenjang : staff_education_levels.alias (huruf kapital)
     *
     * Jika pegawai yang sama punya riwayat LAIN dengan jenjang yang sama
     * (mis. dua kali S1), nama ditambah institusi supaya tetap unik:
     * "Ijazah S1 - Universitas Indonesia".
     *
     * Jenjang dibaca langsung dari database (bukan relasi yang sudah
     * ter-cache) agar hasilnya benar tepat setelah jenjang diubah.
     */
    public function documentName(string $code): string
    {
        $label = DocumentCategory::where('code', $code)->value('name')
            ?? Str::headline($code);

        $level = $this->level()->first();
        $alias = Str::upper($level?->alias ?: ($level?->name ?? ''));

        $name = trim("{$label} {$alias}");

        $sameLevelExists = static::query()
            ->where('staff_id', $this->staff_id)
            ->where('education_level_id', $this->education_level_id)
            ->whereKeyNot($this->getKey())
            ->exists();

        if ($sameLevelExists && filled($this->institution_name)) {
            $name .= ' - ' . $this->institution_name;
        }

        return Str::limit($name, 255, '');
    }
}
