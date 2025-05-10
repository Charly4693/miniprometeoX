document.addEventListener('DOMContentLoaded', function () {
    let filaEnEdicion = null;
    let valoresOriginales = {}; // Objeto para almacenar los valores originales

    const editButtons = document.querySelectorAll('.edit');
    const volverButtons = document.querySelectorAll('.volver');
    const crearButtons = document.querySelectorAll('.crear');

    editButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            console.log("Botón de edición presionado"); // Log al presionar editar

            if (filaEnEdicion) {
                console.log("Ya hay una fila en edición. No se puede editar otra.");
                return;
            }

            const rowId = button.getAttribute('data-row');
            const row = document.querySelector(`[data-row='${rowId}']`);

            if (!row) {
                console.error(`Fila no encontrada para ID: ${rowId}`); // Log de error
                return;
            }

            filaEnEdicion = row; // Guardamos la fila en edición
            console.log("Fila en edición:", filaEnEdicion);

            // Deshabilitar edición y eliminación en otras filas
            editButtons.forEach(btn => btn.disabled = true);
            document.querySelectorAll('.btn-danger').forEach(btn => btn.disabled = true); // Deshabilitar los botones de eliminar

            // Ocultar botón de edición y mostrar los de guardar, crear y volver
            button.classList.add('d-none');
            row.querySelector('.guardar').classList.remove('d-none');
            row.querySelector('.volver').classList.remove('d-none');

            const select = row.querySelector('.select-control');

            // Guardar los valores originales antes de editar
            valoresOriginales[rowId] = {
                select: select ? select.value : null
            };
            console.log("Valores originales guardados:", valoresOriginales[rowId]);

            // Habilitar los inputs
            if (select) select.removeAttribute('disabled');

            // Mostrar el botón de "Crear" o "Guardar"
            if (select.value) {
                row.querySelector('.guardar').classList.remove('d-none');
                row.querySelector('.crear').classList.add('d-none');
            } else {
                row.querySelector('.crear').classList.remove('d-none');
                row.querySelector('.guardar').classList.add('d-none');
            }
        });
    });

    volverButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            console.log("Botón de volver presionado"); // Log al presionar volver
            const rowId = button.getAttribute('data-row');
            const row = document.querySelector(`[data-row='${rowId}']`);

            if (!row) {
                console.error(`Fila no encontrada para ID: ${rowId}`); // Log de error
                return;
            }

            filaEnEdicion = null; // Liberamos la fila en edición
            console.log("Fila en edición liberada");

            // Restaurar la edición de otras filas
            editButtons.forEach(btn => btn.disabled = false);
            document.querySelectorAll('.btn-danger').forEach(btn => btn.disabled = false); // Habilitar los botones de eliminar

            const select = row.querySelector('.select-control');

            // Restaurar los valores originales
            if (select && valoresOriginales[rowId]) {
                select.value = valoresOriginales[rowId].select;
                console.log("Valores restaurados:", valoresOriginales[rowId]);
            }

            // Restaurar a solo lectura
            if (select) select.setAttribute('disabled', true);

            // Ocultar los botones de guardar y volver
            row.querySelector('.guardar').classList.add('d-none');
            row.querySelector('.volver').classList.add('d-none');
            row.querySelector('.crear').classList.add('d-none');

            // Mostrar el botón de edición
            row.querySelector('.edit').classList.remove('d-none');

            // Eliminar los valores almacenados después de restaurarlos
            delete valoresOriginales[rowId];
        });
    });

    // Selecciona todos los botones con la clase 'guardar'
    document.querySelectorAll('.guardar').forEach(button => {
        button.addEventListener("click", function () {
            let ticketNumber = this.getAttribute("data-row"); // Número de ticket
            console.log("Botón de guardar presionado para ticket:", ticketNumber);

            // Obtener el select del alias
            let aliasSelect = document.querySelector(`tr[data-row="${ticketNumber}"] select[name="alias"]`);
            let selectedAlias = aliasSelect.options[aliasSelect.selectedIndex].text; // Obtener el alias (texto visible)
            let selectedIdMachine = aliasSelect.options[aliasSelect.selectedIndex].value; // Obtener el alias (texto visible)

            console.log("Alias seleccionado:", selectedAlias); // Log del alias seleccionado

            // Obtener el select del id_machine
            console.log("Valor de id_machine seleccionado:", selectedIdMachine); // Log del id_machine

            // Validar que se haya seleccionado un alias
            const errorElement = document.getElementById(`error_${ticketNumber}`);
            if (!selectedAlias) {
                // Mostrar mensaje de error
                errorElement.classList.remove('d-none'); // Mostrar mensaje de error
                console.warn(`No se seleccionó un alias para TicketNumber: ${ticketNumber}`);
                return; // Detener la ejecución
            } else {
                errorElement.classList.add('d-none'); // Ocultar mensaje de error
            }

            // Buscar el input hidden dentro del modal
            let modal = document.querySelector(`#modalAccionesLocal${ticketNumber}`);
            let aliasInput = modal ? modal.querySelector(`input[name="alias"]`) : null;
            let idMachineInput = modal ? modal.querySelector(`input[name="id_machine"]`) : null; // Buscar el input del id_machine

            if (aliasInput) {
                aliasInput.value = selectedAlias; // Asignar el alias real al input hidden
                console.log(`Alias guardado en input: ${aliasInput.value}`); // Log del alias guardado
            } else {
                console.error(`No se encontró input[name="alias"] en el formulario del modal para el ticket ${ticketNumber}`); // Log de error
            }

            if (idMachineInput) {
                idMachineInput.value = selectedIdMachine; // Asignar el id_machine real al input hidden
                console.log(`id_machine asignado: ${selectedIdMachine}`); // Log de verificación
            } else {
                console.error(`No se encontró input[name="id_machine"] en el formulario del modal para el ticket ${ticketNumber}`); // Log de error
            }

            // Actualizar el texto visible en el modal
            let aliasText = document.querySelector(`#selected-alias-${ticketNumber}`);
            if (aliasText) {
                aliasText.innerText = selectedAlias ? selectedAlias : 'Sin alias';
            }

            // Abrir el modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show(); // Mostrar el modal

            console.log(`Modal rellenado y abierto para TicketNumber: ${ticketNumber}`);
        });
    });






    // Selecciona todos los botones con la clase 'crear'
    document.querySelectorAll('.crear').forEach(button => {
        button.addEventListener('click', function () {
            let ticketNumber = this.getAttribute('data-row');
            let tipo = this.getAttribute('data-tipo');
            let idMachine = this.getAttribute('data-maquina-id');
            let alias = this.getAttribute('data-alias');

            // Registrar los datos obtenidos
            console.log(`Botón clicado para TicketNumber: ${ticketNumber}, Tipo: ${tipo}, ID Máquina: ${idMachine}, Alias: ${alias}`);

            // Validar que hay un alias seleccionado antes de continuar
            const select = document.querySelector(`tr[data-row="${ticketNumber}"] select[name="alias"]`);
            const errorElement = document.getElementById(`error_${ticketNumber}`);

            if (!select.value) {
                // Mostrar mensaje de error
                errorElement.classList.remove('d-none'); // Mostrar mensaje de error
                console.warn(`No se seleccionó un alias para TicketNumber: ${ticketNumber}`);
                return; // Detener la ejecución
            } else {
                errorElement.classList.add('d-none'); // Ocultar mensaje de error
            }

            // Seleccionar el modal basado en el número del ticket
            let modal = document.querySelector(`#modalCrearTipoAlias${ticketNumber}`);
            if (modal) {
                // Rellenar los campos del modal
                modal.querySelector('#nuevoTipo').value = tipo || ''; // Asegúrate que no sea undefined
                modal.querySelector('#nuevoAlias').value = alias || ''; // Asegúrate que no sea undefined
                modal.querySelector('#idMachine').value = idMachine || ''; // Asegúrate que no sea undefined
                modal.querySelector('#tipoMostrar').innerText = tipo || 'Sin tipo'; // Mostrar un valor por defecto
                modal.querySelector('#aliasMostrar').innerText = alias || 'Sin alias'; // Mostrar un valor por defecto

                // Abrir el modal
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show(); // Mostrar el modal

                console.log(`Modal rellenado y abierto para TicketNumber: ${ticketNumber}`);
            } else {
                console.error(`Modal no encontrado para TicketNumber: ${ticketNumber}`);
            }
        });
    });

    // Agregar el listener de eventos a crearButtons (suponiendo que tienes este elemento definido)
    crearButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            const ticketNumber = this.getAttribute('data-row'); // Obtener el TicketNumber
            console.log(`Botón de crear presionado para ticket: ${ticketNumber}`);
            validarSeleccionAlias(ticketNumber, this);
        });
    });

    // Validar la selección de alias
    function validarSeleccionAlias(ticketNumber, button) {
        const select = document.querySelector(`tr[data-row="${ticketNumber}"] select[name="alias"]`);
        const errorElement = document.getElementById(`error_${ticketNumber}`);
        const tipoMaquinaElement = document.querySelector(`tr[data-row="${ticketNumber}"] td input[type="text"]`);

        if (select) {
            const selectedValue = select.value; // ID del alias seleccionado
            const selectedText = select.options[select.selectedIndex]?.text || 'Sin alias'; // Texto del alias seleccionado
            const tipoMaquina = tipoMaquinaElement ? tipoMaquinaElement.value : ''; // Tipo de máquina

            // Registrar la información de validación
            console.log(`Validando selección de alias: ID=${selectedValue}, Texto=${selectedText}, Tipo=${tipoMaquina}`);

            if (!selectedValue) {
                errorElement.classList.remove('d-none'); // Mostrar mensaje de error
                console.warn(`No se seleccionó un alias para TicketNumber: ${ticketNumber}`);
                return; // Detener la ejecución
            } else {
                errorElement.classList.add('d-none'); // Ocultar mensaje de error
            }

            // Rellenar los valores del modal antes de abrirlo
            const modalId = `#modalCrearTipoAlias${ticketNumber}`;
            const modal = document.querySelector(modalId);
            if (modal) {
                modal.querySelector("#nuevoTipo").value = tipoMaquina;
                modal.querySelector("#nuevoAlias").value = selectedText; // Usar el texto del alias seleccionado
                modal.querySelector("#idMachine").value = selectedValue; // ID de la máquina desde el botón

                // Actualizar la vista previa del modal
                modal.querySelector("#tipoMostrar").textContent = tipoMaquina;
                modal.querySelector("#aliasMostrar").textContent = selectedText;

                // Abrir el modal manualmente
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show(); // Abrir el modal si la validación es exitosa

                console.log(`Modal abierto para TicketNumber: ${ticketNumber}`);
            } else {
                console.error(`Modal no encontrado para el ticket: ${ticketNumber}`);
            }
        } else {
            console.error(`Elemento select no encontrado para el ticket: ${ticketNumber}`);
        }
    }






    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function () {
            document.body.classList.remove('modal-open');
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
        });
    });

});

