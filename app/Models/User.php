<?php

namespace App\Models;

use App\Models\HRIS\Karyawan;
use App\Models\TindakAudit\Spi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nik',
        'password',
        'is_reset_password',
        'reset_password_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_reset_password' => 'boolean',
            'reset_password_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function karyawan()
    {
        return $this->hasOne(Karyawan::class, 'nik', 'nik');
    }

    public function spi()
    {
        return $this->hasOne(Spi::class, 'nik', 'nik');
    }

    public function isSpi()
    {
        return $this->spi()->exists();
    }
}
