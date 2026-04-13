<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Unit;
use Illuminate\Http\JsonResponse;

class UnitController extends Controller
{
    public function index()
    {
        $data = Unit::where(function ($q) {
            $q->where('flaging', '<>', 1)
              ->orWhereNull('flaging');
        })
        ->orderBy('kode')
        ->simplePaginate(request('per_page') ?? 10);
        return new JsonResponse($data);
    }
}
