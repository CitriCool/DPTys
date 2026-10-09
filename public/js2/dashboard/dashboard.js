// Importaciones de librerías y estilos

import '../components/bootstrap/js/bootstrap.bundle.min.js';
import '../components/bootstrap/js/bootstrap.bundle.min.css';
import '../components/chartJS/chart.js';
import '../components/jquerydatatable/jquery.dataTables.min.js'
import '../../css/dashboard/dashboard.css';
import '../components/bootstrap/js/bootstrap.bundle.min.js';
import '../components/bootstrap/js/bootstrap.bundle.min.css';
import '../components/chartJS/chart.js';
import '../components/html2canvas/html2canvas.js';
import '../components/html2canvas/html2canvas.min.js';
import '../../css/proyectos/proyectos.css';
import '../components/jspdf/jspdf.umd.min.js';
import '../components/jquery/jquery.js';
import '../components/jquery/jquery-ui-min.js';
import '../components/jquery/jquery.min.js';

$(document).ready(function() {
    // Función para inicializar DataTable con búsqueda personalizada
    function initializeDataTable(tableId) {
        var table = $(tableId).DataTable({
            "pageLength": 5, // Set default number of entries to 5
            "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]], // Update length menu options
            "language": {
                "lengthMenu": "Mostrar _MENU_ entradas",
                "zeroRecords": "No se encontraron resultados",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ entradas",
                "infoEmpty": "Mostrando 0 a 0 de 0 entradas",
                "infoFiltered": "(filtrado de _MAX_ entradas totales)",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            }
        });

        // Entrada de búsqueda personalizada
        var searchInput = $('<div class="input-group mb-3">' +
            '<div class="input-group-prepend">' +
            '<span class="input-group-text"><i class="fas fa-search"></i></span>' +
            '</div>' +
            '<input type="search" class="form-control" placeholder="Buscar">' +
            '</div>');

        // Reemplazar la entrada de búsqueda predeterminada con la personalizada
        $(tableId + '_filter').html(searchInput);

        // Vincular la función de búsqueda a la entrada personalizada
        searchInput.find('input').on('keyup', function() {
            table.search(this.value).draw();
        });
    }

    // Inicializar DataTables
    initializeDataTable('#projects-table');
    initializeDataTable('#developers-table');
    initializeDataTable('#test-professionals-table');

    // Función para inicializar gráficos con datos ficticios
    function initializeCharts(developerId) {
        var ctx1 = document.getElementById('chart1').getContext('2d');
        var chart1 = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio'],
                datasets: [{
                    label: 'PPCAL Enviados',
                    data: [12, 19, 3, 5, 2, 3, 9],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        var ctx2 = document.getElementById('chart2').getContext('2d');
        var chart2 = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio'],
                datasets: [{
                    label: 'CRMOL Enviados',
                    data: [28, 48, 40, 19, 86, 27, 90],
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    borderColor: 'rgba(153, 102, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        var ctx3 = document.getElementById('chart3').getContext('2d');
        var chart3 = new Chart(ctx3, {
            type: 'pie',
            data: {
                labels: ['Aprobados', 'Rechazados', 'Pendientes'],
                datasets: [{
                    label: 'PPCAL Ejecutados',
                    data: [300, 50, 100],
                    backgroundColor: ['rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)', 'rgba(255, 206, 86, 0.2)'],
                    borderColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)', 'rgba(255, 206, 86, 1)'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });

        var ctx4 = document.getElementById('chart4').getContext('2d');
        var chart4 = new Chart(ctx4, {
            type: 'doughnut',
            data: {
                labels: ['Aprobados', 'Rechazados', 'Pendientes'],
                datasets: [{
                    label: 'CRMOL Ejecutados',
                    data: [500, 200, 300],
                    backgroundColor: ['rgba(255, 159, 64, 0.2)', 'rgba(75, 192, 192, 0.2)', 'rgba(153, 102, 255, 0.2)'],
                    borderColor: ['rgba(255, 159, 64, 1)', 'rgba(75, 192, 192, 1)', 'rgba(153, 102, 255, 1)'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });
    }

    // Evento del botón de estadísticas
    document.querySelectorAll('.stats-btn').forEach(button => {
        button.addEventListener('click', function() {
            var developerId = this.getAttribute('data-id');
            // Inicializar gráficos cuando se muestra el modal
            $('#statsModal').on('shown.bs.modal', function() {
                initializeCharts(developerId);
            });
        });
    });

 // Función para inicializar los gráficos
 function initializeCharts() {
    const chartConfigs = [
      {
        id: 'chart1',
        type: 'line',
        data: {
          labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
          datasets: [{
            label: 'Dataset 1',
            data: [65, 59, 80, 81, 56, 55, 40],
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
          }]
        }
      },
      {
        id: 'chart2',
        type: 'bar',
        data: {
          labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
          datasets: [{
            label: 'Dataset 2',
            data: [12, 19, 3, 5, 2, 3],
            backgroundColor: [
              'rgba(255, 99, 132, 0.2)',
              'rgba(54, 162, 235, 0.2)',
              'rgba(255, 206, 86, 0.2)',
              'rgba(75, 192, 192, 0.2)',
              'rgba(153, 102, 255, 0.2)',
              'rgba(255, 159, 64, 0.2)'
            ],
            borderColor: [
              'rgba(255, 99, 132, 1)',
              'rgba(54, 162, 235, 1)',
              'rgba(255, 206, 86, 1)',
              'rgba(75, 192, 192, 1)',
              'rgba(153, 102, 255, 1)',
              'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
          }]
        }
      },
      {
        id: 'chart3',
        type: 'pie',
        data: {
          labels: ['Red', 'Blue', 'Yellow'],
          datasets: [{
            label: 'Dataset 3',
            data: [300, 50, 100],
            backgroundColor: [
              'rgb(255, 99, 132)',
              'rgb(54, 162, 235)',
              'rgb(255, 205, 86)'
            ]
          }]
        }
      },
      {
        id: 'chart4',
        type: 'doughnut',
        data: {
          labels: ['Red', 'Blue', 'Yellow', 'Green'],
          datasets: [{
            label: 'Dataset 4',
            data: [300, 50, 100, 150],
            backgroundColor: [
              'rgb(255, 99, 132)',
              'rgb(54, 162, 235)',
              'rgb(255, 205, 86)',
              'rgb(75, 192, 192)'
            ]
          }]
        }
      }
    ];

    // Inicialización de cada gráfico
    chartConfigs.forEach(config => {
      const ctx = document.getElementById(config.id).getContext('2d');
      new Chart(ctx, {
        type: config.type,
        data: config.data,
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      });
    });
  }

  // Llamada a la función para inicializar los gráficos
  initializeCharts();    
});
