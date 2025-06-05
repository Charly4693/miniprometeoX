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

            const company = companyResponse.company; // Obtenemos datos de compania, ip y  port de servidor de Prometeo de compania
            const ipPrometeoPropio = company.ip;
            const portPrometeoPropio = company.port;

            const saveCompanyResponse = await sendPostRequest(
                "{{ route('configuration.save_company') }}", company
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
