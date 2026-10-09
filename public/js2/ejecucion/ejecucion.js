document.addEventListener("DOMContentLoaded", function() {
    // Función para filtrar columnas por el select (estado)
    function filterTable(colIndex, filterValue) {
        var table, tr, td, i, txtValue;
        table = document.getElementById("tabla");
        tr = table.getElementsByTagName("tr");

        // Filtrar la tabla por la columna seleccionada
        for (i = 1; i < tr.length; i++) { // Empieza desde el 1 para evitar los encabezados
            td = tr[i].getElementsByTagName("td")[colIndex];
            if (td) {
                txtValue = td.textContent || td.innerText;
                if (filterValue === "" || txtValue.toUpperCase().indexOf(filterValue) > -1) {
                    tr[i].setAttribute('data-filtered', 'true');  // Marcar la fila como que pasó el filtro
                    tr[i].style.display = "";  // Mostrar la fila
                } else {
                    tr[i].setAttribute('data-filtered', 'false'); // Marcar la fila como que no pasó el filtro
                    tr[i].style.display = "none";  // Ocultar filas que no cumplen
                }
            }
        }

        // Aplicar el filtro de búsqueda sobre las filas visibles
        applySearch();  // <- Aplica ambos filtros al mismo tiempo.
    }

    // Función para buscar dentro de las filas visibles
    function applySearch() {
        let filtro = document.getElementById("buscador_1").value.toLowerCase();
        let filas = document.querySelectorAll("#tabla tbody tr");

        // Si el buscador está vacío, mostrar solo las filas que pasaron el filtro del select
        if (filtro === "") {
            filas.forEach(fila => {
                if (fila.getAttribute('data-filtered') === 'true') {
                    fila.style.display = ""; // Mostrar fila si pasó el filtro del select
                }
            });
        } else {
            // Si hay un filtro de búsqueda, aplicar la lógica de búsqueda
            filas.forEach(fila => {
                if (fila.getAttribute('data-filtered') === 'true') { // Solo en las filas que pasaron el filtro del select
                    let textoFila = fila.textContent.toLowerCase();
                    if (textoFila.includes(filtro)) {
                        fila.style.display = ""; // Mostrar fila si coincide con el buscador
                    } else {
                        fila.style.display = "none"; // Ocultar fila si no coincide
                    }
                }
            });
        }
    }

    // Filtro automático al cargar la página con "Plan de pruebas"
    function applyInitialFilter() {
        const initialFilterValue = "PLAN DE PRUEBAS";  // Filtro por defecto al cargar
        document.getElementById("filterEstado").value = "Plan de pruebas"; // Asignar el valor al select
        filterTable(0, initialFilterValue);  // Aplicar el filtro automático
    }

    // Añadir evento al select para que filtre según el valor seleccionado
    document.getElementById("filterEstado").addEventListener("change", function() {
        let selectedValue = this.value.toUpperCase();
        filterTable(0, selectedValue); // Filtrar por la columna correcta cuando se cambia el select
    });

    // Añadir evento para la búsqueda en tiempo real
    document.getElementById("buscador_1").addEventListener("keyup", applySearch);

    // Aplicar el filtro inicial automáticamente al cargar la página
    applyInitialFilter();
});

function setIconRoutes() {
    const rows = document.querySelectorAll('#table-body tr');

    rows.forEach(row => {
        const tipoPrueba = row.children[0].textContent; // Obtener el valor de la columna "Tipo de prueba"
        const icon = row.querySelector('.action-icon'); // Seleccionar el ícono de la fila

        // Asignar rutas dinámicas según el valor de "Tipo de prueba"
        if (tipoPrueba === 'Plan de pruebas') {
            icon.onclick = () => {
                window.location.href = '/revisiones/ppcal';  // Ruta para "Plan de pruebas"
            };
        } else if (tipoPrueba === 'Control y revisión de modelo de datos') {
            icon.onclick = () => {
                window.location.href = '/revisiones/crmol'; // Ruta para "Control y revisión de modelo de datos"
            };
        } else {
            icon.onclick = () => {
                window.location.href = '/ruta-otro-tipo';  // Ruta para otros tipos de pruebas
            };
        }
    });
}

// Ejecutar la función después de cargar la página
window.onload = setIconRoutes;

