document.addEventListener("DOMContentLoaded", function () {

    const generarTicketBtn = document.getElementById("openModalButton");
    const modal = new bootstrap.Modal(document.getElementById("CrearModal"));
    if (!generarTicketBtn) {
        console.error("El botón Generar no existe en el DOM");
        return;
    }
    generarTicketBtn.addEventListener("click", function (event) {
        event.preventDefault(); // Evita cualquier acción predeterminada

        // Capturar valores del formulario
        const value = document.querySelector('input[name="Value"]').value || "0.0";
        let ticketNumber = document.querySelector('input[name="TicketNumber"]').value.trim();
        const ticketTypeSelect = document.querySelector('select[name="TicketTypeSelect"]');
        const ticketTypeText = document.querySelector('input[name="TicketTypeText"]').value.trim();
        const ticketTypeIsAuxSelect = document.querySelector('select[name="TicketTypeIsAux"]');
        const ticketTypeIsBets = document.querySelector('input[name="TicketTypeIsBets"]').checked ? "Sí" : "No";
        const expired = document.querySelector('input[name="expired"]').checked ? "Sí" : "No";

        // Validaciones
        let hasError = false;
        document.querySelectorAll('.error-message').forEach(el => el.remove());

        if (!value || parseFloat(value) <= 0) {
            showError('input[name="Value"]', 'El valor del ticket debe ser mayor a 0');
            hasError = true;
        }

        if (!ticketTypeSelect || ticketTypeSelect.value === "null") {
            showError('select[name="TicketTypeSelect"]', 'Debes seleccionar un tipo de ticket');
            hasError = true;
        }

        // Solo validamos `TicketTypeIsAux` si `ticketTypeIsBets` NO está marcado como "Sí"
        if (ticketTypeIsBets === "No") {
            if (!ticketTypeIsAuxSelect || ticketTypeIsAuxSelect.value === "") {
                // Aceptamos "0" como válido, solo rechazamos valores vacíos
                showError('select[name="TicketTypeIsAux"]', 'Debes seleccionar una auxiliar');
                hasError = true;
            }
        }

        if (hasError) return; // No abrir el modal si hay errores

        // Obtener el texto del tipo de ticket seleccionado
        let ticketType = "Selecciona un tipo";
        if (ticketTypeSelect && ticketTypeSelect.selectedIndex !== -1) {
            ticketType = ticketTypeSelect.options[ticketTypeSelect.selectedIndex].text.trim();
        }

        // Si el usuario ha ingresado un tipo de ticket manualmente, usarlo
        if (ticketType === "Otro..." && ticketTypeText) {
            ticketType = ticketTypeText;
        }

        // Obtener el texto del tipo de recarga auxiliar seleccionado
        let ticketTypeIsAux = "Selecciona una auxiliar";
        if (ticketTypeIsAuxSelect && ticketTypeIsAuxSelect.selectedIndex !== -1) {
            ticketTypeIsAux = ticketTypeIsAuxSelect.options[ticketTypeIsAuxSelect.selectedIndex].text.trim();
        }

        // Si no se ingresó número de ticket, poner "Aleatorio"
        if (!ticketNumber) {
            ticketNumber = "Aleatorio";
        }

        // Actualizar los valores en el modal
        document.getElementById("summaryValue").textContent = `${value} €`;
        document.getElementById("summaryTicketNumber").textContent = ticketNumber;
        document.getElementById("summaryTicketType").textContent = ticketType;
        document.getElementById("summaryTicketTypeIsBets").textContent = ticketTypeIsBets;
        document.getElementById("summaryTicketTypeIsAux").textContent = ticketTypeIsAux;
        document.getElementById("summaryExpired").textContent = expired;

        console.log("Modal actualizado con éxito:", {
            value,
            ticketNumber,
            ticketType,
            ticketTypeIsBets,
            ticketTypeIsAux,
            expired
        });

        modal.show(); // Abre el modal manualmente
    });

    function showError(selector, message) {
        const input = document.querySelector(selector);
        if (input) {
            const errorElement = document.createElement('div');
            errorElement.className = 'text-danger error-message';
            errorElement.textContent = message;
            input.parentNode.appendChild(errorElement);
        }
    }

    let ticketTypeText = document.getElementById("ticketTypeText");
    const check = document.querySelector('input[name="TicketTypeIsBets"]');
    const selectAux = document.querySelector('select[name="TicketTypeIsAux"]');
    const reset = document.querySelector('input[type="reset"]');
    const ticketTypeSelect = document.getElementById("ticketTypeSelect");
    const ticketTypeTextHidden = document.getElementById("ticketTypeTextHidden"); // Input oculto
    const customTicketType = document.getElementById("customTicketType"); // Input visible

    // Manejar el cambio en el tipo de ticket
    ticketTypeSelect.addEventListener("change", function () {
        if (this.value === "other") {
            customTicketType.classList.remove("d-none"); // Mostrar el input
            customTicketType.removeAttribute("disabled"); // Habilitar input
            customTicketType.value = ""; // Limpiar campo
            customTicketType.focus(); // Enfocar campo
            ticketTypeTextHidden.value = ""; // Limpiar el oculto
        } else {
            customTicketType.classList.add("d-none"); // Ocultar input
            customTicketType.setAttribute("disabled", "true"); // Deshabilitar
            ticketTypeTextHidden.value = this.options[this.selectedIndex].text.trim(); // Guardar selección
        }
    });

    // Si el usuario escribe en el input, actualizar el valor oculto
    customTicketType.addEventListener("input", function () {
        ticketTypeTextHidden.value = customTicketType.value;
    });


    // Manejar el checkbox de apuestas
    check.addEventListener('change', function () {
        if (check.checked) {
            selectAux.value = 0;
            selectAux.disabled = true;
        } else {
            selectAux.disabled = false;
        }
    });

    // Habilitar selectAux al resetear el formulario
    reset.addEventListener('click', function () {
        selectAux.disabled = false;
    });

    // Ocultar alertas después de unos segundos
    const errorAlert = document.getElementById('error-alert');
    if (errorAlert) {
        setTimeout(() => {
            errorAlert.classList.add('fade-out');
        }, 5000);
        setTimeout(() => {
            errorAlert.remove();
        }, 7000);
    }

    // Guardar y restaurar la pestaña activa
    const tabs = document.querySelectorAll('button[data-bs-toggle="tab"]');

    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (e) {
            localStorage.setItem('activeTab', e.target.id);
        });
    });

    const activeTab = localStorage.getItem('activeTab');
    if (activeTab) {
        const tab = document.getElementById(activeTab);
        if (tab) {
            const bsTab = new bootstrap.Tab(tab);
            bsTab.show();
        }
    }
});


