<?php

namespace App\Models\HRIS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $connection = 'superapps';

    protected $table = 'hris.holiday';
}
