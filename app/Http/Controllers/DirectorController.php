<?php

namespace App\Http\Controllers;

use App\Models\Director;
use App\Http\Requests\StoreDirectorRequest;
use App\Http\Requests\UpdateDirectorRequest;

class DirectorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("director.index");
    }


}