let estatuspc = 0;
let checkspc = document.getElementsByClassName("checkboxpc");
const abortarbtnpc = document.getElementById("abortarBtnPc");

let estatustlf = 0;
let checkstlf = document.getElementsByClassName("checkboxtlf");
const abortarBtnTlf = document.getElementById("abortarBtnTlf");

function countSelectedCheckboxes() {
    let selectedCount = 0;
    for (let i = 0; i < checkspc.length; i++) {
        if (checkspc[i].checked) {
            selectedCount++;
        }
    }
    return selectedCount;
}

for (let i = 0; i < checkspc.length; i++) {
    checkspc[i].addEventListener("change", function () {
        estatuspc = countSelectedCheckboxes();

        if (estatuspc <= 0) {
            abortarbtnpc.style.visibility = "hidden";
        } else {
            abortarbtnpc.style.visibility = "visible";
        }
    });
}

if (countSelectedCheckboxes() <= 0) {
    abortarbtnpc.style.visibility = "hidden";
}

function countSelectedCheckboxestlf() {
    let selectedCount = 0;
    for (let i = 0; i < checkstlf.length; i++) {
        if (checkstlf[i].checked) {
            selectedCount++;
        }
    }
    return selectedCount;
}

for (let i = 0; i < checkstlf.length; i++) {
    checkstlf[i].addEventListener("change", function () {
        estatustlf = countSelectedCheckboxestlf();
        if (estatustlf <= 0) {
            abortarBtnTlf.style.visibility = "hidden";
        } else {
            abortarBtnTlf.style.visibility = "visible";
        }
    });
}

if (countSelectedCheckboxestlf() <= 0) {
    abortarBtnTlf.style.visibility = "hidden";
}

// Script for confirm button visibility
let estatusConfPc = 0;
let checksConfPc = document.getElementsByClassName("checkboxConfpc");
const confirmarBtnPc = document.getElementById("confirmarBtnPc");

let estatusConfTlf = 0;
let checksConfTlf = document.getElementsByClassName("checkboxConftlf");
const confirmarBtnTlf = document.getElementById("confirmarBtnTlf");

function countSelectedCheckboxesConfPc() {
    let selectedCount = 0;
    for (let i = 0; i < checksConfPc.length; i++) {
        if (checksConfPc[i].checked) {
            selectedCount++;
        }
    }
    return selectedCount;
}

for (let i = 0; i < checksConfPc.length; i++) {
    checksConfPc[i].addEventListener("change", function () {
        estatusConfPc = countSelectedCheckboxesConfPc();

        if (estatusConfPc <= 0) {
            confirmarBtnPc.style.visibility = "hidden";
        } else {
            confirmarBtnPc.style.visibility = "visible";
        }
    });
}

if (countSelectedCheckboxesConfPc() <= 0) {
    if (confirmarBtnPc) {
        confirmarBtnPc.style.visibility = "hidden";
    }
}

function countSelectedCheckboxesConfTlf() {
    let selectedCount = 0;
    for (let i = 0; i < checksConfTlf.length; i++) {
        if (checksConfTlf[i].checked) {
            selectedCount++;
        }
    }
    return selectedCount;
}

for (let i = 0; i < checksConfTlf.length; i++) {
    checksConfTlf[i].addEventListener("change", function () {
        estatusConfTlf = countSelectedCheckboxesConfTlf();
        if (estatusConfTlf <= 0) {
            confirmarBtnTlf.style.visibility = "hidden";
        } else {
            confirmarBtnTlf.style.visibility = "visible";
        }
    });
}

if (countSelectedCheckboxesConfTlf() <= 0) {

    if (confirmarBtnTlf) {
        confirmarBtnTlf.style.visibility = "hidden";
    }
}
