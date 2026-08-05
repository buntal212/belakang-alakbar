<?php

namespace App\Models;

use App\Models\Master\Jabatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WahaBendahara extends Model
{
    use HasFactory;
    protected $table = 'waha_bendahara';
    protected $guarded = ['id'];

    public function jabatan()
    {
         return $this->hasOne(Jabatan::class, 'kode', 'pemilik');
    }
}
