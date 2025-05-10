@extends('plantilla.plantilla')
@section('titulo', 'Importar')
@section('contenido')

    <div class="container d-none d-md-block">
        <div class="row">
            <div class="col-12 text-center d-flex justify-content-center mt-3 mb-3" id="headerAll">
                <div class="w-50 ttl mb-5 p-2">
                    <h1>Sincronizar máquinas con Prometeo</h1>
                </div>
            </div>

            @include('plantilla.messages')

            <div class="row">

                <!-- Tabla miniprometeo -->
                <div class="table-wrapper col-12 col-md-6 text-center">
                    <h2 class="titleMoneys mb-3">MiniPrometeo</h2>
                    <table class="table table-bordered">
                        <thead>
                            <tr class="table-info">
                                <th scope="col">Alias</th>
                                <th scope="col">Identificador</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($machines as $machine)
                                <tr class="user-row">
                                    <td>{{ $machine->alias }}</td>
                                    <td>
                                        @if ($diferencia && in_array($machine->identificador, $diferencia))
                                            <span style="color: red">{{ $machine->identificador }}</span>
                                        @else
                                            {{ $machine->identificador }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                        </tbody>
                    </table>

                </div>

                <!-- Tabla prometeo -->
                <div class="table-wrapper col-12 col-md-6 text-center">
                    <h2 class="titleMoneys mb-3">Prometeo</h2>
                    @if ($machines_prometeo && $machines_prometeo->isNotEmpty())
                        <table class="table table-bordered">
                            <thead>
                                <tr class="table-info">
                                    <th scope="col">Alias</th>
                                    <th scope="col">Identificador</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($machines_prometeo as $machine_prometeo)
                                    @php
                                        // Verificar si el identificador de Prometeo existe en MiniPrometeo
                                        $existeEnMiniPrometeo = $machines->contains(
                                            'identificador',
                                            $machine_prometeo->identificador,
                                        );
                                    @endphp
                                    <tr class="user-row {{ !$existeEnMiniPrometeo ? 'table-danger' : '' }}">
                                        <td>{{ $machine_prometeo->alias }}</td>
                                        <td>{{ $machine_prometeo->identificador }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="m-auto p-5 fs-1 fw-bolder text-center">
                            No hay internet.
                        </div>
                    @endif
                </div>
            </div>





            <div class="d-flex justify-content-around">
                @if (
                    $importBD === false &&
                        $machines_prometeo &&
                        $machines_prometeo->isNotEmpty() &&
                        (!empty($diferencia) || !empty($faltantes) || $machines->isEmpty()))
                    <div class="row mt-5 p-5 col-6 border border-primary">
                        <h2 class="pt-3 pb-3 text-center">Quieres sincronizar las máquinas?</h2>
                        <div class="col-6">
                            <a class="btn btn-warning w-100 btn-ttl me-2" href="{{ route('import.store') }}">SI</a>
                        </div>
                        <div class="col-6">
                            <a class="btn btn-danger w-100 btn-ttl ms-2">NO</a>
                        </div>
                    </div>
                @elseif ($importBD === false && $machines->isNotEmpty() && $machines_prometeo->isNotEmpty() && empty($diferencia))
                    <div>
                        <div class="p-5 fs-1 fw-bolder text-success">Las listas de maquinas coinciden</div>
                        <div class="d-flex justify-content-around">
                            <div class="col-6">
                                <a class="btn btn-secondary w-100 me-2" href="{{ route('machines.index') }}">Volver</a>
                            </div>
                        </div>
                    </div>
                @elseif ($importBD === true)
                    <div>
                        <div class="p-5 fs-1 fw-bolder text-success">{{ $message }}</div>
                        <div class="d-flex justify-content-around">
                            <div class="col-6">
                                <a class="btn btn-secondary w-100 me-2" href="{{ route('machines.index') }}">Volver</a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

        </div>


    </div>
@endsection


