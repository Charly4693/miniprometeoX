@extends('plantilla.plantilla')
@section('titulo', 'Configuración')
@section('contenido')

    <div class="container d-none d-md-block">
        <div class="row">
            <div class="col-12 text-center d-flex justify-content-center mt-3 mb-3" id="headerAll">
                <div class="w-50 ttl">
                    <h1>Configuración de contadores</h1>
                </div>
            </div>

            @include('plantilla.messages')

            <div id="alert-container" class="position-fixed top-0 end-0 p-3"></div>



            <div class="row d-flex justify-content-center">
                <div class="col-12 isla-list">
                    <div class="row p-3 justify-content-center text-center">
                        <div class="col-12">
                            <h2 class="titleSecondaryMoney">¿Cómo quieres configurar los contadores?</h2>
                            <div class="d-flex justify-content-center align-items-center gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="storeOption" id="single"
                                        value="single" checked>
                                    <label class="form-check-label" for="single">
                                        Individualmente
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="storeOption" id="all"
                                        value="all">
                                    <label class="form-check-label" for="all">
                                        Todos
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-3 flex-wrap mt-3">
                        <div class="mx-auto">
                            <div class="row p-1">
                                <div class="col-12">
                                    <a class="btn btn-primary w-100 btn-ttl">Máquinas</a>
                                </div>
                            </div>
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th scope="col">Alias</th>
                                        <th scope="col">Identificador</th>
                                        <th scope="col">Asignar Número de placa</th>
                                        <th scope="col">Anular pago manual</th>
                                        <th scope="col">
                                            <form id="saveAllForm" action="{{ route('configurationAccountants.storeAll') }}"
                                                method="POST">
                                                @csrf
                                                <button type="button" id="saveAll" class="btn btn-warning">Guardar
                                                    Todo</button>
                                            </form>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($machines as $machine)
                                        <tr class="user-row">
                                            <form action="{{ route('configurationAccountants.store') }}" method="POST"
                                                autocomplete="off">
                                                @csrf
                                                <input type="hidden" name="machine_id" value="{{ $machine->id }}">
                                                <td>
                                                    <input type="text"
                                                        class="form-control w-100 @error('alias.' . $machine->id) is-invalid @enderror"
                                                        name="alias[{{ $machine->id }}]" id="alias-{{ $machine->id }}"
                                                        data-row="{{ $machine->id }}" value="{{ $machine->alias }}"
                                                        readonly>
                                                    @error('alias.' . $machine->id)
                                                        <div class="invalid-feedback text-start"> {{ $message }} </div>
                                                    @enderror
                                                </td>
                                                <td>{{ $machine->identificador }}</td>
                                                <td>
                                                    <select name="numPlaca[{{ $machine->id }}]"
                                                        class="w-100 form-control select-control  numPlaca-select"
                                                        data-row="{{ $machine->id }}" disabled>

                                                        <option value="0">Seleccione placa</option>

                                                        @foreach ($numPlacas as $numPlaca)
                                                            <option value="{{ $numPlaca }}"
                                                                {{ isset($acumuladosLocales[$machine->id]) && $acumuladosLocales[$machine->id] == $numPlaca ? 'selected' : '' }}>
                                                                {{ $numPlaca }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="form-check">
                                                        <!-- Input hidden para enviar 0 cuando el checkbox está desmarcado -->
                                                        <input type="hidden" name="AnularPM[{{ $machine->id }}]"
                                                            value="0">

                                                        <!-- Checkbox con el mismo nombre para que sobrescriba el valor si se marca -->
                                                        <input type="checkbox" name="AnularPM[{{ $machine->id }}]"
                                                            id="AnularPM{{ $machine->id }}" value="1"
                                                            {{ $machine->AnularPM == 1 ? 'checked' : '' }}
                                                            data-row="{{ $machine->id }}" disabled>
                                                        <!-- 🔹 Añadimos "disabled" para que esté bloqueado al inicio -->
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex">
                                                        <button type="button" class="btn btn-warning w-100 btn-in edit"
                                                            id="{{ $machine->id }}" data-row="{{ $machine->id }}">
                                                            <i class="bi bi-pencil-square"></i>
                                                        </button>
                                                        <button type="submit"
                                                            class="btn btn-success w-100 btn-in d-none guardar"
                                                            id="{{ $machine->id }}" data-row="{{ $machine->id }}">
                                                            <i class="bi bi-check-lg"></i>
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-secondary w-100 btn-in ms-2 d-none volver"
                                                            id="{{ $machine->id }}" data-row="{{ $machine->id }}">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    </div>
                                                </td>

                                            </form>
                                        </tr>

                                        <!--MODAL ACCIONES-->
                                        <div class="modal fade" id="modalAccionesLocal{{ $machine->id }}"
                                            data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                                            aria-labelledby="modalAcciones" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h1 class="modal-title fs-5" id="modalAccionesLabel">Acciones
                                                            para
                                                            la
                                                            máquina {{ $machine->alias }}</h1>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="text-center">
                                                            <a class="btn btn-danger" data-bs-toggle="modal"
                                                                data-bs-target="#eliminarModal{{ $machine->id }}">
                                                                Eliminar
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!--Modal eliminar-->
                                        <div class="modal fade" id="eliminarModal{{ $machine->id }}"
                                            data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                                            aria-labelledby="eliminarModal{{ $machine->id }}" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h1 class="modal-title fs-5" id="staticBackdropLabel">!Eliminar
                                                            {{ $machine->alias }}!</h1>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                            aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        ¿Estas seguro que quieres eliminar la máquina
                                                        {{ $machine->alias }}?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <form action="{{ route('machines.destroy', $machine->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('DELETE')

                                                            <button type="submit"
                                                                class="btn btn-danger">Eliminar</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mx-auto">
                            <div class="row p-1">
                                <div class="col-12">
                                    <a class="btn btn-primary w-100 btn-ttl">Contadores</a>
                                </div>
                            </div>
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th scope="col">Número placa</th>
                                        <th scope="col">Nombre de la placa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($acumulados as $acumulado)
                                        <tr>
                                            <td>{{ $acumulado->NumPlaca }}</td>
                                            <td>{{ $acumulado->nombre }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>


                </div>

                <!-- MODAL DE CONFIRMACIÓN DE CAMBIOS-->
                <div class="modal fade" id="confirmModal" data-bs-backdrop="static" data-bs-keyboard="false"
                    tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h1 class="modal-title fs-5" id="confirmModalLabel">Confirmación de los cambios</h1>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <p id="confirmModalMessage"></p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-warning" id="confirmModalBtn">Aceptar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-center mt-4">
                    <!-- pagination -->
                </div>
            </div>
            <div class="d-flex justify-content-center">
                <a class="btn btn-secondary m-3" href="{{ route('home') }}">Volver</a>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/configurationAccountants.js') }}"></script>

@endsection
