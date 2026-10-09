import 'select2';
import 'select2/dist/css/select2.min.css'; 

document.addEventListener('DOMContentLoaded', function () {
    // Selecciona todos los elementos con la clase "select2" y aplica Select2
    $('.select2').select2({
        placeholder: "Seleccionar ...", // Mensaje de ayuda
        allowClear: true // Habilita el botón de limpiar selección
    });
});