<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\Local;
use App\Models\Ticket;
use App\Models\Machine;
use App\Models\ConfigMC;
use Illuminate\Http\Request;
use App\Models\AuxMoneyStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TicketsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $local = Local::first();
        $abortTicket = $this->getAbortTickets($local->id);
        $confirmTicket = $this->getConfirmTickets($local->id);
        $auxiliaresName = $this->getAuxiliaresName($local->id);

        //dd($allTypes);




        //$allTypes = $this->getAllTypes();


        // "machines" para mostrar el typo a la hora de generar tickets
        $machines = Machine::getOnlyChildren($local->id);
        //dd($machines);


        //return view('tickets.index', compact('machines'));



        return view('tickets.index', compact('local', 'abortTicket', 'confirmTicket', 'machines', 'auxiliaresName'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    /*OBTENRT LOS TICKETS QUE SE PUEDEN ABORTAR*/
    public function getAbortTickets($id)
    {
        try {

            $tickets = DB::connection(nuevaConexion($id))->table('tickets')
                ->where(function ($query) {
                    $query->where('Command', 'OPEN')
                        ->orWhere('Command', 'PRINTED')
                        ->orWhere('Command', 'AUTHREQ');
                })
                ->get();
            return $tickets;

            //dd($tickets);
        } catch (Exception $e) {
            dd('dentro del catch     ' . $e);
        }
    }

    public function abortTickets(Request $request, $local)
    {
        //dd($request->all());
        try {
            if (!empty($request->tickets)) {
                $user = 'Prometeo_' .auth()->user()->name;
                $date = Carbon::now('Europe/Madrid');
                $ip = getRealIpAddr();

                $localDate = Local::find($local);
                $nombreUsuario = null;

                foreach ($request->tickets as $ticket) {
                    $updateTicket = DB::connection(nuevaConexion($local))->table('tickets')
                        ->where("TicketNumber", $ticket)
                        ->update([
                            'LastCommandChangeDateTime' => $date,
                            'Command' => 'ABORT',
                            'Used' => '0',
                            'LastIP' => $ip,
                            'LastUser' => $user
                        ]);

                    $updateTicket = Ticket::where("TicketNumber", $ticket)
                        ->update([
                            'LastCommandChangeDateTime' => $date,
                            'Command' => 'ABORT',
                            'Used' => '0',
                            'LastIP' => $ip,
                            'LastUser' => $user
                        ]);

                    $getUpdateTicket = Ticket::where("TicketNumber", $ticket)->first();

                    if (
                        $getUpdateTicket &&
                        !is_null($getUpdateTicket->Value) &&
                        $getUpdateTicket->User !== "Prometeo_Prometeo" &&
                        $getUpdateTicket->User !== "Prometeo_Miniprometeo"
                    ) {
                        if (str_starts_with($getUpdateTicket->User, "Prometeo_")) {
                            $nombreUsuario = explode("_", $getUpdateTicket->User, 2)[1] ?? null;
                            if (!empty($nombreUsuario)) {
                                $this->currentBalanceUser($nombreUsuario, $getUpdateTicket->Value, $local);
                            }
                        } else {
                            $this->currentBalanceUser($getUpdateTicket->User, $getUpdateTicket->Value, $local);
                        }
                    }

                    $this->generarLogConfirm($local, $request->tickets, 'abort', $user);
                }
                Log::info($nombreUsuario);

                return redirect()->route("tickets.index", $local)->with(['success' => 'Ticket abortado exitosamanete y currentBalance aztualizado.']);
            } else {
                return redirect()->route("tickets.index", $local)->withErrors(['error' => 'Ocurrió un error al abortar tickets antes de CATCH']);
            }
        } catch (Exception $e) {
            return redirect()->route("tickets.index", $local)->with(['error' => 'Ocurrió un error al abortar tickets despues del CATCH' . $e]);
        }
    }


    /*SINCRONIZAR CURRENT BALANCE DEL USUARIO*/
    // falta hacerlo con algun usuario que este en la base de datos y no sea con prometeo si no el currentBalance da error, por que prometeo no tiene user entonces current valance es 0
    // probe con usuario Rafa a mano y funciona correctamente
    function currentBalanceUser($usuario, $valor, $local)
    {
        //dd($usuario);
        try {
            $actualCurrentBalance = DB::connection(nuevaConexion($local))->table('users')
                ->where("User", $usuario)->value('currentbalance'); // puse rafa para comprobar si funciona por que con prometeo no se puede hacer a nos ser que creemos un user prometeo

            if ($actualCurrentBalance) {
                $newCurrentBalance = $actualCurrentBalance - $valor;

                if ($newCurrentBalance >= 0) {
                    $updateCurrentValance = DB::connection(nuevaConexion($local))->table('users')
                        ->where("User", $usuario)
                        ->update([
                            'currentbalance' => $newCurrentBalance,
                        ]);
                    return redirect()->route("tickets.index", $local)->with(['success' => 'Saldo descontado a su currentBalance y ticket ABORTADO']);
                } else {
                    $updateCurrentValance = DB::connection(nuevaConexion($local))->table('users')
                        ->where("User", $usuario)
                        ->update([
                            'currentbalance' => 0,
                        ]);
                    return redirect()->route("tickets.index", $local)->with(['success' => 'Saldo actualizado, currentBalance puesto a 0 y ticket ABORTADO']);
                }
            } else {
                return redirect()->route("tickets.index", $local)->withErrors(['error' => 'Ocurrió un error al actualizar currentBalance']);
            }
        } catch (Exception $e) {
            return redirect()->route("tickets.index", $local)->withErrors(['error' => 'Ocurrió un error al actualizar currentBalance' . $e]);
        }
    }

    /*OBTENRT LOS TICKETS QUE SE PUEDEN CONFIRMAR*/
    public function getConfirmTickets($id)
    {
        $tickets = DB::connection(nuevaConexion($id))->table('tickets')
            ->where(function ($query) {
                $query->where('Command', 'AUTHREQ');
            })
            ->get();
        return $tickets;
    }

    /*ABORTAR TICKETS*/
    public function confirmTicket(Request $request, $local)
    {
        try {
            if (!empty($request->tickets)) {
                $user = "Prometeo_" . auth()->user()->name;
                $date = Carbon::now('Europe/Madrid');
                $ip = getRealIpAddr();

                $localDate = Local::find($local);

                foreach ($request->tickets as $ticket) {
                    $updateTicket = DB::connection(nuevaConexion($local))->table('tickets')
                        ->where("TicketNumber", $ticket)
                        ->update([
                            'LastCommandChangeDateTime' => $date,
                            'Command' => 'OPEN',
                            'Used' => '0',
                            'LastIP' => $ip,
                            'LastUser' => $user,
                        ]);

                    $updateTicket = Ticket::where("TicketNumber", $ticket)
                        ->update([
                            'LastCommandChangeDateTime' => $date,
                            'Command' => 'OPEN',
                            'Used' => '0',
                            'LastIP' => $ip,
                            'LastUser' => $user,
                        ]);
                }

                // a la hora de confirmar ticket hay que pasar el usuario si no dara error, por que para generar el log necesita user
                $this->generarLogConfirm($local, $request->tickets, 'confirm', $user);
                return redirect()->route("tickets.index", $local)->with(['success' => 'Ticket confirmado con éxito']);;
            } else {
                return redirect()->route("tickets.index", $local)->withErrors(['error' => 'Ocurrió un error al confirmar tickets']);
            }
        } catch (Exception $e) {
            return redirect()->route("tickets.index", $local)->withErrors(['error' => 'Ocurrió un error al confirmar tickets']);
        }
    }

    public function generarLogConfirm($id, $tickets, $action, $user = 'Miniprometeo')
    {
        //dd($user);
        try {
            foreach ($tickets as $ticket) {
                $currentTime = Carbon::now();
                $micro = sprintf("%06d", ($currentTime->micro / 1000));

                if ($action == 'confirm') {
                    $text = 'Ticket confirmado en modo web: ' . $ticket;
                } else if ($action == 'abort') {
                    $text = 'Ticket abortado en modo web: ' . $ticket;
                } else {
                    throw new Exception("Acción no válida: $action");
                }

                $newConfirmLog = DB::connection(nuevaConexion($id))->table('logs')->insert([
                    'Type' => 'log',
                    'Text' => $text,
                    'Link' => '',
                    'DateTime' => $currentTime,
                    'DateTimeEx' => $micro,
                    'IP' => getRealIpAddr(),
                    'User' => $user
                ]);
            }
        } catch (Exception $e) {
            dd('Generando el log ' . $e);
            //return redirect()->route("tickets.index", $local)->withErrors(['error' => 'Ocurrió un error al generar logs']);
        }
    }

    public function generarTicket(Request $request, $local)
    {
        //dd(getRealIpAddr());

        try {
            //Log::info("Iniciando generación de ticket para el local: " . $local);

            // Validar la existencia del Local y ConfigMC antes de continuar
            $localData = Local::find($local);
            if (!$localData) {
                //Log::error("El local no existe.");
                throw new Exception("El local no existe.");
            }
            //Log::info("Local encontrado: " . json_encode($localData));

            $config = ConfigMC::where("local_id", $local)->first();
            if (!$config) {
                //Log::error("No se encontró configuración para el local.");
                throw new Exception("No se encontró configuración para el local.");
            }
            //Log::info("Configuración del local encontrada: " . json_encode($config));

            // Crear el nuevo ticket
            $newTicket = new Ticket();
            $newTicket->local_id = $local;
            $newTicket->idMachine = $localData->idMachines;
            $newTicket->Command = ($request->Value >= $config->MoneyLimitThatNeedsAuthorization) ? "AUTHREQ" : "OPEN";

            // Validar y asignar TicketNumber
            $newTicket->TicketNumber = (!$request->TicketNumber || strlen($request->TicketNumber) != $config->NumberOfDigits)
                ? GenerateNewNumberFormat($config->NumberOfDigits)
                : $request->TicketNumber;

            //Log::info("Número de ticket generado: " . $newTicket->TicketNumber);

            $newTicket->Mode = $request->Mode;
            $newTicket->DateTime = Carbon::now();
            $newTicket->LastCommandChangeDateTime = Carbon::now();
            $newTicket->LastIP = getRealIpAddr();
            $newTicket->LastUser = "Prometeo_" . auth()->user()->name;

            if ($request->Value < 1) {
                //Log::error("El valor del ticket es inválido: " . $request->Value);
                throw new Exception("El valor debe ser mayor o igual a 1.");
            }

            $newTicket->Value = $request->Value;
            $newTicket->Residual = 0;
            $newTicket->IP = getRealIpAddr();
            $newTicket->User = "Prometeo_" . auth()->user()->name;
            $newTicket->Comment = "Creado mediante Miniprometeo";

            if (empty($request->TicketTypeText) && empty($request->TicketTypeSelect)) {
                //Log::error("Tipo de ticket no seleccionado.");
                throw new Exception("Debes seleccionar un tipo de ticket.");
            }

            $newTicket->Type = !empty($request->TicketTypeText) ? $request->TicketTypeText : $request->TicketTypeSelect;

            if ($request->TicketTypeIsBets && $request->TicketTypeIsAux == 1) {
                //Log::error("Un ticket no puede ser apuesta y auxiliar al mismo tiempo.");
                throw new Exception("No puede ser apuesta y auxiliar a la vez.");
            }

            $newTicket->TypeIsBets = $request->TicketTypeIsBets ? true : false;
            $newTicket->TypeIsAux = $request->TicketTypeIsAux ?? 0;
            $newTicket->HideOnTC = 0;
            $newTicket->Used = 0;
            $newTicket->TITOExpirationType = 0;

            // Guardar en la base de datos
            $newTicket->save();
            //Log::info("Ticket guardado en la base de datos con ID: " . $newTicket->id);

            // Construcción del array de datos para la inserción manual
            $insertData = [
                'Command' => $newTicket->Command,
                'TicketNumber' => $newTicket->TicketNumber,
                'Mode' => $newTicket->Mode,
                'DateTime' => $newTicket->DateTime,
                'LastCommandChangeDateTime' => $newTicket->LastCommandChangeDateTime,
                'LastIP' => $newTicket->LastIP,
                'LastUser' => $newTicket->LastUser,
                'Value' => $newTicket->Value,
                'Residual' => $newTicket->Residual,
                'IP' => $newTicket->IP,
                'User' => $newTicket->User,
                'Comment' => $newTicket->Comment,
                'Type' => $newTicket->Type,
                'TypeIsBets' => $newTicket->TypeIsBets,
                'TypeIsAux' => $newTicket->TypeIsAux ?? 0,
                'HideOnTC' => $newTicket->HideOnTC,
                'Used' => $newTicket->Used,
                'TITOExpirationType' => $newTicket->TITOExpirationType,
                'UsedDateTime' => $newTicket->UsedDateTime,
                'ExpirationDate' => $newTicket->ExpirationDate = request()->has('expired') ? now() : '1970-01-01 01:01:01'
            ];

            // Si tiene fecha de expiración, agregarla
            /* if (isset($request->expired)) {
                $insertData['UsedDateTime'] = '1970-01-01 01:01:01';
                $insertData['ExpirationDate'] = '1970-01-01 01:01:01';
            }*/


            //Log::info("Datos a insertar en la base de datos externa: " . json_encode($insertData));

            // Insertar en la base de datos externa
            try {
                DB::connection(nuevaConexion($local))->table('tickets')->insert($insertData);
                //Log::info("Ticket insertado correctamente en la base de datos externa.");
            } catch (\Exception $e) {
                //Log::error("Error al insertar en la base de datos externa: " . $e->getMessage());
            }

            // Generar log del ticket
            $this->generarLogCreate($local, $newTicket);
            //Log::info("Log de ticket generado.");

            return redirect()->route("tickets.index", $local)->with('success', 'Ticket generado correctamente.');
        } catch (Exception $e) {
            //Log::error("Ocurrió un error al generar el ticket: " . $e->getMessage());
            return redirect()->route("tickets.index", $local)->withErrors(['error' => 'Ocurrió un error: ' . $e->getMessage()]);
        }
    }




    public function generarLogCreate($id, $ticket)
    {
        try {
            //dd($ticket);
            $text1 = "Ticket " . $ticket->Type;

            if ($ticket->TypeIsBets) {
                $text1 .= "[BETS]";
            } else if ($ticket->TypeIsAux != 0) {
                $text1 .= "[AUX-" . $ticket->TypeIsAux . "]";
            }

            $formattedValue = number_format($ticket->Value, 2);
            $text1 .= " creado en modo web " . $ticket->TicketNumber . " " . $formattedValue . "€ (" . $ticket->DateTime . ")";

            $currentTime = Carbon::now();
            $micro = sprintf("%06d", ($currentTime->micro / 1000));

            $newCreateLog = DB::connection(nuevaConexion($id))->table('logs')->insert([
                'Type' => 'log',
                'Text' => $text1,
                'Link' => '',
                'DateTime' => $currentTime,
                'DateTimeEx' => $micro,
                'IP' => getRealIpAddr(),
                'User' => auth()->user()->name
            ]);

            $text2 = $ticket->Type . "|" . $ticket->TicketNumber . "|" . $formattedValue . "|0.00|Prometeo|" . $ticket->DateTime . "||0|0000-00-00 00:00:00";

            $newCreateLogMovementTicket = DB::connection(nuevaConexion($id))->table('logs')->insert([
                'Type' => 'movementTicket',
                'Text' => $text2,
                'Link' => '',
                'DateTime' => $currentTime,
                'DateTimeEx' => $micro,
                'IP' => getRealIpAddr(),
                'User' =>  auth()->user()->name
            ]);
        } catch (Exception $e) {
            return redirect()->route("tickets.index", $id)->withErrors(['error' => 'Ocurrió un error al generar logs']);
        }
    }

    /*public function getAllTypes()
    {
        $types = TypeMachines::select('name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->get();

        return $types;
    }*/

    public function getAuxiliaresName($id)
    {
        $auxiliares = AuxMoneyStorage::where("local_id", $id)
            ->orderByRaw('CAST(TypeIsAux AS UNSIGNED) ASC') // Ordena numéricamente
            ->get();

        return $auxiliares;
    }
}
