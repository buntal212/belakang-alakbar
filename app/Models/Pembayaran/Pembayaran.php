<?php

namespace App\Models\Pembayaran;

use App\Models\Master\Jabatan;
use App\Models\Master\Penyedia;
use App\Models\Master\Unit;
use App\Models\Tagihan\TagihanbelanjaRinci;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;
    protected $table = 'pembayaran';
    protected $guarded = ['id'];

    public function rinci()
    {
         return $this->hasMany(TagihanbelanjaRinci::class, 'notagihan', 'notagihan');
    }

    public function penyedia()
    {
         return $this->hasOne(Penyedia::class, 'kode', 'penyedia');
    }

    public function jabatan()
    {
         return $this->hasOne(Jabatan::class, 'kode', 'jabatan');
    }

     public function unit()
    {
         return $this->hasOne(Unit::class, 'kode', 'unit');
    }
}
