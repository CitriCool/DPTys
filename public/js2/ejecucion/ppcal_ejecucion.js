import '../components/jquery/jquery.min.js';
import '../components/jquery/jquery-ui-min.js';

import '../components/bootstrap/js/bootstrap.bundle.min.js';
import '../components/bootstrap/js/bootstrap.bundle.min.css';

import '../components/summernote/summernote-bs4.min.js';
import '../components/summernote/summernote-bs4.min.css';

document.querySelectorAll('#detalles').forEach(detalle => {
    detalle.addEventListener('click', function() {
        var offcanvasElement = new bootstrap.Offcanvas(document.getElementById('offcanvasRight'));
        offcanvasElement.show();
    });
});

const boton = document.getElementById('mostrar-boton1');
const input = document.getElementById('mi-input1');
let estado = false; // Variable para llevar el control del estado del input



boton.addEventListener('click', () => {
    estado = !estado; // Invertimos el estado en cada clic
    input.style.display = estado ? 'block' : 'none';
});

const boton1 = document.getElementById('mostrar-boton');
const input1 = document.getElementById('mi-input');
let estado1 = false; // Variable para llevar el control del estado del input

boton1.addEventListener('click', () => {

    estado1 = !estado1; // Invertimos el estado en cada clic
    input1.style.display = estado1 ? 'block' : 'none';
});






const button = document.getElementById('agregar_pasos_adicionales');
  const div = document.getElementById('stepsContainer');

  button.addEventListener('click',   
 () => {
    if (div.style.display === 'none') {
      div.style.display = 'block';
    } else {
      div.style.display   
 = 'none';
    }
  });
  
document.getElementById("buscador_1").addEventListener("keyup", function() {
    // Obtener el valor del input y pasarlo a minúsculas
    let filtro = this.value.toLowerCase();
    
    // Obtener las filas de la tabla
    let filas = document.querySelectorAll("#tabla tbody tr");

    // Recorrer las filas de la tabla y esconder aquellas que no coincidan con el filtro
    filas.forEach(fila => {
        let textoFila = fila.textContent.toLowerCase();
        if (textoFila.includes(filtro)) {
            fila.style.display = ""; // Mostrar la fila
        } else {
            fila.style.display = "none"; // Ocultar la fila
        }
    });
});



//Filtro para Pasos Aprobados



document.getElementById("buscador_3").addEventListener("keyup", filtrarTabla);
document.getElementById("filtro_categoria1").addEventListener("change", filtrarTabla);

function filtrarTabla() {
    // Obtener valor del campo de búsqueda y convertir a minúsculas
    let filtroTexto = document.getElementById("buscador_3").value.toLowerCase();
    
    // Obtener valor del filtro de categoría
    let filtroCategoria = document.getElementById("filtro_categoria1").value.toLowerCase();
    
    // Obtener todas las filas de la tabla
    let filas = document.querySelectorAll("#tabla_rechazo1 tbody tr");

    // Recorrer cada fila
    filas.forEach(fila => {
        let textoFila = fila.textContent.toLowerCase();  // Todo el texto de la fila
        let categoriaFila = fila.querySelector("td").textContent.toLowerCase();  // Solo la categoría de la fila
        
        // Verificar si la fila coincide con el filtro de texto y categoría
        if ((textoFila.includes(filtroTexto) || filtroTexto === "") && 
            (categoriaFila.includes(filtroCategoria) || filtroCategoria === "")) {
            fila.style.display = "";  // Mostrar la fila
        } else {
            fila.style.display = "none";  // Ocultar la fila
        }
    });
}


//Filtro para Pasos Rechazados

document.getElementById("buscador_2").addEventListener("keyup", filtrarTabla2);
document.getElementById("filtro_categoria").addEventListener("change", filtrarTabla2);

function filtrarTabla2() {
    // Obtener valor del campo de búsqueda y convertir a minúsculas
    let filtroTexto = document.getElementById("buscador_2").value.toLowerCase();
    
    // Obtener valor del filtro de categoría
    let filtroCategoria = document.getElementById("filtro_categoria").value.toLowerCase();
    
    // Obtener todas las filas de la tabla
    let filas = document.querySelectorAll("#tabla_rechazo tbody tr");

    // Recorrer cada fila
    filas.forEach(fila => {
        let textoFila = fila.textContent.toLowerCase();  // Todo el texto de la fila
        let categoriaFila = fila.querySelector("td").textContent.toLowerCase();  // Solo la categoría de la fila
        
        // Verificar si la fila coincide con el filtro de texto y categoría
        if ((textoFila.includes(filtroTexto) || filtroTexto === "") && 
            (categoriaFila.includes(filtroCategoria) || filtroCategoria === "")) {
            fila.style.display = "";  // Mostrar la fila
        } else {
            fila.style.display = "none";  // Ocultar la fila
        }
    });
}

