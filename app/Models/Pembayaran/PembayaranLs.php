<?php

namespace App\Models\Pembayaran;

use App\Models\Master\Jabatan;
use App\Models\Master\Penyedia;
use App\Models\Master\Unit;
use App\Models\Tagihan\TagihanLsRinci;
use Illuminate\Database\Eloquent\Model;

class PembayaranLs extends Model
{
    protected $table = 'pembayaran_ls';
    protected $guarded = ['id'];

    public function rinci() { return $this->hasMany(TagihanLsRinci::class, 'notagihan', 'notagihan'); }
    public function penyedia() { return $this->hasOne(Penyedia::class, 'kode', 'penyedia'); }
    public function unit() { return $this->hasOne(Unit::class, 'kode', 'unit'); }
    public function jabatan() { return $this->hasOne(Jabatan::class, 'kode', 'jabatan'); }
}
