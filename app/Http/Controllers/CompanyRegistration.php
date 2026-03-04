<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyRegistration extends Controller
{
    public function show()
    {
        return Inertia::render('CompanyRegistration');
    }
}
