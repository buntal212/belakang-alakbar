<?php

namespace App\Models\Panjar;

use App\Models\Master\Jabatan;
use App\Models\Master\Unit;
use App\Models\SpjPanjar\spjpanjar_heder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class panjar extends Model
{
    use HasFactory;
    protected $table = 'panjar';
    protected $guarded = ['id'];

    public function unit()
    {
        return $this->hasOne(Unit::class, 'kode', 'unit');
    }

    public function jabatan()
    {
        return $this->hasOne(Jabatan::class, 'kode', 'jabatan');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'kode', 'ditujukanke');
    }

    public function SpjPanjarH()
    {
        return $this->hasMany(spjpanjar_heder::class,'nopanjar','notrans');
    }
}
