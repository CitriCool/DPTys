$(function() {
    let selected = true; // Se usa let para ámbito de bloque
    
    $('#BtnSeleccionar').on('click', function() { // .on() es la recomendación en jQuery moderno
        if (selected) {
            $('#meses input[type=checkbox]').prop("checked", true);
        } else {
            $('#meses input[type=checkbox]').prop("checked", false);
        }
        const checkboxes = document.querySelectorAll('.countCheckbox:checked');
        const total = checkboxes.length;
      
        document.getElementById('totalCount').textContent = total;
        selected = !selected; // Alterna el valor de selected
    });

});

// Función para contar checkboxes seleccionados
function contar() {
    const checkboxes = document.querySelectorAll('.countCheckbox:checked');
    const total = checkboxes.length;
  
    document.getElementById('totalCount').textContent = total;
  }
  
  // Agregar el evento 'change' a cada checkbox
  document.querySelectorAll('.countCheckbox').forEach(checkbox => {
    checkbox.addEventListener('change', contar);
  });

  document.addEventListener("DOMContentLoaded", function() {
    // Función para filtrar columnas
    function filterTable(colIndex, selectId) {
        var select, filter, table, tr, td, i, txtValue;
        select = document.getElementById(selectId);
        filter = select.value.toUpperCase();
        table = document.getElementById("tabla");
        tr = table.getElementsByTagName("tr");

        for (i = 1; i < tr.length; i++) { // Empieza desde el 1 para evitar los encabezados
            td = tr[i].getElementsByTagName("td")[colIndex];
            if (td) {
                txtValue = td.textContent || td.innerText;
                if (filter === "" || txtValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }       
        }
    }

    // Añadir eventos para cada select
    document.getElementById("filterEstado").addEventListener("change", function() {
        filterTable(3, "filterEstado");
    });
    document.getElementById("filterPropietario").addEventListener("change", function() {
        filterTable(4, "filterPropietario");
    });
    document.getElementById("filterPrioridad").addEventListener("change", function() {
        filterTable(5, "filterPrioridad");
    });

    

   
});

