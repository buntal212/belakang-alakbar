<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penyedia extends Model
{
    use HasFactory;
    protected $table = 'm_penyedia';
    protected $guarded = ['id'];

    public function rekening()
    {
        return $this->hasMany(Bank::class, 'kode_penyedia','kode');
    }
}

