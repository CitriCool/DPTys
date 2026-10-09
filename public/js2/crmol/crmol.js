import '../components/jquery/jquery.min.js';
import '../components/jquery/jquery-ui-min.js';

import '../components/bootstrap/js/bootstrap.bundle.min.js';
import '../components/bootstrap/js/bootstrap.bundle.min.css';

import'../../css/crmol/crmol.css';

$(document).ready(function(){
    var stepCount = 1;
  
    // unción para agregar nuevos pasos
    $('#addStepButton').click(function(){
      stepCount++;
      var newStep = `
        <div class="form-group step">
          <label>Paso ${stepCount}:</label>
          <div class="row">
            <div class="col-md-6">
              <textarea class="form-control" placeholder="Pasos de la prueba" rows="3"></textarea>
            </div>
            <div class="col-md-6">
              <textarea class="form-control" placeholder="Resultado esperado" rows="3"></textarea>
            </div>
          </div>
        </div>`;
      
      $('#stepsContainer').append(newStep);  // Añadir el nuevo paso al contenedor
    });
  
    // Para mostrar el nombre del archivo seleccionado
    $('#fileInput').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });
  });


  $(document).ready(function(){
    // Hacer que el modal sea redimensionable
    $('.modal-dialog').resizable({
      alsoResize: ".modal-content", // Para redimensionar el contenido del modal
      minHeight: 300,
      minWidth: 300
    });
  });