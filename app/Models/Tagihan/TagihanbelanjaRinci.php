<?php

namespace App\Models\Tagihan;

use App\Models\Master\Kodebelanja;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagihanbelanjaRinci extends Model
{
    use HasFactory;
    protected $table = 'tagihan_r';
    protected $guarded = ['id'];

    public function akun()
    {
         return $this->hasOne(Kodebelanja::class, 'kode', 'akun');
    }
}
