<?php

namespace App\Helpers\Formating;


class FormatingHelper
{

    public static function genKodeMaster($n, $kode)
    {


        $hasil = str_pad($n, 6, '0', STR_PAD_LEFT);
        return $kode . $hasil;
    }

    public static function notrans($n, $kode, $semester,$entitas)
    {
        $hasil = str_pad($n, 6, '0', STR_PAD_LEFT);
        return $hasil . '/' . $kode. '-' . $entitas. '/'. $semester .'/'.  date("Y");
    }

}
