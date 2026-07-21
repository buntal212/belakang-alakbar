<?php

namespace App\Models\SpjPanjar;

use App\Models\Master\Kodebelanja;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class spjpanjar_rinci extends Model
{
     use HasFactory;
    protected $table = 'spjpanjar_r';
    protected $guarded = ['id'];

    public function akun()
    {
         return $this->hasOne(Kodebelanja::class, 'kode', 'akun');
    }

}
