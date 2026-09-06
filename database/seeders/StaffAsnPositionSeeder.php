<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StaffAsnPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                'name' => 'Guru Ahli Madya',
                'position_type' => 'fungsional_keahlian',
                'eligibility' => 'both',
            ],
            [
                'name' => 'Guru Ahli Muda',
                'position_type' => 'fungsional_keahlian',
                'eligibility' => 'both',
            ],
            [
                'name' => 'Guru Ahli Pertama',
                'position_type' => 'fungsional_keahlian',
                'eligibility' => 'both',
            ],
            [
                'name' => 'Pranata Komputer Terampil',
                'position_type' => 'fungsional_keterampilan',
                'eligibility' => 'both',
            ],
            [
                'name' => 'Pustakawan Terampil',
                'position_type' => 'fungsional_keterampilan',
                'eligibility' => 'both',
            ],
            [
                'name' => 'Pengadministrasi Perkantoran',
                'position_type' => 'pelaksana',
                'eligibility' => 'pns',
            ],
            [
                'name' => 'Operator Layanan Operasional',
                'position_type' => 'pelaksana',
                'eligibility' => 'both',
            ],
        ];

        $dataToInsert = [];
        $now = now();

        foreach ($positions as $position) {
            $dataToInsert[] = array_merge($position, [
                'id' => Str::uuid()->toString(), // Generate UUID manual untuk query builder
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('staff_asn_positions')->insert($dataToInsert);
    }
}
