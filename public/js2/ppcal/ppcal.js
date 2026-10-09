// =======================================
// Importación de librerías y estilos
// =======================================

// Bootstrap JS y CSS
import '../components/bootstrap/js/bootstrap.bundle.js';
import '../components/bootstrap/js/bootstrap.bundle.min.css';

// Importar jQuery y asignarlo a $
import '../components/jquery/jquery.min.js';

// Importar jQuery UI widgets y estilos
import '../components/jquery-ui/jquery-ui.min.js';


// Estilos personalizados y FontAwesome
import '../../css/ppcal/ppcal.css';

// Importar Quill desde node_modules
import Quill from 'quill';
import 'quill/dist/quill.snow.css';


$(document).ready(function() {
    // =======================================
    // Variables globales
    // =======================================
    var stepCount = 1; // Contador inicial de pasos

    // Porcentaje para el ancho máximo
    const anchoPorcentaje = 0.8;

    // =======================================
    // Inicialización de componentes
    // =======================================
    function initializeComponents() {
        // Inicializar Quill en los editores existentes
        $('.quill-editor').each(function() {
            initializeQuillEditor(this);
        });

        // Habilitar drag and drop con jQuery UI solo desde el "handle"
        $('#stepsContainer').sortable({
            axis: 'y',
            handle: '.drag-handle',
            update: function(event, ui) {
                updateStepNumbers();
            }
        });

        // Actualizar los números de paso
        updateStepNumbers();
    }

    function initializeQuillEditor(element) {
        new Quill(element, {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [1, 2, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link', 'code-block'],
                    ['clean']
                ]
            },
            placeholder: 'Escribe aquí...'
        });
    }

    // Llamar a la función al cargar la página
    initializeComponents();

    // =======================================
    // Funciones auxiliares
    // =======================================
    function updateStepNumbers() {
        $('#stepsContainer .step').each(function(index) {
            $(this).find('label:first').text('Etapa ' + (index + 1) + ':');
            // No cambies el ID del elemento
            // $(this).attr('id', 'step_' + (index + 1));
        });

        // Mostrar u ocultar el botón de eliminar paso según el número de pasos
        var steps = $('#stepsContainer .step');
        if (steps.length === 1) {
            steps.find('.remove-step').hide();
        } else {
            steps.find('.remove-step').show();
        }
    }

    // =======================================
    // Manejo de eventos
    // =======================================
    $('#planType').on('change', function() {
        var selectedTemplate = $(this).val();

        // Realizar una petición AJAX para cargar el contenido de la plantilla seleccionada
        $.ajax({
            url: '/pruebas/ppcal/getPlantilla',
            type: 'GET',
            data: { plantilla: selectedTemplate },
            success: function(response) {
                // Actualizar el contenido dinámico con la plantilla recibida
                $('#dynamic-template-content').html(response.html);

                // Re-inicializar componentes si es necesario
                initializeComponents();
            },
            error: function(xhr, status, error) {
                console.error('Error al cargar la plantilla:', error);
            }
        });
    });

    // Función para agregar nuevos pasos
    $(document).on('click', '.add-step', function(e) {
        e.preventDefault();

        var currentStep = $(this).closest('.step');
        stepCount++;
        var newStepId = 'step_' + stepCount;

        var newStep = `
            <br> <!-- Agregar espacio arriba -->
            <div class="form-group step mb-5" id="${newStepId}">
                <div class="d-flex align-items-center mb-3">
                    <span class="drag-handle me-2">
                        <i class="fas fa-arrows-alt"></i>
                    </span>
                    <label class="flex-grow-1 mb-0">Paso ${stepCount}:</label>
                    <div class="action-icons">
                        <a href="#" class="add-step me-2" data-id="${newStepId}" title="Agregar Paso" aria-label="Agregar Paso">
                            <i class="fas fa-plus"></i>
                        </a>
                        <a href="#" class="remove-step me-2" data-id="${newStepId}" title="Eliminar Paso" aria-label="Eliminar Paso">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                        <a href="#" class="attach-file" data-id="${newStepId}" title="Adjuntar Archivos" aria-label="Adjuntar Archivos">
                            <i class="fas fa-paperclip"></i>
                        </a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label>Paso a seguir:</label>
                        <div id="stepTest_${newStepId}" class="quill-editor"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label>Resultado esperado:</label>
                        <div id="expectedResult_${newStepId}" class="quill-editor"></div>
                    </div>
                </div>
                <!-- Añadir input para los archivos -->
                <input type="file" id="fileInput_${newStepId}" class="file-input" data-id="${newStepId}" multiple style="display: none;">
                <!-- Contenedor para mostrar los archivos -->
                <div id="fileContainer_${newStepId}" class="file-container"></div>
            </div>
            <br> <!-- Agregar espacio abajo -->`;

        var newStepElement = $(newStep);

        // Insertar el nuevo paso después del paso actual
        currentStep.after(newStepElement);

        // Inicializar Quill en los nuevos editores
        initializeQuillEditor(document.getElementById('stepTest_' + newStepId));
        initializeQuillEditor(document.getElementById('expectedResult_' + newStepId));

        // Actualizar los números de los pasos
        updateStepNumbers();
    });

    // Función para eliminar pasos
    $(document).on('click', '.remove-step', function(e) {
        e.preventDefault();

        var stepId = $(this).data('id');
        $('#' + stepId).remove();

        // Actualizar los números de los pasos
        updateStepNumbers();
    });

    // Manejador de eventos para el botón "Adjuntar Archivo"
    $(document).on('click', '.attach-file', function(e) {
        e.preventDefault();

        var stepId = $(this).data('id');
        $('#fileInput_' + stepId).click();
    });

    // Manejador de eventos para el cambio en el campo de entrada de archivo
    $(document).on('change', '.file-input', function() {
        var stepId = $(this).data('id');
        var files = this.files;

        // Mostrar los archivos seleccionados
        var $fileContainer = $('#fileContainer_' + stepId);
        $fileContainer.html('');

        $.each(files, function(index, file) {
            $fileContainer.append(
                `<div class="file-item d-flex align-items-center" data-index="${index}">
                    <span class="me-2">${file.name}</span>
                    <a href="#" class="text-danger btn-delete-file" data-id="${stepId}" data-index="${index}" title="Eliminar archivo">
                        <i class="fas fa-times"></i>
                    </a>
                </div>`
            );
        });
    });

    // Manejador de eventos para eliminar archivos adjuntos
    $(document).on('click', '.btn-delete-file', function(e) {
        e.preventDefault();
        var stepId = $(this).data('id');
        var fileIndex = $(this).data('index');
        var fileInput = $('#fileInput_' + stepId)[0];

        // Crear una nueva lista de archivos sin el archivo eliminado
        var newFileList = Array.from(fileInput.files).filter((file, index) => index != fileIndex);

        // Crear un nuevo objeto DataTransfer y agregar los archivos restantes
        var dataTransfer = new DataTransfer();
        newFileList.forEach(file => dataTransfer.items.add(file));

        // Asignar la nueva lista de archivos al input
        fileInput.files = dataTransfer.files;

        // Actualizar la visualización de archivos
        $(this).closest('.file-item').remove();
    });

    // Para mostrar el nombre del archivo seleccionado
    // Calcular el ancho máximo del offcanvas
    var maxAncho = $(window).width() * anchoPorcentaje;

    // Hacer que el offcanvas sea redimensionable desde el lado izquierdo
    $('#offcanvasRight').resizable({
        handles: 'w',
        minWidth: 300, // Ancho mínimo
        maxWidth: maxAncho, // Ancho máximo al 80% del ancho de la ventana
        resize: function(event, ui) {
            ui.element.css({
                left: 'auto',
                right: 0
            });
        },
        start: function(event, ui) {
            ui.element.css({
                left: 'auto',
                right: 0
            });
        }
    });

    // Actualizar maxWidth cuando la ventana cambie de tamaño
    $(window).resize(function() {
        maxAncho = $(window).width() * anchoPorcentaje;
        $('#offcanvasRight').resizable('option', 'maxWidth', maxAncho);
    });

    // Activar el evento de abrir el offcanvas al hacer clic en una fila
    $('#tabla_planes tbody tr').on('click', function() {
        var offcanvasElement = new bootstrap.Offcanvas(document.getElementById('offcanvasRight'));
        offcanvasElement.show();
        var formData = $(this).serializeArray();
        $.ajax({
            type: "GET",
            url: "/planpruebas",
            data: formData,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                alert('asdas');
                console.log('sasdasdas');
            },
            error: function(xhr, status, error) {
                console.error('Error en la solicitud AJAX:', error);
                console.log('Detalles del error:', xhr.responseText);
            }
        });
    });

    // // Configuración de DataTable para #table7
    // // =======================================
    // // Inicialización de DataTables
    // // =======================================
    // const minDateEl = $('#minDate');
    // const maxDateEl = $('#maxDate');

    // const table7 = $('#table7').DataTable({
    //     paging: true,
    //     ordering: true,
    //     searching: true,
    //     responsive: true,
    //     language: {
    //         "decimal": "",
    //         "emptyTable": "No hay datos disponibles en la tabla",
    //         "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
    //         "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
    //         "infoFiltered": "(filtrado de _MAX_ entradas totales)",
    //         "infoPostFix": "",
    //         "thousands": ",",
    //         "lengthMenu": "Mostrar _MENU_ entradas",
    //         "loadingRecords": "Cargando...",
    //         "processing": "Procesando...",
    //         "search": "Buscar:",
    //         "zeroRecords": "No se encontraron resultados",
    //         "paginate": {
    //             "first": "Primero",
    //             "last": "Último",
    //             "next": "Siguiente",
    //             "previous": "Anterior"
    //         },
    //         "aria": {
    //             "sortAscending": ": activar para ordenar la columna de manera ascendente",
    //             "sortDescending": ": activar para ordenar la columna de manera descendente"
    //         }
    //     },
    //     // Puedes agregar más opciones aquí si lo deseas
    // });

    // // Función personalizada para filtrar por rango de fechas
    // $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
    //     const minDate = minDateEl.val() ? new Date(minDateEl.val()) : null;
    //     const maxDate = maxDateEl.val() ? new Date(maxDateEl.val()) : null;
    //     const creationDate = new Date(data[2]); // Ajusta el índice de columna según corresponda

    //     if (
    //         (!minDate && !maxDate) ||
    //         (!minDate && creationDate <= maxDate) ||
    //         (minDate <= creationDate && !maxDate) ||
    //         (minDate <= creationDate && creationDate <= maxDate)
    //     ) {
    //         return true;
    //     }
    //     return false;
    // });

    // // Actualiza la tabla cada vez que se cambia un filtro
    // minDateEl.on('change', function() {
    //     table7.draw();
    // });

    // maxDateEl.on('change', function() {
    //     table7.draw();
    // });

    // Si tienes otra tabla, puedes mantener esta sección
    /*
    // Configuración de DataTable para #miTabla (si tienes otra tabla)
    var miTabla = $('#miTabla').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
        },
        "dom": '<"top">rt<"bottom"ip><"clear">'
    });

    // Mueve el buscador al contenedor personalizado y alinea a la derecha
    $('#miTabla_filter').detach().appendTo('#datatable-search-container');
    $('#datatable-search-container').addClass('text-right');
    $('#miTabla_filter input').attr('placeholder', 'Buscar en la tabla...');
    $('#miTabla_filter label').contents().filter(function() {
        return this.nodeType === 3;
    }).remove();
    */

    // =======================================
    // Inicialización de otros componentes y eventos
    // =======================================
    // ... (resto de tu código JavaScript)
});

