<?php

namespace App\Models\SpjPanjar;

use App\Models\Master\Jabatan;
use App\Models\Master\Penyedia;
use App\Models\Master\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class spjpanjar_heder extends Model
{
    use HasFactory;
    protected $table = 'spjpanjar_h';
    protected $guarded = ['id'];

    public function rinci()
    {
         return $this->hasMany(spjpanjar_rinci::class, 'nospjpanjar', 'nospjpanjar');
    }
    public function penyedia()
    {
         return $this->hasOne(Penyedia::class, 'kode', 'penyedia');
    }

    public function jabatan()
    {
         return $this->hasOne(Jabatan::class, 'kode', 'jabatan');
    }

    public function unit()
    {
         return $this->hasOne(Unit::class, 'kode', 'unit');
    }

    public function user()
    {
         return $this->hasOne(User::class, 'kode', 'ditujukanke');
    }
}
