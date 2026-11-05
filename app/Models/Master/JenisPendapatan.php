<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisPendapatan extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'jenis_pendapatan';

    protected $guarded = ['id', 'flag'];
}
