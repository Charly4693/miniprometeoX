@extends('plantilla.plantilla')
@section('titulo', 'Tickets')
@section('contenido')

    <div class="container d-none d-md-block">
        <div class="row">
            <div class="col-12 text-center d-flex justify-content-center mt-3 mb-3" id="headerAll">
                <div class="w-50 ttl">
                    <h1>Salón {{ $local->name }}</h1>
                </div>
            </div>

            <div class="col-10 offset-1">
                <div class="row">
                    @include('plantilla.messages')

                    <!-- Column for Locals -->
                    <div class="col-12 mx-auto isla-list">
                        <div class="p-4">

                            <div class="row p-2">
                                <div class="col-12">
                                    <a class="btn btn-primary w-100 btn-ttl">Operaciones con tickets</a>
                                </div>
                            </div>

                            <div class="d-flex justify-content-center">
                                <ul class="nav nav-tabs ul-aux border-0 flex-nowrap" id="myTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link btn-danger" id="confTick-tab" data-bs-toggle="tab"
                                            data-bs-target="#confTick-tab-pane" type="button" role="tab"
                                            aria-controls="confTick-tab-pane" aria-selected="false">Confirmar
                                            tickets</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link btn-danger" id="abort-tab" data-bs-toggle="tab"
                                            data-bs-target="#abort-tab-pane" type="button" role="tab"
                                            aria-controls="abort-tab-pane" aria-selected="false">Abortar
                                            tickets</button>
                                    </li>

                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link btn-danger" id="creatTick-tab" data-bs-toggle="tab"
                                            data-bs-target="#creatTick-tab-pane" type="button" role="tab"
                                            aria-controls="creatTick-tab-pane" aria-selected="false">Crear
                                            tickets</button>
                                    </li>

                                </ul>
                            </div>

                            <div class="mt-2">
                                <div class="tab-content" id="myTabContent">
                                    <div class="tab-pane fade" id="abort-tab-pane" role="tabpanel"
                                        aria-labelledby="abort-tab" tabindex="0">
                                        <div class="d-none d-md-block">
                                            @if (!$abortTicket->isEmpty())
                                                <div class="table-responsive mt-5">
                                                    <form action="{{ route('abortTicket', $local->id) }}" method="POST">
                                                        @csrf
                                                        @method('POST')
                                                        <table class="table mx-auto text-center">
                                                            <thead>
                                                                <tr>
                                                                    <th scope="col">Borrar</th>
                                                                    <th scope="col">Número de ticket</th>
                                                                    <th scope="col">Fecha</th>
                                                                    <th scope="col">Usuario</th>
                                                                    <th scope="col">Valor €</th>
                                                                    <th scope="col">Tipo</th>
                                                                    <th scope="col">Comentarios</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($abortTicket as $ticket)
                                                                    <tr>
                                                                        <td><input type="checkbox" class="checkboxpc"
                                                                                name="tickets[]"
                                                                                value="{{ $ticket->TicketNumber }}"></td>
                                                                        <td>{{ $ticket->TicketNumber }}</td>
                                                                        <td>{{ \Carbon\Carbon::parse($ticket->DateTime)->format('d-m-Y H:i') }}
                                                                        </td>
                                                                        <td>{{ $ticket->User }}</td>
                                                                        <td>{{ $ticket->Value }}€</td>
                                                                        <td>{{ $ticket->Type }}</td>
                                                                        <td>{{ $ticket->Comment }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                        <div class="text-center mt-4">
                                                            <button type="button" class="btn btn-warning"
                                                                data-bs-toggle="modal" data-bs-target="#eliminarModalAbort"
                                                                id="abortarBtnPc">Abortar</button>
                                                        </div>


                                                        <!--MODAL ABORTAR-->
                                                        <div class="modal fade" id="eliminarModalAbort"
                                                            data-bs-backdrop="static" data-bs-keyboard="false"
                                                            tabindex="-1" aria-labelledby="eliminarModalAbort"
                                                            aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h1 class="modal-title fs-5"
                                                                            id="staticBackdropLabel">
                                                                            ¿Seguro que
                                                                            quieres abortar?</h1>
                                                                        <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal"
                                                                            aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        ¿Estas seguro que quieres abortar todos los tickets
                                                                        seleccionados?
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <input type="submit" value="Abortar"
                                                                            class="btn btn-danger mt-4" id="abortarBtnPc">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            @else
                                                <h3
                                                    class="alert alert-danger text-center d-flex justify-content-center align-items-center">
                                                    No hay tickets para abortar</h3>
                                            @endif
                                        </div>
                                        <div class="d-block d-md-none">
                                            @if (!$abortTicket->isEmpty())
                                                <div class="table-responsive mt-5">
                                                    <form action="{{ route('abortTicket', $local->id) }}" method="POST">
                                                        @csrf
                                                        @method('POST')
                                                        <table class="table mx-auto text-center">
                                                            <thead>
                                                                <tr>
                                                                    <th scope="col">Borrar</th>
                                                                    <th scope="col">Fecha</th>
                                                                    <th scope="col">Valor €</th>
                                                                    <th scope="col">Tipo</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($abortTicket as $ticket)
                                                                    <tr>
                                                                        <td><input type="checkbox" class="checkboxtlf"
                                                                                name="tickets[]"
                                                                                value="{{ $ticket->TicketNumber }}"></td>
                                                                        <td>{{ \Carbon\Carbon::parse($ticket->DateTime)->format('d-m-Y H:i') }}
                                                                        </td>
                                                                        <td>{{ $ticket->Value }}€</td>
                                                                        <td>{{ $ticket->Type }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                        <input type="submit" value="Abortar"
                                                            class="btn btn-warning mt-4" id="abortarBtnTlf">
                                                    </form>
                                                </div>
                                            @else
                                                <h3
                                                    class="alert alert-danger text-center d-flex justify-content-center align-items-center">
                                                    No hay tickets para abortar</h3>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="confTick-tab-pane" role="tabpanel"
                                        aria-labelledby="confTick-tab" tabindex="0">
                                        <!--CONFIRMAR-->
                                        <div class="d-none d-md-block">
                                            @if (!$confirmTicket->isEmpty())
                                                <div class="table-responsive mt-5">
                                                    <form action="{{ route('confirmTicket', $local->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('POST')
                                                        <table class="table mx-auto text-center">
                                                            <thead>
                                                                <tr>
                                                                    <th scope="col">Confirmar</th>
                                                                    <th scope="col">Número de ticket</th>
                                                                    <th scope="col">Fecha</th>
                                                                    <th scope="col">Usuario</th>
                                                                    <th scope="col">Valor €</th>
                                                                    <th scope="col">Tipo</th>
                                                                    <th scope="col">Comentarios</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($confirmTicket as $ticket)
                                                                    <tr>
                                                                        <td><input type="checkbox" class="checkboxConfpc"
                                                                                name="tickets[]"
                                                                                value="{{ $ticket->TicketNumber }}"></td>
                                                                        <td>{{ $ticket->TicketNumber }}</td>
                                                                        <td>{{ \Carbon\Carbon::parse($ticket->DateTime)->format('d-m-Y H:i') }}
                                                                        </td>
                                                                        <td>{{ $ticket->User }}</td>
                                                                        <td>{{ $ticket->Value }}€</td>
                                                                        <td>{{ $ticket->Type }}</td>
                                                                        <td>{{ $ticket->Comment }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                        <div class="d-flex justify-content-center">
                                                            <button type="button" class="btn btn-warning mt-4"
                                                                data-bs-toggle="modal" data-bs-target="#ConfirmarModal"
                                                                id="confirmarBtnPc">Confirmar</button>
                                                        </div>

                                                        <!--MODAL CONFIRMAR-->
                                                        <div class="modal fade" id="ConfirmarModal"
                                                            data-bs-backdrop="static" data-bs-keyboard="false"
                                                            tabindex="-1" aria-labelledby="ConfirmarModal"
                                                            aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h1 class="modal-title fs-5"
                                                                            id="staticBackdropLabel">
                                                                            ¿Seguro que
                                                                            quieres confirmar?</h1>
                                                                        <button type="button" class="btn-close"
                                                                            data-bs-dismiss="modal"
                                                                            aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        ¿Estas seguro que quieres confirmar todos los
                                                                        tickets
                                                                        seleccionados?
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <input type="submit" value="Confirmar"
                                                                            class="btn btn-warning mt-4"
                                                                            id="confirmarBtnPc">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            @else
                                                <h3
                                                    class="alert alert-danger text-center d-flex justify-content-center align-items-center">
                                                    No hay tickets para confirmar</h3>
                                            @endif
                                        </div>
                                        <div class="d-block d-md-none">
                                            @if (!$confirmTicket->isEmpty())
                                                <div class="table-responsive mt-5">
                                                    <form action="{{ route('confirmTicket', $local->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('POST')
                                                        <table class="table mx-auto text-center">
                                                            <thead>
                                                                <tr>
                                                                    <th scope="col">Confirmar</th>
                                                                    <th scope="col">Fecha</th>
                                                                    <th scope="col">Valor €</th>
                                                                    <th scope="col">Tipo</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($confirmTicket as $ticket)
                                                                    <tr>
                                                                        <td><input type="checkbox" class="checkboxConftlf"
                                                                                name="tickets[]"
                                                                                value="{{ $ticket->TicketNumber }}"></td>
                                                                        <td>{{ \Carbon\Carbon::parse($ticket->DateTime)->format('d-m-Y H:i') }}
                                                                        </td>
                                                                        <td>{{ $ticket->Value }}€</td>
                                                                        <td>{{ $ticket->Type }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                        <input type="submit" value="Confirmar"
                                                            class="btn btn-warning mt-4" id="confirmarBtnTlf">
                                                    </form>
                                                </div>
                                            @else
                                                <h3
                                                    class="alert alert-danger text-center d-flex justify-content-center align-items-center">
                                                    No hay tickets para confirmar</h3>
                                            @endif
                                        </div>

                                    </div>

                                    <div class="tab-pane fade" id="creatTick-tab-pane" role="tabpanel"
                                        aria-labelledby="creatTick-tab" tabindex="0">
                                        <div class="col-12">
                                            <form action="{{ route('generarTicket', $local->id) }}" method="POST">
                                                @csrf
                                                @method('POST')

                                                <div class="text-center">
                                                    <div class="form-floating mb-2 w-75 mx-auto">
                                                        <input type="hidden" name="Mode" value="webPost">
                                                        <input type="number" name="Value"
                                                            class="form-control form-control-sm" id="floatingValue"
                                                            placeholder="Valor del ticket (€)" value="0.0"
                                                            min="0">
                                                        <label for="floatingValue">Valor del ticket (€)</label>
                                                    </div>

                                                    <div class="form-floating mb-2 w-75 mx-auto">
                                                        <input type="text" name="TicketNumber"
                                                            class="form-control form-control-sm" id="floatingTicketNumber"
                                                            placeholder="Forzar número de ticket" value="">
                                                        <label for="floatingTicketNumber">Forzar número de ticket</label>
                                                    </div>

                                                    <div class="form-floating mb-2 w-75 mx-auto">
                                                        <!-- Selector de máquinas -->
                                                        <select name="TicketTypeSelect" id="ticketTypeSelect"
                                                            class="form-select form-select-sm">
                                                            <option value="null" disabled selected>Selecciona una máquina
                                                            </option>
                                                            @foreach ($machines as $machine)
                                                                <option value="{{ $machine->id }}">{{ $machine->alias }}
                                                                </option>
                                                            @endforeach
                                                            <option value="other">Otro...</option>
                                                            <!-- Opción para escribir manualmente -->
                                                        </select>
                                                        <label for="ticketTypeSelect">Tipo ticket</label>

                                                        <!-- Campo oculto que almacena el tipo de ticket (rellenado automáticamente) -->
                                                        <input type="hidden" name="TicketTypeText"
                                                            id="ticketTypeTextHidden" value="">

                                                        <!-- Campo visible para ingresar manualmente el tipo de ticket (inicialmente oculto) -->
                                                        <input type="text" name="TicketTypeText" id="customTicketType"
                                                            class="form-control mt-2 d-none"
                                                            placeholder="Especifica el tipo de ticket">
                                                    </div>

                                                    <div class="form-floating mb-2 w-75 mx-auto">
                                                        <select name="TicketTypeIsAux" class="form-select form-select-sm">
                                                            <option value="0">Selecciona una auxiliar</option>
                                                            @foreach ($auxiliaresName as $auxiliar)
                                                                <option value="{{ $auxiliar->TypeIsAux }}">
                                                                    {{ $auxiliar->AuxName }}</option>
                                                            @endforeach
                                                        </select>
                                                        <label>Tipo ticket recarga auxiliar</label>
                                                    </div>

                                                    <div class="form-check form-switch d-inline-block mx-3">
                                                        <input type="checkbox" name="TicketTypeIsBets"
                                                            class="form-check-input" id="ticketTypeIsBets">
                                                        <label class="form-check-label" for="ticketTypeIsBets">Tipo ticket
                                                            apuesta</label>
                                                    </div>

                                                    <div class="form-check form-switch d-inline-block mx-3">
                                                        <input type="checkbox" name="expired" class="form-check-input"
                                                            id="expired">
                                                        <label class="form-check-label" for="expired">Cobrar al
                                                            momento</label>
                                                    </div>

                                                    <div class="text-center mt-3">
                                                        <button type="button" class="btn btn-warning btn-sm"
                                                            id="openModalButton">Generar ticket</button>
                                                        <input type="reset" class="btn btn-danger btn-sm"
                                                            value="Limpiar">
                                                    </div>
                                                </div>

                                                <!-- MODAL CREAR -->
                                                <div class="modal fade" id="CrearModal" data-bs-backdrop="static"
                                                    data-bs-keyboard="false" tabindex="-1" aria-labelledby="CrearModal"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">¿Seguro que quieres generar el
                                                                    ticket?</h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>¿Estás seguro que quieres generar el siguiente ticket?
                                                                </p>
                                                                <table class="table text-center">
                                                                    <tr>
                                                                        <td>Cantidad</td>
                                                                        <td id="summaryValue"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Número de Ticket</td>
                                                                        <td id="summaryTicketNumber"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Tipo Ticket</td>
                                                                        <td id="summaryTicketType"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Ticket de Apuesta</td>
                                                                        <td id="summaryTicketTypeIsBets"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Tipo Ticket Recarga Auxiliar</td>
                                                                        <td id="summaryTicketTypeIsAux"></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>Cobrar al momento</td>
                                                                        <td id="summaryExpired"></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <input type="submit" value="Generar"
                                                                    class="btn btn-warning btn-sm">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </form>



                                        </div>
                                    </div>
                                    @if ($errors->any())
                                        <div id="error-alert" class="alert alert-danger mt-5">
                                            @foreach ($errors->all() as $error)
                                                <span>{{ $error }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

<!-- SCRIPT despues de la seccion del contenido -->
@section('js')
    <script src="{{ asset('js/ticketShow.js') }}"></script>
@endsection
