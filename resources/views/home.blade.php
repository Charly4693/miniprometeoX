@extends('plantilla.plantilla')

@section('contenido')
    <div class="container">
        <div class="row justify-content-center">

            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

            <!-- Versión "corta" -->
            @if (session('error'))
                <div class="col-8 isla-list text-center p-2">

                    <div>
                        <div class="row p-2">
                            <div class="col-12">
                                <a class="btn btn-primary w-100 btn-ttl">Está autorizado en la versión "Corta"</a>
                            </div>
                        </div>
                        <p>Corrije los siguentes errores:</p>
                    </div>

                    <div>
                        <div class="alert alert-danger" id="mensage_error">
                            {{ session('error') }}
                        </div>
                        @if (session('error') == 'Serial numero de processador es incorrecto')
                            <button id="pedirAyuda" class="btn btn-primary">
                                Pedir ayuda online
                            </button>
                        @endif

                        @if (session('error') == 'El servidor no está configurado. Configure el servidor.')
                            <a href ="{{ route('configuration.index') }}">
                                <button id="pedirAyuda" class="btn btn-primary">
                                    Configurar el servidor.
                                </button>
                            </a>
                        @endif

                        <div class="d-none" id="button_comprobar_serial_number">
                            <a href ="{{ route('home') }}">
                                <button class="btn btn-primary mt-3">Comprobar cambio</button>
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <!-- contenido principal -->

                <div class="container d-none d-md-block text-center pt-5">
                    <div class="row">
                        <div class="col-12 text-center d-flex justify-content-center mt-4 mb-3">
                            <div class="w-50 ttl">
                                <h1>Conexión de dispositivos</h1>
                            </div>
                        </div>

                        <div class="col-12 text-center row justify-content-center mt-4 mb-3">
                            <div class="col-3 alert alert-warning m-2 alertInfo">Prometeo</div>
                            <div class="col-3 alert alert-warning m-2 alertInfo">ComDataHost</div>
                            <div class="col-3 alert alert-warning m-2 alertInfo">TicketServer</div>
                        </div>

                        <div class="mt-2">
                            <div class="row">
                                <div class="col-10 offset-1 isla-list">
                                    <div class="pading-dinamico-3 pb-0">
                                        <div class="row p-2">
                                            <div class="col-12">
                                                <a class="btn btn-primary w-100 btn-ttl">Dispositivos</a>
                                            </div>
                                        </div>

                                        <table class="table table-bordered text-center">
                                            <thead>
                                                <tr>
                                                    <th scope="col">Num</th>
                                                    <th scope="col">Nombre</th>
                                                    <th scope="col">Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody_acumulado">
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="d-flex justify-content-center mt-4">
                                        <!--pagination-->
                                    </div>
                                </div>
                                <div class="d-flex flex-column flex-md-row justify-content-center gap-3 mt-3 mb-3 w-75 mx-auto">
                                    <a href="{{ route('sync.acumulados') }}" class="btn btn-warning">Sync Máquinas contadores</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container d-block d-md-none text-center pt-5">
                    <div class="ttl d-flex align-items-center p-2">
                        <div>
                            <a href="#" class="titleLink">
                                <i style="font-size: 30pt" class="bi bi-arrow-bar-left"></i>
                            </a>
                        </div>
                        <div>
                            <h1>Conexión dispositivos</h1>
                        </div>
                    </div>

                    <div class="mt-5 p-3 isla-list">
                        <div class="row p-2 mb-4">
                            <div class="col-12">
                                <a class="btn btn-primary w-100 btn-ttl">Dispositivos</a>
                            </div>
                        </div>
                        <div class="overflow-auto">
                            <table class="table table-bordered text-center overflow-auto">
                                <thead>
                                    <tr>
                                        <th scope="col">Num</th>
                                        <th scope="col">Nombre</th>
                                        <th scope="col">Tipo</th>
                                        <th scope="col">Versión</th>
                                        <th scope="col">Estado</th>
                                        <th scope="col">Comentario</th>
                                    </tr>
                                </thead>
                                <tbody id ="tbody_acumulado">

                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-4">
                             <!-- Pagination { $machines->links('vendor.pagination.bootstrap-5') }} -->
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
            @endif
        </div>



        <script>

            // funcion para comprobar conexiones

            function checkConnect() {

                const alertes = Array.from(document.querySelectorAll('.alertInfo'));
                console.log(alertes);

                setInterval(async () => {

                    const url = "/api/checkConexion"

                    fetch(url, {
                            method: 'GET'
                        })
                        .then(response => response.json())
                        .then(data => { // devolvemos un array con boolean [false,true,true]
                            console.log(data);

                            contadorDeAlertes = 0;
                            alertes.forEach((alert) => { // comprobamos cada conexion
                                if (data.conexiones[contadorDeAlertes]) {
                                    alert.classList.remove('alert-warning');
                                    alert.classList.remove('alert-danger');
                                    alert.classList.add('alert-success');
                                } else {
                                    alert.classList.remove('alert-success');
                                    alert.classList.remove('alert-warning');
                                    alert.classList.add('alert-danger');
                                }
                                contadorDeAlertes++;
                            })
                        })
                        .catch(error => { // si no hay data - todo rojo
                            alertes.forEach(alert => {
                                alert.classList.remove('alert-success');
                                alert.classList.remove('alert-danger');
                                alert.classList.add('alert-warning');
                            })
                        })
                }, 5000);
            }



            // funcion para sincronizar tabla acumulados

            function checkAcumulados() {

                const tablaAcumulado = document.getElementById('tbody_acumulado');

                setInterval(async () => {

                    const url = "/api/checkAcumulados"

                    fetch(url, {
                            method: 'GET'
                        })
                        .then(response => response.json())
                        .then(data => { // devolvemos un array con boolean [false,true,true]
                            acumulados = data;

                            console.log(tablaAcumulado);
                            console.log(acumulados);

                            tablaAcumulado.innerHTML = "";
                            let rowColor;

                            acumulados.forEach((acumulado) => {

                                switch (acumulado.EstadoMaquina) {
                                    case 'OK':
                                        rowColor = 'bg-success-subtle';
                                        break;
                                    case 'ARRANCANDO':
                                        rowColor = 'bg-warning-subtle';
                                        break;
                                    case 'LEYENDO DATOS':
                                        rowColor = 'bg-warning-subtle';
                                        break;
                                    case 'DESCONECTADA':
                                        rowColor = 'bg-danger-subtle';
                                        break;
                                    case 'SIN DATOS':
                                        rowColor = 'bg-danger-subtle';
                                        break;
                                    default:
                                        rowColor = 'bg-danger-subtle';
                                }

                                tablaAcumulado.innerHTML +=
                                    ` <tr class="${rowColor}">
                                        <td style="background-color:transparent"> ${acumulado.NumPlaca} </td>
                                        <td style="background-color:inherit"> ${acumulado.nombre} </td>
                                        <td style="background-color:inherit"> ${acumulado.EstadoMaquina} </td>
                                    </tr> `

                            })
                        })
                        .catch(error => { // si no hay data - todo rojo
                            console.log('error')
                        })
                }, 5000);
            }



            checkConnect();
            checkAcumulados();



            const buttonPedirAyuda = document.getElementById('pedirAyuda');

            if (buttonPedirAyuda !== null) {
                buttonPedirAyuda.addEventListener('click', function(event) {
                    var serialNumberProcessor = @json(session('serialNumberProcessor'));
                    var localId = @json(session('localId->id'));
                    var prometeo_ip = @json(session('prometeo_ip'));
                    var prometeo_port = @json(session('prometeo_port'));

                    $error_message = document.getElementById('mensage_error'); // ventana de message de error

                    $url = 'http://' + prometeo_ip + ':' + prometeo_port + '/api/verify-serial-change';

                    fetch($url, {
                            method: 'POST',
                            body: JSON.stringify({
                                serialNumber: serialNumberProcessor,
                                local_id: localId || 1 // Usar localId de la sesión o 1 como fallback
                            }),
                            headers: {
                                'Content-Type': 'application/json'
                            }

                        })
                        .then(response => response.json())
                        .then(data => {

                            console.log(data);

                            $button = document.getElementById('button_comprobar_serial_number');
                            $button.classList.remove('d-none');
                            $button.classList.add('d-block');

                            if (data.status === 'pending') {

                                $error_message.innerHTML = "Esperando confirmación del administrador";
                                $error_message.classList.remove('alert-danger', 'alert-success');
                                $error_message.classList.add('alert-warning');

                            } else if (data.status === 'ok') {
                                $error_message.innerHTML = "Serial number correcto.";
                                $error_message.classList.remove('alert-danger', 'alert-warning');
                                $error_message.classList.add('alert-success');
                            }
                        })
                        .catch(error => {
                            $error_message.innerHTML = "Ocurrió un error al intentar enviar la solicitud.";
                            $error_message.classList.remove('alert-warning', 'alert-success');
                            $error_message.classList.add('alert-danger');
                        });
                });
            }

        </script>
    @endsection
