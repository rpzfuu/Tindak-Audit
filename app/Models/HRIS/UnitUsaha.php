<?php

namespace App\Models\HRIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitUsaha extends Model
{
    use HasFactory;

    protected $connection = 'superapps';

    protected $table = 'hris.unit_usaha';

    public function bagian()
    {
        return $this->hasMany(Bagian::class, 'kode_unit', 'kode_unit');
    }
}
