


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
    let filas = document.querySelectorAll("#tabla_apruebo tbody tr");

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
import '../components/jquery/jquery.min.js';
import '../components/jquery/jquery-ui-min.js';

import '../components/bootstrap/js/bootstrap.bundle.min.js';
import '../components/bootstrap/js/bootstrap.bundle.min.css';

import '../components/summernote/summernote-bs4.min.js';
import '../components/summernote/summernote-bs4.min.css';

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



        // Actualizar los números de paso

    }

    // Función para actualizar los números de paso


    // Llamar a la función al cargar la página
    initializeComponents();



    // Función para agregar nuevos pasos
    $(document).on('click', '.add-step', function(e) {
        e.preventDefault();

        

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

$(document).ready(function() {
    $('input[type="checkbox"]').on('change', function() {
        var $row = $(this).closest('tr'); // Selecciona la fila más cercana (tr)
        $row.find('input[type="checkbox"]').not(this).prop('checked', false); // Desactiva los otros checkboxes en la misma fila
    });
});



  

  

  
  
  

  




