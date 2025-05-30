@extends('plantilla.plantilla')
@section('titulo', 'Configuración')
@section('contenido')

    <div class="container">
        <meta name= "csrf-token" content="{{ csrf_token() }}">

        <div class="row">
            <div class="col-12 text-center d-flex justify-content-center mt-3 mb-3" id="headerAll">
                <div class="w-50 ttl">
                    <h1>Configuraciónes de Miniprometeo</h1>
                </div>
            </div>

            @include('plantilla.messages')

            <!-- Configuracion datos de disposicion -->

            @if (!$data['company'])
                <div class="col-8 offset-2 isla-list p-4 mt-2 mb-2 border border-secondary">
                    <h2 class="mb-3"> Configurar disposición</h2>
                    <div class="form-floating">
                        <input type="text" name="company" class="form-control" id="input_company" placeholder="Compañía">
                        <label for="company">Compañía</label>
                        <div class = "invalid-feedback" id="company_error_message">
                            TEXT ERROR
                        </div>
                    </div>
                    <div class="form-group mt-3 col-8 offset-2">
                        <button type="button" class="btn btn-warning w-100" id="button_company">Pedir datos de compañía
                        </button>
                    </div>
                </div>
            @endif

            <!-- configuracion de datos de conexiones -->



            @if ($data['company'])
                <form action="{{ route('configuration.update', $data['user_cambio']) }}" method="POST" autocomplete="off">
                    @csrf
                    @method('PUT')

                    <div class="col-8 offset-2 isla-list p-4 mt-2 mb-2 border border-secondary">
                        <div class="row mb-2">
                            <div class="col-12 d-flex justify-content-center">
                                <a class="btn btn-primary p-1 m-1 btn-ttl">Configurar compañia {{ $data['company'] }}</a>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th scope="col">Delegación</th>
                                        <th scope="col">Zona</th>
                                        <th scope="col">Local</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            {{ $data['name_delegation'] }}
                                        </td>
                                        <td>
                                            {{ $data['name_zona'] }}
                                        </td>
                                        <td>
                                            @if (count($data['locales']) === 1)
                                                {{ $data['locales'][0]->name }}
                                                <input type='hidden' name='locales' value={{ $data['locales'][0]->id }}>
                                            @else
                                                <select name="locales"
                                                    class="form-control @error('locales') is-invalid @enderror">
                                                    <option value =""> == Elije un Local ==</option>
                                                    @foreach ($data['locales'] as $local)
                                                        <option value = "{{ $local->id }}">{{ $local->name }} </option>
                                                    @endforeach
                                                </select>
                                                @error('locales')
                                                    <div class="invalid-feedback"> {{ $message }} </div>
                                                @enderror
                                            @endif
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>
                        @if (session('errorSerialNumber'))
                            <div class="text-danger fw-semibold text-center">{{ session('errorSerialNumber') }} </div>
                        @endif
                    </div>



                    <div class="col-8 offset-2 isla-list p-4 mt-2 mb-2 border border-success">
                        <div class="row mb-2">
                            <div class="col-12 d-flex justify-content-center">
                                @php
                                    // Verificar si hay clientes asignados
                                    $hasClients =
                                        isset($data['users']) &&
                                        $data['users']->isNotEmpty() &&
                                        $data['users']->some(fn($user) => $user->clients->isNotEmpty());
                                @endphp

                                @if ($hasClients)
                                    <!-- Mostrar "Cliente Asignado" por cada usuario con clientes -->
                                    @foreach ($data['users'] as $user)
                                        @if ($user->clients->isNotEmpty())
                                            <a class="btn btn-primary p-1 m-1 btn-ttl mb-2">Cliente Asignado para
                                                {{ $user->name }}</a>
                                        @endif
                                    @endforeach
                                @else
                                    <!-- Mostrar "Obtener y Guardar Datos del Cliente" si no hay clientes -->
                                    <a class="btn btn-primary p-1 m-1 btn-ttl">Obtener y Guardar Datos del Cliente</a>
                                @endif
                            </div>
                        </div>
                        @if (isset($data['users']) && $data['users']->isNotEmpty())
                            <!-- Mostrar clientes asignados si existen -->
                            @foreach ($data['users'] as $user)
                                @if ($user->clients->isNotEmpty())
                                    <div class="table-responsive">
                                        <table class="table text-center">
                                            <thead>
                                                <tr>
                                                    <th>Nombre</th>
                                                    <th>Cliente</th>
                                                    <th>Creado en</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>

                                                @foreach ($user->clients as $client)
                                                    <tr>
                                                        <td>{{ $user->name }}</td>
                                                        <td>{{ $client->name }}</td>
                                                        <td>{{ $client->created_at->format('Y-m-d H:i') }}</td>
                                                        <td>
                                                            <!-- Botón para abrir el modal -->
                                                            <button type="button" class="btn btn-danger"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#eliminarClienteModal{{ $client->id }}">
                                                                <i class="bi bi-trash3"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <!-- Mensaje si no hay usuarios -->
                            <div class="alert alert-danger">
                                No hay usuarios disponibles con un correo válido.
                            </div>
                        @endif

                        <!-- Seleccionar usuario con email válido si no hay clientes en ninguno -->
                        @if (isset($data['users']) && $data['users']->every(fn($user) => $user->clients->isEmpty()))

                            <div class="row">
                                <!-- IP -->
                                <div class="col-12 col-md-6">
                                    <div class="form-floating pb-3">
                                        <select name="user_id" class="form-control @error('user_id') is-invalid @enderror"
                                            id="user_id">
                                            <option value="" disabled selected>Seleccione un usuario</option>
                                            @foreach ($data['users'] as $user)
                                                @if ($user->email)
                                                    <option value="{{ $user->id }}" data-email="{{ $user->email }}"
                                                        @if (old('user_id') == $user->id) selected @endif>
                                                        {{ $user->name }} ({{ $user->email }})

                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <label for="user_id">Usuario</label>
                                        @if ($errors->has('user_id'))
                                            <div class="invalid-feedback"> {{ $errors->first('user_id') }} </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Puerto -->
                                <div class="col-12 col-md-6">
                                    <!-- Contraseña -->
                                    <div class="form-floating pb-3">
                                        <input type="password" name="password"
                                            class="form-control @error('password') is-invalid @enderror" id="password"
                                            placeholder="Contraseña">
                                        <label for="password">Contraseña</label>
                                        @if ($errors->has('password'))
                                            <div class="invalid-feedback"> {{ $errors->first('password') }} </div>
                                        @endif
                                    </div>

                                </div>
                            </div>
                            <!-- Botón centrado en una línea debajo -->
                            <div class="text-center mt-2">
                                <button type="button" class="btn btn-warning" id="getClientData">Obtener Cliente</button>
                            </div>
                        @endif

                        <!-- Mensaje de resultado -->
                        <div id="resultMessage" class="mt-3"></div>
                    </div>

                    <div class="col-8 offset-2 isla-list p-4 mt-2 mb-2 border border-secondary">
                        <div class="row mb-2">
                            <div class="col-12 d-flex justify-content-center">
                                <a class="btn btn-primary p-1 m-1 btn-ttl">Configuración con el servidor principal</a>
                            </div>
                        </div>
                        <div class="row">
                            <!-- IP -->
                            <div class="col-12 col-md-6">
                                <div class="form-floating pb-3 pb-md-0">
                                    <input type="text" name="ip_prometeo"
                                        class="form-control @error('ip_prometeo') is-invalid @enderror" id="ip_prometeo"
                                        placeholder="IP"
                                        @if (old('ip_prometeo')) value="{{ old('ip_prometeo') }}"
                                        @elseif ($data['user_prometeo']->ip)
                                        value="{{ $data['user_prometeo']->ip }}" @endif>
                                    <label for="ip_prometeo">IP</label>
                                    @if ($errors->has('ip_prometeo'))
                                        <div class="invalid-feedback"> {{ $errors->first('ip_prometeo') }} </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Puerto -->
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="port_prometeo"
                                        class="form-control @error('port_prometeo') is-invalid @enderror"
                                        id="port_prometeo" placeholder="Puerto"
                                        @if (old('port_prometeo')) value="{{ old('port_prometeo') }}"
                                        @elseif ($data['user_prometeo']->port !== null)
                                        value="{{ $data['user_prometeo']->port }}" @endif>
                                    <label for="port_prometeo">Puerto</label>
                                    @if ($errors->has('port_prometeo'))
                                        <div class="invalid-feedback"> {{ $errors->first('port_prometeo') }} </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-8 offset-2 isla-list p-4 mt-2 mb-2 border border-secondary">
                        <div class="row mb-2">
                            <div class="col-12 d-flex justify-content-center">
                                <a class="btn btn-primary p-1 m-1 btn-ttl">Configuración con el servidor Ticketserver</a>
                            </div>
                        </div>
                        <div class="row">
                            <!-- IP -->
                            <div class="col-12 col-md-6">
                                <div class="form-floating pb-3 pb-md-0">
                                    <input type="text" name="ip_cambio"
                                        class="form-control @error('ip_cambio') is-invalid @enderror" id="ip_cambio"
                                        placeholder="IP"
                                        @if (old('ip_cambio')) value="{{ old('ip_cambio') }}"
                                        @elseif ($data['user_cambio']->ip)
                                        value="{{ $data['user_cambio']->ip }}" @endif>
                                    <label for="ip_cambio">IP</label>
                                    @if ($errors->has('ip_cambio'))
                                        <div class="invalid-feedback"> {{ $errors->first('ip_cambio') }} </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Puerto -->
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="port_cambio"
                                        class="form-control @error('port_cambio') is-invalid @enderror" id="port_cambio"
                                        placeholder="Puerto"
                                        @if (old('port_cambio')) value="{{ old('port_cambio') }}"
                                        @elseif ($data['user_cambio']->port)
                                        value="{{ $data['user_cambio']->port }}" @endif>
                                    <label for="port_cambio">Puerto</label>
                                    @if ($errors->has('port_cambio'))
                                        <div class="invalid-feedback"> {{ $errors->first('port_cambio') }} </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-8 offset-2 isla-list p-4 mt-2 mb-2 border border-secondary">
                        <div class="row mb-2">
                            <div class="col-12 d-flex justify-content-center">
                                <a class="btn btn-primary p-1 m-1 btn-ttl">Configuración con el servidor de contadores</a>
                            </div>
                        </div>
                        <div class="row">
                            <!-- IP -->
                            <div class="col-12 col-md-6">
                                <div class="form-floating pb-3 pb-md-0">
                                    <input type="text" name="ip_comdatahost"
                                        class="form-control @error('ip_comdatahost') is-invalid @enderror"
                                        id="ip_comdatahost" placeholder="IP"
                                        @if (old('ip_comdatahost')) value="{{ old('ip_comdatahost') }}"
                                        @elseif ($data['user_comDataHost']->ip)
                                        value="{{ $data['user_comDataHost']->ip }}" @endif>
                                    <label for="ip_comdatahost">IP</label>
                                    @if ($errors->has('ip_comdatahost'))
                                        <div class="invalid-feedback"> {{ $errors->first('ip_comdatahost') }} </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Puerto -->
                            <div class="col-12 col-md-6">
                                <div class="form-floating">
                                    <input type="text" name="port_comdatahost"
                                        class="form-control @error('port_comdatahost') is-invalid @enderror"
                                        id="port_comdatahost" placeholder="Puerto"
                                        @if (old('port_comdatahost')) value="{{ old('port_comdatahost') }}"
                                        @elseif ($data['user_comDataHost']->port)
                                        value="{{ $data['user_comDataHost']->port }}" @endif>
                                    <label for="port_comdatahost">Puerto</label>
                                    @if ($errors->has('port_comdatahost'))
                                        <div class="invalid-feedback"> {{ $errors->first('port_comdatahost') }} </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Contenedor flexible para alinear los botones en el centro -->
                    <div class="d-flex justify-content-center gap-3 mt-3">
                        <!-- Botón de Enviar dentro del formulario -->
                        <button type="submit" class="btn btn-warning px-4">Guardar</button>

                        <!-- Botón "Obtener datos contadores" corregido -->
                        <a href="{{ route('configuration.buscar') }}" class="btn btn-warning px-4">Obtener datos
                            contadores</a>

                        <!-- Botón "Borrar datos" corregido
                                                    <button class="btn btn-danger px-4" data-bs-toggle="modal"
                                                        data-bs-target="#modalAccionesLocal{{ $data['user_cambio']->id }}">
                                                        Borrar
                                                    </button>-->
                    </div>

                </form>
                <!-- Contenedor para los botones -->
                <div class="d-flex justify-content-center gap-3 mt-3">
                    <button class="btn btn-danger px-4" data-bs-toggle="modal"
                        data-bs-target="#modalAccionesLocal{{ $data['user_cambio']->id }}">
                        Borrar
                    </button>
                    <a class="btn btn-secondary" href="{{ route('home') }}">Volver</a>
                </div>

            @endif
        </div>
    </div>
    <!--modal de eliminar todos los datos de configuracion-->
    <div class="modal fade" id="modalAccionesLocal{{ $data['user_cambio']->id }}" data-bs-backdrop="static"
        data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalAcciones" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">!Eliminar
                        configuración!</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Estas seguro que quieres eliminar datos del configuración?
                </div>
                <div class="modal-footer">
                    <form action="{{ route('configuration.destroy', $data['user_cambio']) }}" method="POST">
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALES DE ELIMINACIÓN (FUERA DEL FORMULARIO) -->
    @if (isset($user) && $user->clients->isNotEmpty())
        @foreach ($user->clients as $client)
            <div class="modal fade" id="eliminarClienteModal{{ $client->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Eliminar Cliente</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ¿Estás seguro de que deseas eliminar a {{ $client->name }}?
                        </div>
                        <div class="modal-footer">
                            <form action="{{ route('clients.destroy', $client->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif


    <!--Modal para reiniciar session-->
    <div class="modal fade" id="reiniciarModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="eliminarModal1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="staticBackdropLabel">!Reiniciar session!</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Quieres reiniciar el sesión?
                </div>
                <div class="modal-footer">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf

                        <button type="submit" class="btn btn-danger">Reiniciarr</button>
                    </form>
                </div>
            </div>
        </div>
    </div>



    @if (session('reiniciar') === true)
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var myModal = new bootstrap.Modal(document.getElementById('reiniciarModal'), {
                    keyboard: false
                });
                myModal.show();
            });
        </script>
    @endif

    <script>
        const buttonPedirCompany = document.getElementById('button_company');
        const blockDeError = document.getElementById('company_error_message');
        const PROMETEO_PRINCIPAL_IP = @json(session('PROMETEO_PRINCIPAL_IP'));
        const PROMETEO_PRINCIPAL_PORT = @json(session('PROMETEO_PRINCIPAL_PORT'));

        if (buttonPedirCompany !== null) {
            buttonPedirCompany.addEventListener('click', async () => {
                const companyName = document.getElementById('input_company').value;
                const apiUrl = `http://${PROMETEO_PRINCIPAL_IP}:${PROMETEO_PRINCIPAL_PORT}/api/verify-company`;

                try {
                    const companyResponse = await sendPostRequest(apiUrl, {
                        company_name: companyName
                    }); // Probamos nombre compania

                    if (companyResponse.status === 'error') {
                        showErrorMessage("Datos enviados son incorrectos");
                        return;
                    }

                    const company = companyResponse
                        .company; // Obtenemos datos de compania, ip y  port de servidor de Prometeo de compania
                    const ipPrometeoPropio = company.ip;
                    const portPrometeoPropio = company.port;

                    const saveCompanyResponse = await sendPostRequest(
                        '{{ route('configuration.save_company') }}', company
                    ); //guardamos a BD datos de compania: nombre, ip, puerto

                    console.log(saveCompanyResponse.message);

                    if (saveCompanyResponse.message !== 'success') {
                        showErrorMessage("Error al guardar datos");
                        return;
                    }

                    const urlPrometeoPropio =
                        `http://${ipPrometeoPropio}:${portPrometeoPropio}/api/send-data-company`; // obtenemos datos de compania: locales etc
                    const dataResponse = await sendPostRequest(urlPrometeoPropio, {
                        company_name: companyName
                    });

                    if (dataResponse.status === 'error') {
                        showErrorMessage("No hay datos en servidor Prometeo");
                        return;
                    }

                    const companyInfo = dataResponse.company;
                    const saveCompanyInfoResponse = await sendPostRequest(
                        '{{ route('configuration.company') }}', companyInfo
                    ); // si tenemos datos de servidor -guardamos a BD


                    if (saveCompanyInfoResponse.message === 'success') {
                        console.log("Proceso finalizado exitosamente!");
                        window.location.href =
                            '{{ route('configuration.index') }}'; // si toto bien - reiniciamos pagina
                    } else {
                        showErrorMessage("Error al guardar datos en la BD");
                    }
                } catch (error) {
                    showErrorMessage("Ocurrió un error inesperado.");
                    console.error(error);
                }
            });
        }

        /**
         * Realiza una solicitud POST.
         * @param {string} url - URL a la que se enviará la solicitud.
         * @param {object} data - Datos a enviar en el cuerpo de la solicitud.
         * @returns {Promise<Response|object>} - Respuesta o error parseado como JSON.
         */
        async function sendPostRequest(url, data) {
            const headers = {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            };
            console.log(document.querySelector('meta[name="csrf-token"]').content);

            const response = await fetch(url, {
                method: 'POST',
                headers,
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            return response.json();
        }

        /**
         * Muestra un mensaje de error en el bloque de errores.
         * @param {string} message - Mensaje a mostrar.
         */
        function showErrorMessage(message, timeout = 2000) {
            blockDeError.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        ${message}
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                                        <span aria-hidden="true">&times;</span>
                                        </button>
                                          </div>`;

            blockDeError.classList.add('d-block');

            // Ocultar el mensaje después de 2 segundos
            setTimeout(() => {
                blockDeError.classList.remove('d-block');
                blockDeError.innerHTML = ''; // Limpiar contenido
            }, timeout);
        }


        async function fetchApiServerUrl() {
            try {
                const response = await fetch('/api/getApiServerUrl');
                const data = await response.json();
                return data.api_server_url || null;
            } catch (error) {
                console.error("Error al obtener la URL del servidor:", error);
                return null;
            }
        }

        fetchApiServerUrl().then(apiUrl => {
            if (!apiUrl) {
                console.error("No se pudo obtener la URL del servidor.");
                showErrorMessage("No se pudo obtener la URL del servidor.");
                return;
            }

            const getClientDataButton = document.getElementById("getClientData");

            if (getClientDataButton) {
                getClientDataButton.addEventListener("click", function() {
                    const userSelect = document.getElementById("user_id");
                    const selectedOption = userSelect.options[userSelect.selectedIndex];
                    const userText = selectedOption.text;
                    const match = userText.match(/\((.*?)\)/);

                    if (!match || !match[1]) {
                        showErrorMessage("No se pudo obtener el email del usuario seleccionado.");
                        return;
                    }

                    const userEmail = match[1];
                    const password = document.getElementById("password").value;

                    if (!password) {
                        showErrorMessage("Por favor, complete la contraseña.");
                        return;
                    }

                    const url = `${apiUrl}/api/getDataClient`;

                    fetch(url, {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector("meta[name='csrf-token']")
                                    .getAttribute("content"),
                            },
                            body: JSON.stringify({
                                email: userEmail,
                                password: password,
                            }),
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.client) {
                                document.getElementById("resultMessage").innerHTML =
                                    `<div class="alert alert-success">
                            Cliente obtenido correctamente: <br>
                            <strong>Nombre:</strong> ${data.client.name}<br>
                        </div>`;

                                return fetch("/saveClientData", {
                                    method: "POST",
                                    headers: {
                                        "Content-Type": "application/json",
                                        "X-CSRF-TOKEN": document.querySelector(
                                            "meta[name='csrf-token']").getAttribute(
                                            "content"),
                                    },
                                    body: JSON.stringify({
                                        email: match[1],
                                        id: data.client.id,
                                        user_id: data.client.user_id,
                                        name: data.client.name,
                                        client_secret: data.client.client_secret,
                                    }),
                                });
                            } else {
                                showErrorMessage("Error: No se pudo obtener el cliente.");
                                return Promise.reject("Cliente no encontrado.");
                            }
                        })
                        .then(response => response.json())
                        .then(saveResponse => {
                            if (saveResponse.message) {
                                document.getElementById("resultMessage").innerHTML +=
                                    `<div class="alert alert-success">${saveResponse.message}</div>`;

                                window.location.reload();
                            } else {
                                showErrorMessage(saveResponse.error ||
                                    "Ocurrió un error al guardar el cliente.");
                            }
                        })
                        .catch(error => {
                            showErrorMessage(`Error en la solicitud: ${error.message || error}`);
                        });
                });
            }
        });
    </script>

@endsection
