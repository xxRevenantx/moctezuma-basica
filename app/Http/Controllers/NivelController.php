<?php

namespace App\Http\Controllers;

use App\Models\Nivel;
use App\Http\Requests\StoreNivelRequest;
use App\Http\Requests\UpdateNivelRequest;

class NivelController extends Controller
{

    public function index()
    {
        return view("nivel.index");
    }
}
