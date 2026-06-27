<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;

use App\Http\Requests\Dashboard\Profile\ChangePasswordRequest;

use App\Http\Requests\Dashboard\Profile\UpdateProfileRequest;
use App\Services\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Profile', ['user' => auth()->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        try {
            ProfileService::updateProfile($request->validated());

            return response()->success('Profile updated.');
        } catch (\Throwable) {
            return response()->error('Unable to update profile.');
        }
    }

    public function changePassword(ChangePasswordRequest $request): RedirectResponse
    {
        try {
            $password = $request->validated('password');
            $request->user()->update([
                'password' => $password,
                'force_password_change' => false,
            ]);
            Auth::logoutOtherDevices($password);

            return response()->success('Password changed.');
        } catch (\Throwable) {
            return response()->error('Unable to change password.');
        }
    }

}

