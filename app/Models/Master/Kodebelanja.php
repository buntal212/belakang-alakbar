<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kodebelanja extends Model
{
    use HasFactory;
    protected $table = 'm_kodebelanja';
    protected $guarded = ['id'];
}
