<?php

namespace App\Models\Pengajuangu;

use App\Models\Master\Kodebelanja;
use App\Models\Master\Penyedia;
use App\Models\Pembayaran\Pembayaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanguRinci extends Model
{
    use HasFactory;
    protected $table = 'gu_r';
    protected $guarded = ['id'];

    public function heder()
    {
        return $this->belongsTo(PengajuanguHeder::class, 'nogu', 'nogu');
    }

    public function akun()
    {
        return $this->hasOne(Kodebelanja::class, 'kode', 'kode_akun');
    }

    public function pembayaran()
    {
        return $this->hasOne(Pembayaran::class, 'nopembayaran', 'nospj');
    }

    public function penyedia()
    {
        return $this->belongsTo(Penyedia::class, 'penyedia', 'kode');
    }

    public function penerimaUser()
    {
        return $this->belongsTo(User::class, 'penerima', 'kode');
    }
}
