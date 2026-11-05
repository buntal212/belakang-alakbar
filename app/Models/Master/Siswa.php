<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;
    protected $table = 'msiswa';
    protected $guarded = ['id', 'flag'];

    public function kelasData() { return $this->belongsTo(Kelas::class, 'kelas', 'kode'); }
    public function diskonData() { return $this->belongsTo(JenisDiskon::class, 'diskon', 'id'); }
    public function pendapatanSiswa() { return $this->hasMany(PendapatanSiswa::class, 'nis', 'nis'); }
}
