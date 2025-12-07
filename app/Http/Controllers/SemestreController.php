<?php

namespace App\Http\Controllers;

use App\Models\Semestre;
use App\Http\Requests\StoreSemestreRequest;
use App\Http\Requests\UpdateSemestreRequest;

class SemestreController extends Controller
{

    public function index()
    {
        return view('semestres.index');
    }


}
