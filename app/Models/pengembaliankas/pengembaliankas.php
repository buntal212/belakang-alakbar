<?php

namespace App\Models\pengembaliankas;

use App\Models\Master\Jabatan;
use App\Models\Master\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pengembaliankas extends Model
{
     use HasFactory;
    protected $table = 'pengembaliankas';
    protected $guarded = ['id'];

    public function jabatan()
    {
         return $this->hasOne(Jabatan::class, 'kode', 'jabatan');
    }

    public function unit()
    {
         return $this->hasOne(Unit::class, 'kode', 'unit');
    }


}
