<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Master\UserMenuAccess;
use App\Models\Master\UserSubmenuAccess;
use App\Models\Master\Jabatan;
use App\Models\Master\Unit;

#[Fillable([
    'kode',
    'username',
    'name',
    'email',
    'password',
    'pass',
    'jabatan',
    'unit'
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens,HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function menuAccesses()
    {
        return $this->hasMany(UserMenuAccess::class);
    }

    public function submenuAccesses()
    {
        return $this->hasMany(UserSubmenuAccess::class);
    }

    public function jabatanData()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan', 'kode');
    }

    public function unitData()
    {
        return $this->belongsTo(Unit::class, 'unit', 'kode');
    }
}
