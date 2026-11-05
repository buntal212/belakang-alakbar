<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisDiskon extends Model
{
    use HasFactory;
    protected $table = 'jenis_diskon';
    protected $guarded = ['id', 'flag'];
}
