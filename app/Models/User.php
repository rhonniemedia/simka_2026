<?php

namespace App\Models;

use App\Models\UserRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['username', 'password', 'staff_id', 'status'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relasi ke Profil Staf
    public function staff()
    {
        return $this->belongsTo(Data::class, 'staff_id');
    }

    // Relasi Pivot untuk mengecek Hak Akses (Role) di masing-masing Aplikasi
    public function roles()
    {
        return $this->belongsToMany(UserRole::class, 'user_app_roles', 'user_id', 'role_id')
            ->withPivot('app_id')
            ->withTimestamps();
    }

    // Helper agar middleware lebih clean
    public function hasAccessToApp(string $appId): bool
    {
        return $this->roles()
            ->wherePivot('app_id', $appId)
            ->exists();
    }
}
