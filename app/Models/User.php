<?php

namespace App\Models;

use App\Models\HRIS\Karyawan;
use App\Models\TindakAudit\Spi;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $connection = 'superapps';

    protected $table = 'public.users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nik',
        'password',
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

    public function isSpi(): bool
    {
        return $this->spi()->exists();
    }

    public function hasAppAccess(): bool
    {
        return DB::connection('superapps')->table('public.user_access')
            ->where('nik', $this->nik)
            ->where('aplikasi', config('tindakaudit.app_code'))
            ->exists();
    }
}
