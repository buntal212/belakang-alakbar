<?php

namespace App\Models\Pengajuangu;

use App\Models\Master\Jabatan;
use App\Models\Master\Unit;
use App\Models\Pembayaran\Pembayaran;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanguHeder extends Model
{
    use HasFactory;
    protected $table = 'gu_h';
    protected $guarded = ['id'];

    public function rinci()
    {
        return $this->hasMany(PengajuanguRinci::class, 'nogu', 'nogu');
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
