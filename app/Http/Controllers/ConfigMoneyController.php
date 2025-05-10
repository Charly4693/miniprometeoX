<?php

namespace App\Http\Controllers;

use App\Models\Local;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ConfigMoneyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $local = Local::first();
        return view('configurationMoney.index', compact('local'));
    }

    public function syncAuxiliares()
    {
        try {
            Artisan::call('miniprometeo:sync-money-auxmoneystorage');
            session()->flash('success', 'Comando ejecutado y datos enviados a Prometeo correctamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al ejecutar el comando: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function syncConfig()
    {
        try {
            Artisan::call('miniprometeo:sync-money-config');
            session()->flash('success', 'Comando ejecutado y datos enviados a Prometeo correctamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al ejecutar el comando: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function syncHcInfo()
    {
        try {
            Artisan::call('miniprometeo:sync-money-synchronization24h');
            session()->flash('success', 'Comando ejecutado y datos enviados a Prometeo correctamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al ejecutar el comando: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function syncAcumulados()
    {
        try {
            // Ejecutar el comando Artisan
            Artisan::call('perform-acumulado-synchronization');
            session()->flash('success', 'Comando ejecutado y datos enviados a Prometeo correctamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al ejecutar el comando: ' . $e->getMessage());
        }

        return redirect()->back();
    }
}
