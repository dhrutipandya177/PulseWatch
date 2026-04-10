<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Central\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return view('central.dashboard.index', compact('user'));
    }

    public function editProfile()
    {
        return view('central.dashboard.profile');
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user->update($request->only(['name', 'email', 'company_name', 'phone']));

        return back()->with('success', 'Profile updated successfully.');
    }
}
