<?php

namespace App\Services;

use App\Models\Data;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Profil sekolah untuk kop dan tanda tangan laporan PDF,
 * diambil dari tabel core_schools.
 */
class SchoolProfile
{
    /**
     * @return array{name:string, city:string, head_name:string, head_nip:string}
     */
    public static function get(): array
    {
        $school = DB::table('core_schools')->first();

        $head = $school?->headmaster_staff_id
            ? Data::with('vault')->find($school->headmaster_staff_id)
            : null;

        $nip = null;
        try {
            $nip = $head?->vault?->nip;
        } catch (Throwable) {
            // gagal dekripsi: biarkan kosong
        }

        return [
            'name'      => $school->name ?? '',
            'city'      => $school->regency ?? '',   // kabupaten, mis. "Rejang Lebong"
            'head_name' => $head?->name_with_title ?? '',
            'head_nip'  => (! empty($nip) && strcasecmp($nip, 'tidak ada') !== 0) ? $nip : '',
        ];
    }
}
