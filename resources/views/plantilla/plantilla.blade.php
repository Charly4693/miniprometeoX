<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>
        @yield('titulo')
    </title>
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <script src="{{ asset('js/js/bootstrap.bundle.min.js') }}"></script>
    <link rel="icon" href="{{ asset('img/sin fondo - copia_favicon.png') }}" type="image/x-icon">
    <style>
        .sidebar {
            height: 100vh;
            position: sticky;
            top: 0;
        }

        .sidebar-logo {
            text-align: center;
            padding: 1rem 0;
        }

        .sidebar-logo img {
            max-width: 80%;
            height: auto;
        }
    </style>
</head>

<body>


    @php
        // use App\Models\Delegation;
        //$delegations = Delegation::where('id', '<>', 0)->get();
        // $delegations = auth()->user()->delegation;

        // if ($delegations->isEmpty()) {
         //   $delegations = Delegation::where('id', '<>', 0)->get();
        // }
    @endphp

    <div class="d-flex">

        <!-- Sidebar -->
        <nav class="navbar navbar-expand-sm navbar-dark bg-dark flex-column sidebar d-none d-md-flex"
            style="width: 20%;">
            <div class="container-fluid d-flex flex-column align-items-start overflow-auto hideScroll">
                <div class="sidebar-logo">
                    <a href="/"><img src="{{ asset('img/sin fondo - copia.png') }}" alt="Logo"></a>
                </div>
                <ul class="navbar-nav flex-column w-100" id="sidebarMenu">
                    <li class="nav-item w-100">
                         <a class="nav-link" href="{{ route('configuration.index') }}">
                            <i class="bi bi-square-fill"></i> Configuración de miniprometeo
                        </a>
                        <a class="nav-link" href="{{ route('configurationMoney.index') }}">
                            <i class="bi bi-square-fill"></i> Sincronizar máquina de cambio
                        </a>
                        <a class="nav-link" href="{{ route('machines.index') }}">
                            <i class="bi bi-square-fill"></i> Configuración de máquinas
                        </a>
                        <a class="nav-link" href="{{ route('configurationAccountants.index') }}">
                            <i class="bi bi-square-fill"></i> Configuración de contadores
                        </a>
                        <a class="nav-link" href="{{ route('configurationTypeAlias.index') }}">
                            <i class="bi bi-square-fill"></i> Configuración de Tipo y alias
                        </a>
                        <a class="nav-link" href="{{ route('tickets.index') }}">
                            <i class="bi bi-square-fill"></i> Tickets
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        <!-- Fin de sidebar -->

        <div class="w-100">
            <div class="container d-flex justify-content-between pb-0 mb-2">
                <div class="pb-0 text-start">
                    @auth
                        <div>
                            Bienvenido, {{ auth()->user()->name }}
                        </div>
                    @endauth
                </div>
                <div class="pb-0 text-start d-none d-md-block">
                    <form action="{{ route('logout') }}" method="POST" style="d-inline-block">
                        @csrf
                        <button type="submit" class="border-0 text-primary text-decoration-underline">
                            Salir
                        </button>
                    </form>
                </div>
            </div>
            @yield('contenido')
        </div>

        <script>
            /*
                                                            document.addEventListener('DOMContentLoaded', function() {
                                                                var collapseElements = document.querySelectorAll('.collapse');

                                                                // Restaurar el estado del menú desde localStorage
                                                                collapseElements.forEach(function(collapseElement) {
                                                                    var id = collapseElement.id;
                                                                    var state = localStorage.getItem(id);

                                                                    if (state === 'true') {
                                                                        var collapse = new bootstrap.Collapse(collapseElement, {
                                                                            toggle: true
                                                                        });
                                                                        collapseElement.classList.add('show');
                                                                    }
                                                                });

                                                                // Escuchar los eventos de colapso para guardar el estado y manejar hijos
                                                                collapseElements.forEach(function(collapseElement) {
                                                                    collapseElement.addEventListener('shown.bs.collapse', function(e) {
                                                                        var id = e.target.id;
                                                                        localStorage.setItem(id, 'true');
                                                                    });

                                                                    collapseElement.addEventListener('hidden.bs.collapse', function(e) {
                                                                        var id = e.target.id;
                                                                        localStorage.setItem(id, 'false');

                                                                        // Cerrar solo los elementos colapsables hijos directos
                                                                        var childCollapseElements = collapseElement.querySelectorAll('.collapse');
                                                                        childCollapseElements.forEach(function(childCollapseElement) {
                                                                            if (collapseElement.contains(childCollapseElement) && collapseElement !== childCollapseElement.parentNode.closest('.collapse')) {
                                                                                var childId = childCollapseElement.id;
                                                                                var childCollapse = new bootstrap.Collapse(childCollapseElement, {
                                                                                    toggle: false
                                                                                });
                                                                                childCollapseElement.classList.remove('show');
                                                                                localStorage.setItem(childId, 'false');
                                                                            }
                                                                        });
                                                                    });
                                                                });
                                                            });*/
        </script>



    </div>

    <footer class="d-md-none fixed-bottom">
        <div class="container-fluid py-3">
            <div class="d-flex justify-content-between">
                <form action="{{ route('logout') }}" method="POST" style="d-inline-block">
                    @csrf
                    <button type="submit" class="border-0 text-primary text-decoration-underline"
                        style="background-color:transparent">
                        <i class="bi bi-box-arrow-left" style="font-size:20pt; color:black"></i>
                    </button>
                </form>
                <a href="" onclick="window.location.reload();" class="footer-links"><i
                        class="bi bi-arrow-clockwise"></i></a>
                <a href="#" class="footer-links">
                    <i class="bi bi-house"></i>
                </a>
            </div>
        </div>
    </footer>

    @yield('js')

</body>

</html>
