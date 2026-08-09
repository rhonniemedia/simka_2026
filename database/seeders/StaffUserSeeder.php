<?php

namespace Database\Seeders;

use App\Models\Data;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffUserSeeder extends Seeder
{
    /**
     * Ketentuan:
     * - Semua staff_data (status = active) dibuatkan user, role "user" di app "simka".
     * - staff_data yang personnel_type->alias = 'guru'   -> tambahan role "user" di app "rapor".
     * - staff_data yang personnel_type->alias = 'tendik' -> tambahan role "user" di app "pintar".
     *
     * Username  = staff_data.slug
     * Password  = "MySch" . <nomor_telepon_hasil_decrypt> . "*"
     */
    public function run(): void
    {
        // 1. Ambil referensi app & role sekali di awal (biar tidak query berulang)
        $apps = DB::table('core_apps')->pluck('id', 'code'); // ['simka' => uuid, 'pintar' => uuid, 'rapor' => uuid]

        $userRoleId = DB::table('user_roles')->where('name', 'user')->value('id');

        if (! $userRoleId) {
            $this->command->error('Role "user" tidak ditemukan di user_roles. Jalankan UserRoleSeeder terlebih dahulu.');
            return;
        }

        foreach (['simka', 'pintar', 'rapor'] as $code) {
            if (! isset($apps[$code])) {
                $this->command->error("App dengan code '{$code}' tidak ditemukan di core_apps. Jalankan CoreAppSeeder terlebih dahulu.");
                return;
            }
        }

        // 2. Ambil staff aktif beserta relasi vault (telepon, via accessor decrypt otomatis)
        //    & personnel type (alias). Eager load supaya tidak N+1 query.
        $staffs = Data::query()
            ->with(['vault', 'personnelType'])
            ->where('status', 'active')
            ->get();

        $now = now();
        $createdUsers = 0;
        $skippedExisting = 0;
        $skippedNoPhone = 0;

        foreach ($staffs as $staff) {
            // Lewati kalau staff tidak punya data vault (nomor telepon tidak tersedia)
            if (! $staff->vault) {
                $this->command->warn("Staff slug '{$staff->slug}' tidak punya data vault, dilewati.");
                $skippedNoPhone++;
                continue;
            }

            // Lewati pembuatan user kalau staff ini sudah punya akun (idempotent)
            $userId = DB::table('users')->where('staff_id', $staff->id)->value('id');

            if ($userId) {
                $skippedExisting++;
            } else {
                // DataVault::phoneNumber() sudah otomatis decrypt via accessor
                $phone = $staff->vault->phone_number;

                if (! $phone) {
                    $this->command->warn("Nomor telepon kosong untuk staff slug '{$staff->slug}', dilewati.");
                    $skippedNoPhone++;
                    continue;
                }

                $phoneDigits = $this->normalizePhone((string) $phone);
                $password = 'MySch' . $phoneDigits . '*';

                $userId = (string) Str::uuid();

                $username = $this->uniqueUsername(str_replace('-', '', $staff->slug));

                DB::table('users')->insert([
                    'id' => $userId,
                    'staff_id' => $staff->id,
                    'username' => $username,
                    'password' => Hash::make($password),
                    'email_verified_at' => $now,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $createdUsers++;
            }

            // 3. Tentukan app mana saja yang perlu di-assign untuk staff ini
            $appCodes = ['simka']; // semua staff aktif -> simka

            $personnelAlias = $staff->personnelType?->alias;

            if ($personnelAlias === 'guru') {
                $appCodes[] = 'rapor';
            } elseif ($personnelAlias === 'tendik') {
                $appCodes[] = 'pintar';
            }

            foreach ($appCodes as $appCode) {
                $alreadyHasRole = DB::table('user_app_roles')
                    ->where('user_id', $userId)
                    ->where('app_id', $apps[$appCode])
                    ->where('role_id', $userRoleId)
                    ->exists();

                if (! $alreadyHasRole) {
                    DB::table('user_app_roles')->insert([
                        'id' => (string) Str::uuid(),
                        'user_id' => $userId,
                        'app_id' => $apps[$appCode],
                        'role_id' => $userRoleId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        $this->command->info(
            "Selesai: {$createdUsers} user baru dibuat, {$skippedExisting} staff sudah punya user, "
                . "{$skippedNoPhone} dilewati (gagal dekripsi telepon)."
        );
    }

    /**
     * Normalisasi nomor telepon jadi format konsisten diawali "08...".
     * Menangani variasi umum: "+62...", "62...", "8..." (tanpa 0 di depan).
     */
    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone); // buang semua karakter non-digit (spasi, +, -, dll)

        if (str_starts_with($digits, '62')) {
            $digits = '0' . substr($digits, 2);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    /**
     * Pastikan username tidak bentrok dengan yang sudah ada di tabel users.
     * Kalau bentrok, tambahkan suffix angka urut: budisantoso, budisantoso2, budisantoso3, dst.
     */
    private function uniqueUsername(string $base): string
    {
        $username = $base;
        $suffix = 1;

        while (DB::table('users')->where('username', $username)->exists()) {
            $suffix++;
            $username = $base . $suffix;
        }

        return $username;
    }
}
