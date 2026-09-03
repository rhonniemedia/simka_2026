<?php

namespace App\Enums\Staff;

enum Profession: string
{
    case TIDAK_BEKERJA = 'tidak-bekerja';
    case IBU_RUMAH_TANGGA = 'ibu-rumah-tangga';
    case NELAYAN = 'nelayan';
    case PETANI = 'petani';
    case PETERNAK = 'peternak';
    case PNS = 'pns';
    case TNI = 'tni';
    case POLRI = 'polri';
    case KARYAWAN_SWASTA = 'karyawan-swasta';
    case PEKERJA_LEPAS = 'pekerja-lepas';
    case PEDAGANG_KECIL = 'pedagang-kecil';
    case PEDAGANG_BESAR = 'pedagang-besar';
    case WIRASWASTA = 'wiraswasta';
    case WIRAUSAHA = 'wirausaha';
    case BURUH = 'buruh';
    case PENSIUNAN = 'pensiunan';
    case TENAGA_KESEHATAN = 'tenaga-kesehatan';
    case GURU_DOSEN = 'guru/dosen';
    case TRANSPORTASI = 'transportasi';
    case SENIMAN = 'seniman';
    case PENGACARA = 'pengacara';
    case TEKNISI = 'teknisi';
    case PEGAWAI_BUMN = 'pegawai-bumn';
    case LAINNYA = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::TIDAK_BEKERJA => 'Tidak Bekerja',
            self::IBU_RUMAH_TANGGA => 'Ibu Rumah Tangga',
            self::NELAYAN => 'Nelayan',
            self::PETANI => 'Petani',
            self::PETERNAK => 'Peternak',
            self::PNS => 'PNS',
            self::TNI => 'TNI',
            self::POLRI => 'POLRI',
            self::KARYAWAN_SWASTA => 'Karyawan Swasta',
            self::PEKERJA_LEPAS => 'Pekerja Lepas/Freelance',
            self::PEDAGANG_KECIL => 'Pedagang Kecil',
            self::PEDAGANG_BESAR => 'Pedagang Besar',
            self::WIRASWASTA => 'Wiraswasta',
            self::WIRAUSAHA => 'Wirausaha',
            self::BURUH => 'Buruh',
            self::PENSIUNAN => 'Pensiunan',
            self::TENAGA_KESEHATAN => 'Tenaga Kesehatan',
            self::GURU_DOSEN => 'Guru/Dosen',
            self::TRANSPORTASI => 'Sopir/Ojek/Transportasi Umum',
            self::SENIMAN => 'Seniman/Artis',
            self::PENGACARA => 'Pengacara/Notaris',
            self::TEKNISI => 'Teknisi/Operator',
            self::PEGAWAI_BUMN => 'Pegawai BUMN',
            self::LAINNYA => 'Lainnya',
        };
    }
}
