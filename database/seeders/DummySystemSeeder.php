<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Faker\Factory as Faker;
use App\Models\Data;
use App\Models\DataVault;
use App\Models\User;

class DummySystemSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        Schema::disableForeignKeyConstraints();

        DB::table('user_app_roles')->truncate();
        DB::table('users')->truncate();
        DB::table('staff_data_vault')->truncate();
        DB::table('staff_data')->truncate();

        // 2. MASTER DATA (Ubah menjadi string murni)
        $appSimka = (string) Str::uuid();
        $appPintar = (string) Str::uuid();
        $appRapor = (string) Str::uuid();

        DB::table('core_apps')->insert([
            ['id' => $appSimka, 'name' => 'SIMKA', 'code' => 'simka', 'created_at' => now(), 'updated_at' => now()],
            ['id' => $appPintar, 'name' => 'Pintar Akademik', 'code' => 'pintar', 'created_at' => now(), 'updated_at' => now()],
            ['id' => $appRapor, 'name' => 'e-Rapor', 'code' => 'rapor', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $apps = [$appSimka, $appPintar, $appRapor];

        $roleAdmin = (string) Str::uuid();
        $roleUser = (string) Str::uuid();
        DB::table('user_roles')->insert([
            ['id' => $roleAdmin, 'name' => 'admin', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['id' => $roleUser, 'name' => 'user_biasa', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $roles = [$roleAdmin, $roleUser];

        $konsentrasiRpl = (string) Str::uuid();
        $konsentrasiTkj = (string) Str::uuid();
        DB::table('core_concentrations')->insert([
            ['id' => $konsentrasiRpl, 'name' => 'Rekayasa Perangkat Lunak', 'alias' => 'RPL', 'code' => 'RPL', 'icon' => 'code', 'description' => '-', 'created_at' => now(), 'updated_at' => now()],
            ['id' => $konsentrasiTkj, 'name' => 'Teknik Komputer dan Jaringan', 'alias' => 'TKJ', 'code' => 'TKJ', 'icon' => 'network', 'description' => '-', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $dummyEmploymentId = (string) Str::uuid();
        $dummyPersonelId = (string) Str::uuid();
        $dummyPositionId = (string) Str::uuid();

        DB::table('staff_employment_statuses')->insert([
            'id' => $dummyEmploymentId,
            'name' => 'Pegawai Negeri Sipil',
            'alias' => 'PNS',
            'code' => 'PNS-01',
            'slug' => Str::slug('Pegawai Negeri Sipil')
        ]);

        DB::table('staff_personnel_types')->insert([
            'id' => $dummyPersonelId,
            'code' => 'TENDIK-01',
            'name' => 'Tenaga Pendidik',
            'alias' => 'Tendik',
            'slug' => Str::slug('Tenaga Pendidik')
        ]);

        DB::table('staff_positions')->insert([
            'id' => $dummyPositionId,
            'code' => 'GURU-AHLI-01',
            'name' => 'Guru Ahli Pertama',
        ]);


        // 3. GENERATE DATA MENGGUNAKAN ELOQUENT MODEL
        for ($i = 0; $i < 5; $i++) {
            $gender = $faker->randomElement(['L', 'P']);
            $name = $faker->name($gender === 'L' ? 'male' : 'female');
            $nip = $faker->numerify('198#######200#0#100#');
            $email = $faker->unique()->safeEmail();

            $staff = Data::create([
                'name' => $name,
                'slug' => Str::slug($name . '-' . Str::random(5)),
                'employment_id' => $dummyEmploymentId,
                'personel_id' => $dummyPersonelId,
                'position_id' => $dummyPositionId,
                'concentration_id' => $faker->randomElement([$konsentrasiRpl, $konsentrasiTkj, null]),
                'gender' => $gender,
                'status' => 'active',
            ]);

            $phone = $faker->phoneNumber();
            DataVault::create([
                'staff_id' => $staff->id,
                'nik'   => $faker->nik(),
                'nip'   => $nip,
                'email' => $email,

                'phone_number_encrypted' => Crypt::encryptString($phone),
                'phone_number_hash'      => hash('sha256', $phone),
                'address_encrypted'      => Crypt::encryptString($faker->streetAddress()),
                'village_encrypted'      => Crypt::encryptString($faker->citySuffix()),
                'district_encrypted'     => Crypt::encryptString($faker->city()),
                'district_hash'          => hash('sha256', strtolower($faker->city())),
                'regency_encrypted'      => Crypt::encryptString($faker->city()),
                'province_encrypted'     => Crypt::encryptString($faker->state()),
            ]);

            $username = strtolower(explode(' ', trim($name))[0]) . rand(10, 99);

            $user = User::create([
                'staff_id' => $staff->id,
                'username' => $username,
                'password' => 'password123',
                'status'   => 'active',
            ]);

            $assignedApps = $faker->randomElements($apps, rand(1, 2));
            foreach ($assignedApps as $appId) {
                // Konversi juga object UUID pivot ini menjadi string murni
                $user->roles()->attach($faker->randomElement($roles), [
                    'id'     => (string) Str::uuid(),
                    'app_id' => $appId
                ]);
            }
        }

        Schema::enableForeignKeyConstraints();

        $this->command->info('✅ 5 Data berhasil di-generate menggunakan Model Eloquent!');
        $this->command->info('🔑 Password untuk semua akun: password123');
    }
}
