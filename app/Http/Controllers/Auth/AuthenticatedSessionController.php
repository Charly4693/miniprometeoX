<?php

namespace App\Http\Controllers\Auth;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Jobs\TestConexionaes;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use App\Http\Requests\Auth\LoginRequest;
use App\Jobs\ObtenerDatosTablaAcumulados;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */

    public function store(Request $request)
    {
        // Validación de los datos de entrada
        $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
        ]);

        // Intentar autenticar al usuario
        if (Auth::attempt(['name' => $request->name, 'password' => $request->password], $request->boolean('remember'))) {
            // Regenerar la sesión
            $request->session()->regenerate();

            // Ejecutar los Jobs antes de redirigir al usuario
            TestConexionaes::dispatch();
            ObtenerDatosTablaAcumulados::dispatch();

            // Redirigir a la página principal, después de ejecutar los Jobs
            return redirect(route('home'))->with('csrf_token', csrf_token());
        }

        // Si no se pudo autenticar, mostrar el error de login
        return back()->withErrors([
            'name' => __('auth.failed'),
        ]);
    }


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
