<?php

namespace App\Models\HRIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bagian extends Model
{
    use HasFactory;

    protected $connection = 'superapps';

    protected $table = 'hris.bagian';

    public function sub_bagian()
    {
        return $this->hasMany(SubBagian::class, 'bagian_code', 'code');
    }
}