function cargarDatos(perfil) {
    $.ajax({
        type: "POST",
        url: "/menuPerfil",
        data: { perfil: perfil },
        dataType: "json",
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(response) {
            var listaHTML = '';

            // Agrupar elementos por sime_codigo para facilitar el acceso
            var menuPorCodigo = {};
            $.each(response.pmenu, function(index, mn) {
                if (!menuPorCodigo[mn.sime_codigo]) {
                    menuPorCodigo[mn.sime_codigo] = [];
                }
                menuPorCodigo[mn.sime_codigo].push(mn);
            });

            // Construir el menú HTML
            $.each(response.pmenu, function(index, mn) {
                if (mn.nivel === "0") {
                    var tieneHijos = menuPorCodigo[mn.sime_codigo] && menuPorCodigo[mn.sime_codigo].some(item => item.nivel === "1") && menuPorCodigo[mn.simm_codigo] != '0';

                    listaHTML += '<li class="nav-item' + (tieneHijos ? ' has-treeview' : '') + '" style="font-size: 12px;">';
                    listaHTML += '<a href="' + mn.url + '" class="nav-link">';
                    listaHTML += '<i class="' + mn.icono + 'as"></i>';
                    listaHTML += '&nbsp;&nbsp;&nbsp' + '<p class="as">' + mn.nombre + (tieneHijos ? '<i class="right fas fa-angle-left"></i>' : '') + '</p>';
                    listaHTML += '</a>';

                    if (tieneHijos) {
                        listaHTML += '<ul class="nav nav-treeview" style="font-size: 12px;">';

                        $.each(menuPorCodigo[mn.sime_codigo], function(index, mn2) {
                            if (mn2.nivel === "1") {
                                listaHTML += '<li class="nav-item">';
                                listaHTML += '<a href="' + mn2.url + '" class="nav-link pr-2">';
                                listaHTML += '<i class="' + mn2.icono + ' as"></i>';
                                listaHTML += '&nbsp;&nbsp;&nbsp' + '<p class="as">' + mn2.nombre + '</p>';
                                listaHTML += '</a>';
                                listaHTML += '</li>';
                            }
                        });

                        listaHTML += '</ul>';
                    }

                    listaHTML += '</li>';
                }
            });

            $('#pmenu').html(listaHTML);

            // Función para resaltar el menú después de cargarlo
            resaltarmenu();
        }
    });
}

function resaltarmenu() {
    alert('asdasdas');
    var path = window.location.pathname;
    var menuLinks = $("#pmenu .nav-link");

    // Contraer todos los menús que estén expandidos
    $("#pmenu .has-treeview").removeClass('menu-open');

    menuLinks.each(function() {
        var link = $(this).attr("href");
        if (path === link) {
            // Agrega la clase "active" al enlace correspondiente
            $(this).addClass("active");

            // Expandir el menú padre del enlace activo
            $(this).closest('.has-treeview').addClass('menu-open');
        }
    });
}
