@extends('plantilla.plantilla')
@section('titulo', 'Configuración máquina de cambio')
@section('contenido')
    <div class="container d-none d-md-block">
        <div class="row">
            <div class="col-12 text-center d-flex justify-content-center mt-3 mb-3" id="headerAll">
                <div class="w-50 ttl">
                    <h1>Asignar tipos de tickets con alias de las máquinas {{ $local->name }}</h1>
                </div>
            </div>
            @include('plantilla.messages')

            <div class="col-12  mt-5">
                <div class="row">
                    <div class="col-12  isla-list">
                        <div class="p-4 pb-0">
                            <div class="row p-2">
                                <div class="col-12">
                                    <a class="btn btn-primary w-100 btn-ttl">Asignar tipo con alias</a>
                                </div>
                                <div class="d-flex justify-content-center gap-3 mt-3 mb-3">

                                    <table class="table table-bordered text-center">
                                        <thead>
                                            <tr>
                                                <th scope="col">Tipo</th>
                                                <th scope="col">Comentario</th>
                                                <th scope="col">Alias</th>
                                                <th scope="col">Acciones</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($uniqueTickets as $ticket)
                                                <tr data-row="{{ $ticket->TicketNumber }}">
                                                    <form action="{{ route('configurationTypeAlias.store') }}"
                                                        method="POST" class="d-flex align-items-center"
                                                        id="form-{{ $ticket->TicketNumber }}">
                                                        @csrf <!-- Token CSRF para seguridad -->

                                                        <td>
                                                            <input type="text" class="form-control"
                                                                value="{{ $ticket->Type }}" readonly>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control"
                                                                value="{{ $ticket->Comment }}" readonly>
                                                        </td>

                                                        <td>
                                                            <input type="hidden" name="type"
                                                                value="{{ $ticket->Type }}">
                                                            <select name="alias" class="form-control select-control"
                                                                disabled>
                                                                <option value="">Seleccione un alias</option>
                                                                @foreach ($machines as $machine)
                                                                    <option value="{{ $machine->id }}"
                                                                        {{ isset($typeAlias[$ticket->Type]) && $typeAlias[$ticket->Type]->id_machine == $machine->id ? 'selected' : '' }}>
                                                                        {{ $machine->alias }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                            <small id="error_{{ $ticket->TicketNumber }}"
                                                                class="text-danger d-none" style="font-size: 12px;">
                                                                Debes seleccionar primero un Alias antes de guardar
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex">
                                                                <button type="button"
                                                                    class="btn btn-warning w-100 btn-in edit"
                                                                    data-row="{{ $ticket->TicketNumber }}"
                                                                    data-alias="{{ isset($typeAlias[$ticket->TicketNumber]) ? $typeAlias[$ticket->TicketNumber]->alias : 'Sin alias' }}">
                                                                    <i class="bi bi-pencil-square"></i>
                                                                </button>

                                                                <button type="button"
                                                                    class="btn btn-success w-100 btn-in d-none guardar"
                                                                    data-row="{{ $ticket->TicketNumber }}"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#modalAccionesLocal{{ $ticket->TicketNumber }}">
                                                                    <i class="bi bi-check-lg"></i>
                                                                </button>

                                                                <button type="button"
                                                                    class="btn btn-warning w-100 btn-in d-none ms-2 crear"
                                                                    data-row="{{ $ticket->TicketNumber }}"
                                                                    data-tipo="{{ $ticket->Type }}"
                                                                    data-maquina-id="{{ isset($typeAlias[$ticket->TicketNumber]) ? $typeAlias[$ticket->TicketNumber]->id_machine : '' }}"
                                                                    data-alias="{{ isset($typeAlias[$ticket->TicketNumber]) ? $typeAlias[$ticket->TicketNumber]->alias : '' }}">
                                                                    <i class="bi bi-floppy"></i>
                                                                </button>

                                                                <button type="button"
                                                                    class="btn btn-secondary w-100 btn-in ms-2 d-none volver"
                                                                    data-row="{{ $ticket->TicketNumber }}">
                                                                    <i class="bi bi-x-circle"></i>
                                                                </button>

                                                                <!-- Botón para abrir el modal de eliminación -->
                                                                <button type="button"
                                                                    class="btn btn-danger w-100 btn-in ms-2"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#eliminarModal{{ $ticket->TicketNumber }}">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </form>
                                                </tr>

                                                <!-- Modal de acciones para confirmar la creación -->
                                                <div class="modal fade"
                                                    id="modalCrearTipoAlias{{ $ticket->TicketNumber }}"
                                                    data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                                                    aria-labelledby="modalCrearTipoAliasLabel" aria-hidden="true">
                                                    <div class="modal-dialog" role="document">
                                                        <div class="modal-content">
                                                            <form id="formCrearAlias" method="POST"
                                                                action="{{ route('configurationTypeAlias.store') }}">
                                                                @csrf <!-- Incluir el token CSRF -->
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Asignación del tipo
                                                                        de ticket a un alias de una máquina</h5>
                                                                    <button type="button" class="btn-close"
                                                                        data-bs-dismiss="modal"
                                                                        aria-label="Close"></button>

                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="type" id="nuevoTipo">
                                                                    <input type="hidden" name="alias" id="nuevoAlias">
                                                                    <input type="hidden" name="id_machine"
                                                                        id="idMachine">

                                                                    <p>¿Estás seguro que quieres asociar este
                                                                        tipo de ticket a este alias de la máquina?
                                                                    </p>
                                                                    <p>Tipo: <strong
                                                                            id="tipoMostrar">{{ $ticket->Type }}</strong>
                                                                    </p> <!-- Mostrar el tipo -->
                                                                    <p>Alias: <strong
                                                                            id="aliasMostrar">{{ isset($typeAlias[$ticket->TicketNumber]) ? $typeAlias[$ticket->TicketNumber]->alias : '' }}</strong>
                                                                    </p> <!-- Mostrar el alias -->
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="submit"
                                                                        class="btn btn-warning">Guardar</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Modal de acciones para confirmar la edición -->
                                                <div class="modal fade"
                                                    id="modalAccionesLocal{{ $ticket->TicketNumber }}"
                                                    data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                                                    aria-labelledby="modalAccionesLabel" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h1 class="modal-title fs-5" id="modalAccionesLabel">
                                                                    Confirmar edición del tipo y su alias</h1>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                ¿Estás seguro que quieres guardar los cambios para el
                                                                <strong>{{ $ticket->Type }}</strong> y su alias <strong
                                                                    id="selected-alias-{{ $ticket->TicketNumber }}">
                                                                    {{ isset($typeAlias[$ticket->TicketNumber]) ? $typeAlias[$ticket->TicketNumber]->alias : 'Sin alias' }}
                                                                </strong>?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <form
                                                                    action="{{ route('configurationTypeAlias.update', $ticket->Type) }}"
                                                                    method="POST"
                                                                    id="update-form-{{ $ticket->TicketNumber }}">
                                                                    @csrf
                                                                    @method('PUT') <!-- Método para actualizar -->
                                                                    <!-- Inputs ocultos para enviar los datos -->
                                                                    <input type="hidden" name="type"
                                                                        value="{{ $ticket->Type }}">
                                                                    <input type="hidden" name="id_machine"
                                                                        id="idMachine" value=''>
                                                                    <input type="hidden" name="alias"
                                                                        value="{{ isset($typeAlias[$ticket->TicketNumber]) ? $typeAlias[$ticket->TicketNumber]->alias : '' }}">
                                                                    <button type="submit" class="btn btn-warning">Guardar
                                                                        cambios</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>


                                                <!-- Modal para confirmar la eliminación -->
                                                <div class="modal fade" id="eliminarModal{{ $ticket->TicketNumber }}"
                                                    data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
                                                    aria-labelledby="eliminarModal{{ $ticket->TicketNumber }}"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h1 class="modal-title fs-5" id="staticBackdropLabel">
                                                                    ¡Eliminar la asociación del tipo
                                                                    <strong>{{ $ticket->Type }}</strong> con el alias
                                                                    <span id="delete-alias-{{ $ticket->TicketNumber }}">
                                                                        {{ optional($typeAlias->get($ticket->Type))->alias ?? 'Sin alias' }}
                                                                    </span>?
                                                                </h1>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                ¿Estás seguro que deseas eliminar el alias
                                                                <strong>{{ optional($typeAlias->get($ticket->Type))->alias ?? 'Sin alias' }}</strong>
                                                                asociado al tipo <strong>{{ $ticket->Type }}</strong>?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <form
                                                                    action="{{ route('configurationTypeAlias.destroy', $ticket->Type) }}"
                                                                    method="POST"
                                                                    id="delete-form-{{ $ticket->TicketNumber }}">
                                                                    @csrf
                                                                    @method('DELETE') <!-- Método para eliminar -->
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-center gap-3 mt-3">
            <a class="btn btn-secondary" href="{{ route('home') }}">Volver</a>
        </div>
    </div>


    <script src="{{ asset('js/configurationTypeAlias.js') }}"></script>


@endsection
