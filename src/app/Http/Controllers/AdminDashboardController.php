<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    //
    public function index()
    {
        // Redirect admins to the daily attendance list upon login
        return redirect('/admin/attendance/list');
    }
}
