<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required' => 'Поле логин обязательно для заполнения',
            'password.required' => 'Поле пароль обязательно для заполнения',
        ]);
        $credentials = [
            'login' => $request->login,
            'password' => $request->password,
        ];
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Успешный вход',
                    'user' => Auth::user()
                ]);
            }
            return redirect('/');
        }
        if ($request->expectsJson()) {
            throw ValidationException::withMessages([
                'login' => 'Неверные учетные данные',
            ]);
        }
        throw ValidationException::withMessages([
            'login' => 'Неверные учетные данные',
        ]);
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Успешный выход']);
        }
        return redirect('/login');
    }
}