function prepararModalCrear(tipo, idMachine, alias, ticketNumber) {
    // Asignar valores a los inputs ocultos
    document.getElementById('nuevoTipo').value = tipo;
    document.getElementById('nuevoAlias').value = alias;
    document.getElementById('idMachine').value = idMachine;

    // Mostrar valores en el modal para referencia
    document.getElementById('tipoMostrar').innerText = tipo;
    document.getElementById('aliasMostrar').innerText = alias;

    // Mostrar el modal
    const modal = new bootstrap.Modal(document.getElementById(`modalCrearTipoAlias${ticketNumber}`));
    modal.show();
}


function enviarDatosCrear(ticketNumber) {
    // Obtener los datos del formulario
    var tipo = document.getElementById('nuevoTipo').value;
    var alias = document.getElementById('nuevoAlias').value;
    var maquinaId = document.getElementById('idMachine').value; // Capturar el id_machine

    console.log(`Enviando datos crear: tipo=${tipo}, alias=${alias}, maquinaId=${maquinaId}`); // Log de envío

    // Crear un formulario para enviar los datos
    var form = document.createElement('form');
    form.action = "{{ route('configurationTypeAlias.store') }}"; // Cambia a la ruta correspondiente
    form.method = "POST";

    // Agregar el token CSRF
    var csrfInput = document.createElement('input');
    csrfInput.type = "hidden";
    csrfInput.name = "_token";
    csrfInput.value = "{{ csrf_token() }}"; // Asumiendo que tienes el token disponible
    form.appendChild(csrfInput);

    // Agregar los datos al formulario
    var tipoInput = document.createElement('input');
    tipoInput.type = "hidden";
    tipoInput.name = "type";
    tipoInput.value = tipo;
    form.appendChild(tipoInput);

    var aliasInput = document.createElement('input');
    aliasInput.type = "hidden";
    aliasInput.name = "alias";
    aliasInput.value = alias;
    form.appendChild(aliasInput);

    // Agregar el ID de la máquina
    var maquinaIdInput = document.createElement('input');
    maquinaIdInput.type = "hidden";
    maquinaIdInput.name = "id_machine";
    maquinaIdInput.value = maquinaId; // Usar el id_machine capturado
    form.appendChild(maquinaIdInput);

    // Agregar el formulario al body y enviarlo
    document.body.appendChild(form);
    form.submit();
}
