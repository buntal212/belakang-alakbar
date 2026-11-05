<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarifPendapatan extends Model
{
    use HasFactory;

    protected $table = 'tarif_pendapatan';

    protected $guarded = ['id', 'flag'];

    public function kelasData()
    {
        return $this->belongsTo(Kelas::class, 'kelas', 'kode');
    }

    public function jenisPendapatanData()
    {
        return $this->belongsTo(JenisPendapatan::class, 'jenispendapatan', 'id');
    }
}
