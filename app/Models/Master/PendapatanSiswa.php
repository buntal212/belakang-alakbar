<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendapatanSiswa extends Model
{
    use HasFactory;
    protected $table = 'mpendapatansiswa';
    protected $guarded = ['id'];

    public function jenisPendapatanData()
    {
        return $this->belongsTo(JenisPendapatan::class, 'jenispendapatan', 'id');
    }
}
