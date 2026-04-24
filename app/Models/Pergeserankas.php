<?php

namespace App\Models;

use App\Models\Master\Jabatan;
use App\Models\Master\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pergeserankas extends Model
{
    use HasFactory;
    protected $table = 'pergeserankas';
    protected $guarded = ['id'];

    public function unit()
    {
         return $this->hasOne(Unit::class, 'kode', 'unit');
    }

    public function jabatan()
    {
         return $this->hasOne(Jabatan::class, 'kode', 'jabatan');
    }
}
