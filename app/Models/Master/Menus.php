<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menus extends Model
{
    use HasFactory;
    protected $table = 'menus';
    protected $guarded = ['id'];

    public function submenus()
    {
        return $this->hasMany(Submenus::class, 'id_menus', 'id');
    }
}
