<?php

namespace App\Models\Pengeluaranyayasan;

use App\Models\Master\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengajuanup extends Model
{
    use HasFactory;
    protected $table = 'pengajuan_up';
    protected $guarded = ['id'];

    public function unit()
    {
         return $this->hasOne(Unit::class, 'kode', 'unit');
    }
}
