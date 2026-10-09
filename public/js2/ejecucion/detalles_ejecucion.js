
document.querySelectorAll('#detalles').forEach(detalle => {
    detalle.addEventListener('click', function() {
        var offcanvasElement = new bootstrap.Offcanvas(document.getElementById('offcanvasRight'));
        offcanvasElement.show();
    });
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
document.getElementById("buscador_2").addEventListener("keyup", function() {
    // Obtener el valor del input y pasarlo a minúsculas
    let filtro = this.value.toLowerCase();
    
    // Obtener las filas de la tabla
    let filas = document.querySelectorAll("#tabla_rechazo tbody tr");

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
import '../components/jquery/jquery.min.js';
import '../components/jquery/jquery-ui-min.js';

import '../components/bootstrap/js/bootstrap.bundle.min.js';
import '../components/bootstrap/js/bootstrap.bundle.min.css';

import '../components/summernote/summernote-bs4.min.js';
import '../components/summernote/summernote-bs4.min.css';

$(document).ready(function() {
    // Inicializar summernote si no se ha hecho previamente
    $('.summernote-editor').summernote({
        toolbar: false,  // Ocultar la barra de herramientas
        airMode: true,   // Para que no aparezca el editor flotante
        disableResizeEditor: true,  // Evitar cambiar el tamaño del editor
    });

    // Deshabilitar la edición en summernote
    $('.summernote-editor').summernote('disable');
});

$(document).ready(function() {
    $('input[type="checkbox"]').on('change', function() {
        var group = 'input[name="' + $(this).attr('name') + '"]';
        $(group).not(this).prop('checked', false);
    });
});

$(document).ready(function() {
    var stepCount = 1;  // Paso inicial
  
    // Inicializar Summernote para el primer paso (ya existente en el HTML)
    $(`#stepTest_step_1`).summernote({
        airMode: false, 
        disable: true, // Desactiva por completo la edición
        height: 100,
        toolbar: [
            ['style', ['bold', 'italic', 'underline']],
            ['para', ['ul', 'ol']],
            ['insert', ['link','codeBlock']],
            ['view', ['fullscreen','codeview']],
            ['codeblock', ['codeBlock']]
        ],
        placeholder: 'Pasos de la prueba'
    });
  
    $(`#expectedResult_step_1`).summernote({
        height: 100,
        toolbar: [
            ['style', ['bold', 'italic', 'underline']],
            ['para', ['ul', 'ol']],
            ['insert', ['link']],
            ['view', ['fullscreen']]
        ],
        placeholder: 'Resultado esperado'
    });
  
    // Función para agregar nuevos pasos
    $('#addStepButton').click(function() {
        stepCount++;        var newStepId = `step_${stepCount}`;
        var newStep = `
        <div class="form-group step" id="step_${newStepId}">
          <label>Paso ${stepCount}:</label>
          <div class="row">
            <div class="col-md-6">
              <div id="stepTest_${newStepId}" class="summernote-editor"></div>
            </div>
            <div class="col-md-6">
              <div id="expectedResult_${newStepId}" class="summernote-editor"></div>
            </div>
          </div>
        </div>`;
      
        $('#stepsContainer').append(newStep);  // Añadir el nuevo paso al contenedor
        updateStepNumbers();  // Actualizar los números de los pasos
        
        // Inicializar Summernote en los nuevos divs
        $(`#stepTest_${newStepId}`).summernote({
            height: 100,
            toolbar: [
                ['style', ['bold', 'italic', 'underline']],
                ['para', ['ul', 'ol']],
                ['insert', ['link']],
                ['view', ['fullscreen']]
            ],
            placeholder: 'Pasos de la prueba'
        });
  
        $(`#expectedResult_${newStepId}`).summernote({
            height: 100,
            toolbar: [
                ['style', ['bold', 'italic', 'underline']],
                ['para', ['ul', 'ol']],
                ['insert', ['link']],
                ['view', ['fullscreen']]
            ],
            placeholder: 'Resultado esperado'
        });
    });
  
    // Función para actualizar los números de paso
    function updateStepNumbers() {
        $('#stepsContainer .step').each(function(index) {
            $(this).find('label').text(`Paso ${index + 1}:`);
        });
    }
  

  

  });
  
  
  

  



$('#stepTest_step_1').summernote({
    airMode: false, 
    toolbar: false,  // Opcional: quitar la barra de herramientas
    editable: false  // No se puede escribir, pero se puede seleccionar
  });


  document.getElementById("buscador_2").addEventListener("keyup", filtrarTabla);
  document.getElementById("filtro_categoria").addEventListener("change", filtrarTabla);
  
  function filtrarTabla() {
      // Obtener el valor del buscador y pasarlo a minúsculas
      let filtroTexto = document.getElementById("buscador_2").value.toLowerCase();
      
      // Obtener el valor del filtro de categoría
      let filtroCategoria = document.getElementById("filtro_categoria").value.toLowerCase();
      
      // Obtener las filas de la tabla
      let filas = document.querySelectorAll("#tabla_rechazo tbody tr");
  
      // Recorrer las filas de la tabla y esconder aquellas que no coincidan con el filtro
      filas.forEach(fila => {
          let textoFila = fila.textContent.toLowerCase();
          let categoriaFila = fila.querySelector("td").textContent.toLowerCase();
          
          // Mostrar la fila solo si coincide con el texto del buscador y la categoría seleccionada
          if ((textoFila.includes(filtroTexto) || filtroTexto === "") && 
              (categoriaFila.includes(filtroCategoria) || filtroCategoria === "")) {
              fila.style.display = ""; // Mostrar la fila
          } else {
              fila.style.display = "none"; // Ocultar la fila
          }
      });
  }