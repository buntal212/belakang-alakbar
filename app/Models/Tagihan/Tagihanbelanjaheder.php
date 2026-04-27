<?php

namespace App\Models\Tagihan;

use App\Models\Master\Jabatan;
use App\Models\Master\Penyedia;
use App\Models\Master\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tagihanbelanjaheder extends Model
{
    use HasFactory;
    protected $table = 'tagihan_h';
    protected $guarded = ['id'];

    public function rinci()
    {
         return $this->hasMany(TagihanbelanjaRinci::class, 'notagihan', 'notagihan');
    }

    public function penyedia()
    {
         return $this->hasOne(Penyedia::class, 'kode', 'penyedia');
    }

    public function unit()
    {
         return $this->hasOne(Unit::class, 'kode', 'unit');
    }

    public function jabatan()
    {
         return $this->hasOne(Jabatan::class, 'kode', 'jabatan');
    }
}
