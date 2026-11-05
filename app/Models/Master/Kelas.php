<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'm_kelas';

    protected $guarded = ['id', 'flag'];

    public function unitData()
    {
        return $this->belongsTo(Unit::class, 'unit', 'kode');
    }
}
