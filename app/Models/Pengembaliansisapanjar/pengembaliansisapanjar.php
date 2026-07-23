<?php

namespace App\Models\Pengembaliansisapanjar;

use App\Models\Master\Jabatan;
use App\Models\Master\Unit;
use App\Models\Panjar\panjar;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pengembaliansisapanjar extends Model
{
    use HasFactory;
    protected $table = 'pengembaliansisapanjar';
    protected $guarded = ['id'];

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

    public function panjar()
    {
         return $this->hasOne(panjar::class, 'notrans', 'nopanjar');
    }
}