//Switch de checkbox

$(document).ready(function() {
    $('input[type="checkbox"]').on('change', function() {
        var group = 'input[name="' + $(this).attr('name') + '"]';
        $(group).not(this).prop('checked', false);
    });
});

//Seteo de los Pasos Antes de la Revisión

$(document).ready(function() {
    var stepCount = 1; // Contador inicial de pasos

    // Función para inicializar componentes
    function initializeComponents() {
        // Inicializar Summernote en los editores
        $('.summernote-editor').summernote({
            height: 100,
            toolbar: [
                ['style', ['bold', 'italic', 'underline']],
                ['para', ['ul', 'ol']],
                ['insert', ['link']],
                ['view', ['fullscreen', 'codeview']],
            ],
            placeholder: 'Escribe aquí...'
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

    // Función para actualizar los números de paso
    function updateStepNumbers() {
        var steps = $('#stepsContainer .step');
        steps.each(function(index) {
            $(this).find('label').text('Paso ' + (index + 1) + ':');
        });

        // Mostrar u ocultar el botón de eliminar paso según el número de pasos
        if (steps.length === 1) {
            steps.find('.remove-step').hide();
        } else {
            steps.find('.remove-step').show();
        }
    }

    // Llamar a la función al cargar la página
    initializeComponents();
     // Función para agregar nuevos pasos
     $(document).on('click', '.add-step', function(e) {
        e.preventDefault();

        var currentStep = $(this).closest('.step'); // Paso actual
        stepCount++;
        var newStepId = 'step_' + stepCount;

        var newStep = `
            <div class="form-group step" id="${newStepId}">
                <div class="d-flex align-items-center">
                    <span class="drag-handle me-2">
                        <i class="fas fa-arrows-alt"></i>
                    </span>
                    <label class="flex-grow-1 mb-0">Paso X:</label>
                    <div class="action-icons">
                        <a href="#" class="add-step" data-id="${newStepId}" title="Agregar Paso" aria-label="Agregar Paso">
                            <i class="fas fa-plus"></i>
                        </a>
                        <a href="#" class="remove-step" data-id="${newStepId}" title="Eliminar Paso" aria-label="Eliminar Paso">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                        <a href="#" class="attach-file" data-id="${newStepId}" title="Adjuntar Archivos" aria-label="Adjuntar Archivos">
                            <i class="fas fa-paperclip"></i>
                        </a>
                    </div>
                </div>
                <input type="file" id="fileInput_${newStepId}" class="file-input" data-id="${newStepId}" style="display: none;" multiple>
                <div class="file-name" id="fileName_${newStepId}"></div>
                <div class="row">
                    <div class="col-md-6">
                        <div id="stepTest_${newStepId}" class="summernote-editor"></div>
                    </div>
                    <div class="col-md-6">
                        <div id="expectedResult_${newStepId}" class="summernote-editor"></div>
                    </div>
                </div>
            </div>`;

        var newStepElement = $(newStep);

        // Insertar el nuevo paso después del paso actual
        currentStep.after(newStepElement);

        // Inicializar Summernote en los nuevos editores
        $('#stepTest_' + newStepId).summernote({
            height: 100,
            toolbar: [
                ['style', ['bold', 'italic', 'underline']],
                ['para', ['ul', 'ol']],
                ['insert', ['link']],
                ['view', ['fullscreen', 'codeview']],
            ],
            placeholder: 'Pasos de la prueba'
        });

        $('#expectedResult_' + newStepId).summernote({
            height: 100,
            toolbar: [
                ['style', ['bold', 'italic', 'underline']],
                ['para', ['ul', 'ol']],
                ['insert', ['link']],
                ['view', ['fullscreen']]
            ],
            placeholder: 'Resultado esperado'
        });

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
        var fileName = this.files[0].name;
        $('#fileName_' + stepId).text('Archivo adjuntado: ' + fileName);
    });

    // Configuraciones adicionales (si las tienes)
});
