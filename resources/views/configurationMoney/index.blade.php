@extends('plantilla.plantilla')
@section('titulo', 'Configuración máquina de cambio')
@section('contenido')
    <div class="container d-none d-md-block">
        <div class="row">
            <div class="col-12 text-center d-flex justify-content-center mt-3 mb-3" id="headerAll">
                <div class="w-50 ttl">
                    <h1>Sincronizar la máquina de cambio</h1>
                    <h1>de {{ $local->name }}</h1>
                </div>
            </div>
            @include('plantilla.messages')

            <div class="col-10 offset-1 mt-5">
                <div class="row">
                    <div class="col-10 offset-1 isla-list">
                        <div class="p-4 pb-0">
                            <div class="row p-2">
                                <div class="col-12">
                                    <a class="btn btn-primary w-100 btn-ttl">Sincronizar máquina de cambio con prometeo</a>
                                </div>
                                <div class="d-flex justify-content-center gap-3 mt-3 mb-3">
                                    <a href="{{ route('sync.auxiliares') }}" class="btn btn-warning">Sync auxiliares</a>
                                    <a href="{{ route('sync.config') }}" class="btn btn-warning">Sync configuración</a>
                                    <a href="{{ route('sync.hcinfo') }}" class="btn btn-warning">Sync HC info...</a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <!-- Contenedor para los botones -->
            <div class="d-flex justify-content-center gap-3 mt-3">
                <a class="btn btn-secondary" href="{{ route('home') }}">Volver</a>
            </div>
        </div>
    </div>

    <div class="container d-block d-md-none text-center pt-5">
        <div class="ttl d-flex align-items-center p-2">
            <div>
                <a href="/" class="titleLink">
                    <i style="font-size: 20pt" class="bi bi-arrow-bar-left"></i>
                </a>
            </div>
            <div>
                <h1>Configuración de la máquina de cambio</h1>
            </div>
        </div>

        <div class="mt-5 p-3 isla-list">
            <div class="row p-2">
                <div class="col-12">
                    <a class="btn btn-primary w-100 btn-ttl">Sincronizar máquina de cambio con prometeo</a>
                </div>
                <div class="d-flex flex-column flex-md-row justify-content-center gap-3 mt-3 mb-3 w-75 mx-auto">
                    <a href="{{ route('sync.auxiliares') }}" class="btn btn-warning">Sync auxiliares</a>
                    <a href="{{ route('sync.config') }}" class="btn btn-warning">Sync configuración</a>
                    <a href="{{ route('sync.hcinfo') }}" class="btn btn-warning">Sync HC info...</a>
                </div>
            </div>
        </div>
    </div>


@endsection
