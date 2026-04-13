<?php

namespace App\Helpers\Formating;


class FormatingHelper
{

    public static function genKodeMaster($n, $kode)
    {


        $hasil = str_pad($n, 6, '0', STR_PAD_LEFT);
        return $kode . $hasil;
    }
    public static function genKodeDinLength($n, $len, $kode)
    {


        $hasil = str_pad($n, $len, '0', STR_PAD_LEFT);
        return $kode . $hasil . date('Ym');
    }

    public static function notrans($n, $kode)
    {
        $hasil = str_pad($n, 6, '0', STR_PAD_LEFT);
        return $hasil . '-' . date("m") . '-' . date("Y") . "-" . $kode;
    }
    public static function notransDay($n, $kode)
    {
        $hasil = str_pad($n, 6, '0', STR_PAD_LEFT);
        return $hasil . '-' . date("Ymd") . "-" . $kode;
    }
}
