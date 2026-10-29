<?php

namespace App\Enums\Staff;

enum Profession: string
{
    case ASN = 'asn';
    case TNI = 'tni';
    case POLRI = 'polri';
    case HONORER_PTT = 'honorer-ptt';
    case PEGAWAI_BUMN_BUMD = 'pegawai-bumn-bumd';
    case KARYAWAN_SWASTA = 'karyawan-swasta';
    case TENAGA_AHLI = 'tenaga-ahli';
    case TENAGA_KESEHATAN = 'tenaga-kesehatan';
    case GURU_DOSEN = 'guru-dosen';
    case TEKNISI = 'teknisi';
    case PENGACARA = 'pengacara';
    case WARTAWAN_MEDIA = 'wartawan-media';
    case WIRASWASTA = 'wiraswasta';
    case WIRAUSAHA = 'wirausaha';
    case PEDAGANG_KECIL = 'pedagang-kecil';
    case PEDAGANG_BESAR = 'pedagang-besar';
    case PEKERJA_LEPAS = 'pekerja-lepas';
    case PEKERJA_KREATIF_DIGITAL = 'pekerja-kreatif-digital';
    case TRANSPORTASI = 'transportasi';
    case SENIMAN = 'seniman';
    case BURUH = 'buruh';
    case PETANI = 'petani';
    case PETERNAK = 'peternak';
    case NELAYAN = 'nelayan';
    case ROHANIWAN = 'rohaniwan';
    case PEKERJA_RUMAH_TANGGA = 'pekerja-rumah-tangga';
    case IBU_RUMAH_TANGGA = 'ibu-rumah-tangga';
    case PELAJAR_MAHASISWA = 'pelajar-mahasiswa';
    case PENSIUNAN = 'pensiunan';
    case TIDAK_BEKERJA = 'tidak-bekerja';
    case LAINNYA = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::ASN => 'Aparatur Sipil Negara',
            self::TNI => 'Tentara Nasional Indonesia',
            self::POLRI => 'Kepolisian Negara Republik Indonesia',
            self::HONORER_PTT => 'Tenaga Honorer / Pegawai Tidak Tetap',
            self::PEGAWAI_BUMN_BUMD => 'Pegawai BUMN / BUMD',
            self::KARYAWAN_SWASTA => 'Karyawan Swasta',
            self::TENAGA_AHLI => 'Tenaga Ahli / Konsultan',
            self::TENAGA_KESEHATAN => 'Tenaga Kesehatan',
            self::GURU_DOSEN => 'Guru / Dosen / Pengajar',
            self::TEKNISI => 'Teknisi / Operator',
            self::PENGACARA => 'Pengacara / Notaris',
            self::WARTAWAN_MEDIA => 'Wartawan / Jurnalis / Media',
            self::WIRASWASTA => 'Wiraswasta',
            self::WIRAUSAHA => 'Wirausaha',
            self::PEDAGANG_KECIL => 'Pedagang Kecil',
            self::PEDAGANG_BESAR => 'Pedagang Besar',
            self::PEKERJA_LEPAS => 'Pekerja Lepas / Freelance',
            self::PEKERJA_KREATIF_DIGITAL => 'Kreator Konten / Pekerja Digital',
            self::TRANSPORTASI => 'Sopir / Ojek / Transportasi Umum',
            self::SENIMAN => 'Seniman / Artis',
            self::BURUH => 'Buruh',
            self::PETANI => 'Petani',
            self::PETERNAK => 'Peternak',
            self::NELAYAN => 'Nelayan',
            self::ROHANIWAN => 'Rohaniwan / Tokoh Agama',
            self::PEKERJA_RUMAH_TANGGA => 'Pekerja Rumah Tangga',
            self::IBU_RUMAH_TANGGA => 'Ibu Rumah Tangga',
            self::PELAJAR_MAHASISWA => 'Pelajar / Mahasiswa',
            self::PENSIUNAN => 'Pensiunan',
            self::TIDAK_BEKERJA => 'Tidak Bekerja',
            self::LAINNYA => 'Lainnya',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::ASN,
            self::TNI,
            self::POLRI,
            self::HONORER_PTT,
            self::PEGAWAI_BUMN_BUMD => 'Pemerintahan & BUMN',

            self::KARYAWAN_SWASTA,
            self::TENAGA_AHLI,
            self::TENAGA_KESEHATAN,
            self::GURU_DOSEN,
            self::TEKNISI,
            self::PENGACARA,
            self::WARTAWAN_MEDIA => 'Profesional & Karyawan',

            self::WIRASWASTA,
            self::WIRAUSAHA,
            self::PEDAGANG_KECIL,
            self::PEDAGANG_BESAR => 'Wirausaha & Perdagangan',

            self::PEKERJA_LEPAS,
            self::PEKERJA_KREATIF_DIGITAL,
            self::TRANSPORTASI,
            self::SENIMAN,
            self::BURUH => 'Mandiri & Jasa',

            self::PETANI,
            self::PETERNAK,
            self::NELAYAN => 'Pertanian & Kelautan',

            self::ROHANIWAN,
            self::PEKERJA_RUMAH_TANGGA,
            self::IBU_RUMAH_TANGGA,
            self::PELAJAR_MAHASISWA,
            self::PENSIUNAN,
            self::TIDAK_BEKERJA,
            self::LAINNYA => 'Lainnya',
        };
    }

    public static function optionsForSelect(): array
    {
        return array_map(fn(self $p) => [
            'value' => $p->value,
            'label' => $p->label(),
            'group' => $p->group(),
        ], self::cases());
    }
}
