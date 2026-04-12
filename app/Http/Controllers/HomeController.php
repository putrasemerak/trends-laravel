<?php

namespace App\Http\Controllers;

use App\Models\ProgramAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\Employee $user */
        $user = Auth::user();
        $empno = $user->EmpNo;

        $programs = ProgramAccess::where('EmpNo', $empno)
            ->where('SystemName', 'AINCCS')
            ->where('Status', 'Active')
            ->with('program')
            ->get();

        return view('home', compact('programs'));
    }
}
