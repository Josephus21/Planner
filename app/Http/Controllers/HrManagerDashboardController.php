<?php

namespace App\Http\Controllers;

class HrManagerDashboardController extends Controller
{
    public function index()
    {
        return view('hr.dashboard');
    }
}