<?php

namespace App\Models\Tagihan;

use App\Models\Master\Kodebelanja;
use Illuminate\Database\Eloquent\Model;

class TagihanLsRinci extends Model
{
    protected $table = 'tagihan_ls_r';
    protected $guarded = ['id'];

    public function akun() { return $this->hasOne(Kodebelanja::class, 'kode', 'akun'); }
}
