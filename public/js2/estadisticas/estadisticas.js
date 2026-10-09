import '../components/jquery/jquery.min.js';
import '../components/jquery/jquery-ui-min.js';
import '../components/bootstrap/js/bootstrap.bundle.min.js';
import '../components/bootstrap/js/bootstrap.bundle.min.css';
import '../components/chartJS/chart.js';
import '../../css/estadisticas/estadisticas.css';

// Array para almacenar todas las instancias de gráficos
let charts = [];

document.addEventListener("DOMContentLoaded", function () {
    // Obtener los contextos de los elementos canvas para los gráficos
    const ctx1 = document.getElementById("chart1").getContext("2d");
    const ctx2 = document.getElementById("chart2").getContext("2d");
    const ctx3 = document.getElementById("chart3").getContext("2d");
    const ctx4 = document.getElementById("chart4").getContext("2d");

    // Crear el primer gráfico de barras
    const chart1 = new Chart(ctx1, {
        type: "bar",
        data: {
            labels: ["Enero", "Febrero", "Marzo", "Abril", "Mayo"],
            datasets: [{
                label: "Dataset 1",
                data: [12, 19, 3, 5, 2],
                backgroundColor: "rgba(255, 99, 132, 0.2)",
                borderColor: "rgba(255, 99, 132, 1)",
                borderWidth: 1,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                },
            },
        },
    });

    // Crear el segundo gráfico de líneas
    const chart2 = new Chart(ctx2, {
        type: "line",
        data: {
            labels: ["Enero", "Febrero", "Marzo", "Abril", "Mayo"],
            datasets: [{
                label: "Dataset 2",
                data: [3, 10, 5, 2, 20],
                backgroundColor: "rgba(54, 162, 235, 0.2)",
                borderColor: "rgba(54, 162, 235, 1)",
                borderWidth: 1,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                },
            },
        },
    });

    // Crear el tercer gráfico de pastel
    const chart3 = new Chart(ctx3, {
        type: "pie",
        data: {
            labels: ["Rojo", "Azul", "Amarillo"],
            datasets: [{
                label: "Dataset 3",
                data: [12, 19, 3],
                backgroundColor: [
                    "rgba(255, 99, 132, 0.2)",
                    "rgba(54, 162, 235, 0.2)",
                    "rgba(255, 206, 86, 0.2)"
                ],
                borderColor: [
                    "rgba(255, 99, 132, 1)",
                    "rgba(54, 162, 235, 1)",
                    "rgba(255, 206, 86, 1)"
                ],
                borderWidth: 1,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
        },
    });

    // Crear el cuarto gráfico de radar
    const chart4 = new Chart(ctx4, {
        type: "radar",
        data: {
            labels: ["Enero", "Febrero", "Marzo", "Abril", "Mayo"],
            datasets: [{
                label: "Dataset 4",
                data: [20, 10, 15, 5, 12],
                backgroundColor: "rgba(75, 192, 192, 0.2)",
                borderColor: "rgba(75, 192, 192, 1)",
                borderWidth: 1,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scale: {
                ticks: {
                    beginAtZero: true,
                },
            },
        },
    });

    // Agregar los gráficos al array de gráficos
    charts.push(chart1, chart2, chart3, chart4);

    // Obtener elementos del DOM para la selección de vista y contenedores
    const viewSelector = document.getElementById('view-selector');
    const statsContainer = document.getElementById('stats-container');
    const developersContainer = document.getElementById('developers-container');
    const dashboardContainer = document.getElementById('dashboard-container');
    const dateFilterButtons = document.querySelectorAll('.date-filter button');

    // Función para mostrar la vista seleccionada
    function showSelectedView() {
        const selectedView = viewSelector.value;
        statsContainer.classList.toggle('d-none', selectedView !== 'stats');
        developersContainer.classList.toggle('d-none', selectedView !== 'developers');
        dashboardContainer.classList.toggle('d-none', selectedView !== 'dashboard');

        if (selectedView === 'dashboard') {
            initializeDashboardCharts();
        }

        resizeCharts();
    }

    // Agregar event listener para cambiar la vista seleccionada
    viewSelector.addEventListener('change', showSelectedView);

    // Mostrar la vista de estadísticas por defecto al cargar la página
    showSelectedView();

    // Función para manejar el clic en los botones de filtro de fecha
    function handleDateFilter(event) {
        const filterDays = event.target.dataset.filter;
        
        // Remover la clase 'active' de todos los botones
        dateFilterButtons.forEach(btn => btn.classList.remove('active'));
        
        // Agregar la clase 'active' al botón clickeado
        event.target.classList.add('active');

        // Aquí iría la lógica para filtrar los datos según el período seleccionado
        console.log(`Filtrar datos para los últimos ${filterDays} días`);

        // Actualizar los gráficos con los nuevos datos filtrados
        updateChartsWithFilteredData(filterDays);
    }

    // Agregar event listener a cada botón de filtro
    dateFilterButtons.forEach(button => {
        button.addEventListener('click', handleDateFilter);
    });

    // Función para actualizar los gráficos con los datos filtrados
    function updateChartsWithFilteredData(filterDays) {
        // Aquí iría la lógica para actualizar los datos de los gráficos
        // Por ahora, solo simularemos la actualización cambiando algunos valores

        // Actualizar gráficos en la vista de estadísticas
        if (charts.length > 0) {
            charts.forEach(chart => {
                chart.data.datasets.forEach(dataset => {
                    dataset.data = dataset.data.map(() => Math.floor(Math.random() * 100));
                });
                chart.update();
            });
        }

        // Actualizar gráficos en el dashboard
        if (activeTestRunsChart) {
            activeTestRunsChart.data.datasets[0].data = [
                Math.floor(Math.random() * 50),
                Math.floor(Math.random() * 50),
                Math.floor(Math.random() * 50),
                Math.floor(Math.random() * 50),
                Math.floor(Math.random() * 50)
            ];
            activeTestRunsChart.update();
        }

        if (closedTestRunsChart) {
            closedTestRunsChart.data.datasets[0].data = closedTestRunsChart.data.datasets[0].data.map(() => Math.floor(Math.random() * 100));
            closedTestRunsChart.update();
        }

        if (resultsClosedTestRunsChart) {
            resultsClosedTestRunsChart.data.datasets.forEach(dataset => {
                dataset.data = dataset.data.map(() => Math.floor(Math.random() * 100));
            });
            resultsClosedTestRunsChart.update();
        }

        // Actualizar los números en las tarjetas de estadísticas
        document.querySelectorAll('.stat-value').forEach(statValue => {
            statValue.textContent = Math.floor(Math.random() * 100);
        });
    }

    // Variables para los gráficos del dashboard
    let activeTestRunsChart, closedTestRunsChart, resultsClosedTestRunsChart;

    // Función para inicializar los gráficos del dashboard
    function initializeDashboardCharts() {
        // Active Test Runs Chart
        if (activeTestRunsChart) activeTestRunsChart.destroy();
        activeTestRunsChart = new Chart(document.getElementById('activeTestRunsChart'), {
            type: 'doughnut',
            data: {
                labels: ['Passed', 'Failed', 'Blocked', 'Untested', 'Retest'],
                datasets: [{
                    data: [45, 20, 15, 10, 10],
                    backgroundColor: ['#28a745', '#dc3545', '#6c757d', '#ffc107', '#17a2b8']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
            }
        });

        // Closed Test Runs Chart
        if (closedTestRunsChart) closedTestRunsChart.destroy();
        closedTestRunsChart = new Chart(document.getElementById('closedTestRunsChart'), {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Closed Test Runs',
                    data: [65, 59, 80, 81, 56, 55, 40, 60, 55, 30, 78, 95],
                    fill: false,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
            }
        });

        // Results from Closed Test Runs Chart
        if (resultsClosedTestRunsChart) resultsClosedTestRunsChart.destroy();
        resultsClosedTestRunsChart = new Chart(document.getElementById('resultsClosedTestRunsChart'), {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [
                    {
                        label: 'Passed',
                        data: [65, 59, 80, 81, 56, 55],
                        backgroundColor: '#28a745'
                    },
                    {
                        label: 'Failed',
                        data: [28, 48, 40, 19, 86, 27],
                        backgroundColor: '#dc3545'
                    },
                    {
                        label: 'Blocked',
                        data: [10, 30, 15, 32, 15, 18],
                        backgroundColor: '#6c757d'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Agregar los gráficos del dashboard al array de gráficos
        charts = [activeTestRunsChart, closedTestRunsChart, resultsClosedTestRunsChart, ...charts];
    }

    // Función para redimensionar los gráficos
    function resizeCharts() {
        requestAnimationFrame(() => {
            charts.forEach(chart => {
                if (chart && typeof chart.resize === 'function') {
                    chart.resize();
                }
            });
        });
    }

    // Event listener para redimensionar los gráficos cuando cambia el tamaño de la ventana
    window.addEventListener('resize', resizeCharts);
});