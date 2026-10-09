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

document.addEventListener('DOMContentLoaded', function () {
  // Selección de elementos del DOM
  const ppcalButtons = document.querySelectorAll('.ppcal-btn');
  const crmolButtons = document.querySelectorAll('.crmol-btn');
  const dynamicTableContainer = document.getElementById('dynamicTableContainer');
  const dynamicTableTitle = document.getElementById('dynamicTableTitle');
  const dynamicTable = document.getElementById('dynamicTable').getElementsByTagName('tbody')[0];
  const tableSearch = document.getElementById('tableSearch');
  const tableRows = document.querySelectorAll('.table tbody tr');
  const addCoordinatorBtn = document.getElementById('addCoordinatorBtn');
  const addClientUnitBtn = document.getElementById('addClientUnitBtn');
  const addDeveloperBtn = document.getElementById('addDeveloperBtn');

  const addCoordinatorModal = new bootstrap.Modal(document.getElementById('addCoordinatorModal'));
  const addClientUnitModal = new bootstrap.Modal(document.getElementById('addClientUnitModal'));
  const searchDeveloperModal = new bootstrap.Modal(document.getElementById('searchDeveloperModal'));

  const developerList = document.getElementById('developerList');
  const developerSearch = document.getElementById('developerSearch');
  const developerTable = document.getElementById('developerTable');

  const projectFormCollapse = new bootstrap.Collapse(document.getElementById('createProjectCollapse'), {
    toggle: false
  });
  const projectForm = document.getElementById('projectForm');
  const saveProjectBtn = document.getElementById('saveProject');
  const cancelEditBtn = document.getElementById('cancelEdit');
  const createProjectBtn = document.querySelector('.create-project-btn');

  // Función para manejar la edición de un proyecto
  function handleEditProject(projectId) {
    const projectData = {
      id: projectId,
      name: `Proyecto ${projectId}`,
      description: `Descripción del Proyecto ${projectId}`,
      startDate: '2023-01-01',
      endDate: '2023-12-31',
      deliveryDate: '2023-12-15',
      coordinator: 'Coordinador 1',
      coordination: 'Coordinación A',
      clientUnitCode: 'CU001',
      requester: 'Solicitante 1'
    };

    document.getElementById('projectId').value = projectData.id;
    document.getElementById('projectName').value = projectData.name;
    document.getElementById('projectDescription').value = projectData.description;
    document.getElementById('projectStartDate').value = projectData.startDate;
    document.getElementById('projectEndDate').value = projectData.endDate;
    document.getElementById('projectDeliveryDate').value = projectData.deliveryDate;
    document.getElementById('projectModule').value = projectData.coordinator;
    document.getElementById('coordinatorSelect').value = projectData.coordination;
    document.getElementById('projectClientUnitCode').value = projectData.clientUnitCode;
    document.getElementById('projectRequester').value = projectData.requester;

    createProjectBtn.textContent = 'Editar Proyecto';
    saveProjectBtn.textContent = 'Guardar Cambios';
    projectFormCollapse.show();
  }

  // Event listeners para los botones de editar
  document.querySelectorAll('.edit-project-btn').forEach(button => {
    button.addEventListener('click', function () {
      const projectId = this.getAttribute('data-project-id');
      handleEditProject(projectId);
    });
  });

  // Event listener para el botón de guardar
  saveProjectBtn.addEventListener('click', function () {
    if (!validateForm()) {
      showNotification('Por favor, completa todos los campos requeridos y selecciona al menos un desarrollador.', 'warning');
      return;
    }

    const projectId = document.getElementById('projectId').value;
    const projectName = document.getElementById('projectName').value;
    const projectDescription = document.getElementById('projectDescription').value;

    console.log('Guardando proyecto:', { id: projectId, name: projectName, description: projectDescription });

    showNotification('Proyecto guardado con éxito', 'success');
    projectFormCollapse.hide();
    resetForm();
    loadProjectsTable();
  });

  // Event listener para el botón de cancelar
  cancelEditBtn.addEventListener('click', function () {
    projectFormCollapse.hide();
    resetForm();
  });

  // Event listener para el botón de crear proyecto
  createProjectBtn.addEventListener('click', function () {
    resetForm();
  });

  function resetForm() {
    projectForm.reset();
    document.getElementById('projectId').value = '';
    createProjectBtn.textContent = 'Crear Proyecto';
    saveProjectBtn.textContent = 'Guardar';
    developerList.innerHTML = '';
  }

  function addDeveloperToList(developer) {
    const developerItem = document.createElement('div');
    developerItem.className = 'col-auto mb-2';
    developerItem.innerHTML = `
      <div class="developer-item" 
           data-rut="${developer.rut}" 
           data-email="${developer.email}" 
           data-rol="${developer.rol}">
        <span>${developer.nombre}</span>
        <button type="button" class="btn btn-danger btn-sm remove-developer">X</button>
      </div>
    `;
    developerList.appendChild(developerItem);

    developerItem.querySelector('.remove-developer').addEventListener('click', function () {
      developerList.removeChild(developerItem);
    });
  }

  // Evento para mostrar el modal de búsqueda de desarrolladores
  addDeveloperBtn.addEventListener('click', function () {
    searchDeveloperModal.show();
  });

  // Función para manejar la apertura de modales
  function openModal(modal) {
    modal.show();
  }

  // Evento para mostrar el modal de agregar coordinador
  addCoordinatorBtn.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    openModal(addCoordinatorModal);
  });

  // Evento para mostrar el modal de unidad cliente
  addClientUnitBtn.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    openModal(addClientUnitModal);
  });

  // Función para filtrar la tabla de desarrolladores
  developerSearch.addEventListener('input', function () {
    const searchTerm = this.value.toLowerCase();
    const rows = developerTable.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
      const name = rows[i].cells[0].textContent.toLowerCase();
      if (name.includes(searchTerm)) {
        rows[i].style.display = '';
      } else {
        rows[i].style.display = 'none';
      }
    }
  });

  // Evento para seleccionar un desarrollador de la tabla
  developerTable.addEventListener('click', function(e) {
    if (e.target.classList.contains('select-developer')) {
      const button = e.target;
      const developer = {
        rut: button.getAttribute('data-rut'),
        nombre: button.getAttribute('data-nombre'),
        email: button.getAttribute('data-email'),
        rol: button.getAttribute('data-rol')
      };
      addDeveloperToList(developer);
      searchDeveloperModal.hide();
    }
  });

  // Evento para filtrar las filas de la tabla principal
  tableSearch.addEventListener('input', function () {
    const searchTerm = this.value.toLowerCase();
    tableRows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
  });

  function getStatusIcon(status) {
    const icons = {
      'completado': '<i class="fas fa-check-circle status-icon status-completed"></i>',
      'en progreso': '<i class="fas fa-spinner status-icon status-in-progress"></i>',
      'rechazado': '<i class="fas fa-times-circle status-icon status-rejected"></i>',
      'pendiente': '<i class="fas fa-clock status-icon status-pending"></i>'
    };
    return icons[status.toLowerCase()] || '';
  }

  function showDynamicTable(title, data) {
    dynamicTableTitle.textContent = title;
    dynamicTable.innerHTML = '';
    data.forEach(item => {
      const row = dynamicTable.insertRow();
      row.insertCell(0).textContent = item.name;
      row.insertCell(1).innerHTML = getStatusIcon(item.status) + item.status;
      row.insertCell(2).textContent = item.developer;
      row.insertCell(3).textContent = item.testerQA;
      row.insertCell(4).textContent = item.coordinator;
    });
    dynamicTableContainer.classList.remove('hidden');
  }

  function handlePPCALClick() {
    const projectId = this.getAttribute('data-project-id');
    const ppcalData = [
      { name: `Archivo PPCAL 1 - Proyecto ${projectId}`, status: 'En progreso', developer: 'Juan Pérez', testerQA: 'Ana García', coordinator: 'Carlos López' },
      { name: `Archivo PPCAL 2 - Proyecto ${projectId}`, status: 'Completado', developer: 'María Rodríguez', testerQA: 'Pedro Sánchez', coordinator: 'Laura Martínez' },
      { name: `Archivo PPCAL 3 - Proyecto ${projectId}`, status: 'Pendiente', developer: 'Diego Fernández', testerQA: 'Sofía Ruiz', coordinator: 'Javier Torres' }
    ];
    showDynamicTable(`PPCAL - Proyecto ${projectId}`, ppcalData);
  }

  function handleCRMOLClick() {
    const projectId = this.getAttribute('data-project-id');
    const crmolData = [
      { name: `Archivo CRMOL 1 - Proyecto ${projectId}`, status: 'Completado', developer: 'Elena Gómez', testerQA: 'Roberto Díaz', coordinator: 'Isabel Moreno' },
      { name: `Archivo CRMOL 2 - Proyecto ${projectId}`, status: 'En progreso', developer: 'Andrés Herrera', testerQA: 'Carmen Vega', coordinator: 'Miguel Ángel Soto' },
      { name: `Archivo CRMOL 3 - Proyecto ${projectId}`, status: 'Rechazado', developer: 'Lucía Ortega', testerQA: 'Francisco Jiménez', coordinator: 'Beatriz Navarro' }
    ];
    showDynamicTable(`CRMOL - Proyecto ${projectId}`, crmolData);
  }

  function addTableButtonListeners() {
    document.querySelectorAll('.ppcal-btn').forEach(button => {
      button.removeEventListener('click', handlePPCALClick);
      button.addEventListener('click', handlePPCALClick);
    });

    document.querySelectorAll('.crmol-btn').forEach(button => {
      button.removeEventListener('click', handleCRMOLClick);
      button.addEventListener('click', handleCRMOLClick);
    });

    document.querySelectorAll('.view-btn').forEach(button => {
      button.addEventListener('click', function() {
        const projectId = this.getAttribute('data-project-id');
        showStatistics(projectId);
      });
    });

    document.querySelectorAll('.edit-project-btn').forEach(button => {
      button.addEventListener('click', function() {
        const projectId = this.getAttribute('data-project-id');
        handleEditProject(projectId);
      });
    });

    document.querySelectorAll('.delete-project-btn').forEach(button => {
      button.addEventListener('click', function() {
        const projectId = this.getAttribute('data-project-id');
        if (confirm('¿Estás seguro de que quieres eliminar este proyecto?')) {
          console.log(`Eliminando proyecto ${projectId}`);
          this.closest('tr').remove();
          showNotification('Proyecto eliminado con éxito', 'success');
        }
      });
    });
  }

  function validateForm() {
    let isValid = true;
    const requiredFields = document.querySelectorAll('#projectForm [required]');
    
    requiredFields.forEach(field => {
      if (!field.value.trim()) {
        isValid = false;
        field.classList.add('is-invalid');
      } else {
        field.classList.remove('is-invalid');
      }
    });

    if (developerList.children.length === 0) {
      isValid = false;
      document.getElementById('addDeveloperBtn').classList.add('is-invalid');
    } else {
      document.getElementById('addDeveloperBtn').classList.remove('is-invalid');
    }

    return isValid;
  }

  function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.role = 'alert';
    notification.innerHTML = `
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.insertAdjacentElement('afterbegin', notification);
    
    setTimeout(() => {
      notification.remove();
    }, 5000);
  }

  function loadProjectsTable() {
    const projectsData = [
      { id: 1, name: 'Proyecto SGQA', creationDate: '2024-10-21', coordination: 'Calidad' },
      { id: 2, name: 'Proyecto Control de cambios', creationDate: '2024-10-21', coordination: 'Finanzas' }
    ];

    const tableBody = document.querySelector('.table tbody');
    tableBody.innerHTML = '';

    projectsData.forEach(project => {
      const row = `
        <tr>
          <td>${project.id}</td>
          <td>${project.name}</td>
          <td>${project.creationDate}</td>
          <td>${project.coordination}</td>
          <td class="text-center">
            <button class="btn btn-primary btn-sm ppcal-btn" data-project-id="${project.id}">PPCAL</button>
            <button class="btn btn-secondary btn-sm crmol-btn" data-project-id="${project.id}">CRMOL</button>
          </td>
          <td class="text-center">
            <button class="btn btn-success btn-sm view-btn" data-project-id="${project.id}">Ver Estadísticas</button>
            <button class="btn btn-warning btn-sm edit-project-btn" data-project-id="${project.id}">Editar</button>
            <button class="btn btn-danger btn-sm delete-project-btn" data-project-id="${project.id}">Eliminar</button>
          </td>
        </tr>
      `;
      tableBody.innerHTML += row;
    });

    addTableButtonListeners();
  }

  function showStatistics(projectId) {
    const projectData = getProjectData(projectId);

    // Limpiar gráficos existentes
    document.querySelectorAll('.chart-container canvas').forEach(canvas => {
      canvas.remove();
    });

    // Crear nuevos canvas para los gráficos
    document.querySelectorAll('.chart-container').forEach((container, index) => {
      const canvas = document.createElement('canvas');
      canvas.id = `chart${index + 1}`;
      container.appendChild(canvas);
    });

    // Inicializar los gráficos con los datos del proyecto
    initializeCharts(projectData);

    // Actualizar la información del proyecto en el modal
    updateProjectInfo(projectData);

    // Obtener el modal y crear nueva instancia
    const statisticsModal = document.getElementById('viewModal');
    const modal = new bootstrap.Modal(statisticsModal, {
      backdrop: 'static',
      keyboard: true
    });

    // Manejar el evento de cierre del modal
    statisticsModal.addEventListener('hidden.bs.modal', function () {
      // Remover backdrop si existe
      const backdrop = document.querySelector('.modal-backdrop');
      if (backdrop) {
        backdrop.remove();
      }
      
      // Limpiar clases y estilos del body
      document.body.classList.remove('modal-open');
      document.body.style.removeProperty('padding-right');
      
      // Destruir los charts
      Chart.helpers.each(Chart.instances, function(instance) {
        instance.destroy();
      });
    });

    // Manejar el botón de cerrar
    const closeButton = statisticsModal.querySelector('[data-bs-dismiss="modal"]');
    if (closeButton) {
      closeButton.addEventListener('click', function() {
        modal.hide();
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
          backdrop.remove();
        }
        document.body.classList.remove('modal-open');
      });
    }

    modal.show();
  }

  function getProjectData(projectId) {
    return {
      id: projectId,
      name: `Proyecto ${projectId}`,
      startDate: '2023-01-01',
      endDate: '2023-12-31',
      taskCompletion: [65, 59, 80, 81, 56, 55, 40],
      resourceAllocation: [12, 19, 3, 5, 2, 3],
      statusDistribution: [30, 50, 20],
      timelineProgress: [40, 60]
    };
  }

  function updateProjectInfo(data) {
    const infoContainer = document.getElementById('projectInfo');
    infoContainer.innerHTML = `
      <h3>${data.name}</h3>
      <p>ID: ${data.id}</p>
      <p>Fecha de inicio: ${data.startDate}</p>
      <p>Fecha de finalización: ${data.endDate}</p>
    `;
  }

  function initializeCharts(data) {
    const chartConfigs = [
      {
        id: 'chart1',
        type: 'line',
        data: {
          labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul'],
          datasets: [{
            label: 'Completitud de Tareas',
            data: data.taskCompletion,
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          plugins: {
            title: {
              display: true,
              text: 'Progreso de Tareas'
            }
          }
        }
      },
      {
        id: 'chart2',
        type: 'bar',
        data: {
          labels: ['Diseño', 'Desarrollo', 'Pruebas', 'Documentación', 'Gestión', 'Otros'],
          datasets: [{
            label: 'Asignación de Recursos',
            data: data.resourceAllocation,
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
        },
        options: {
          responsive: true,
          plugins: {
            title: {
              display: true,
              text: 'Distribución de Recursos'
            }
          }
        }
      },
      {
        id: 'chart3',
        type: 'pie',
        data: {
          labels: ['Completado', 'En Progreso', 'Pendiente'],
          datasets: [{
            label: 'Distribución de Estados',
            data: data.statusDistribution,
            backgroundColor: [
              'rgb(255, 99, 132)',
              'rgb(54, 162, 235)',
              'rgb(255, 205, 86)'
            ]
          }]
        },
        options: {
          responsive: true,
          plugins: {
            title: {
              display: true,
              text: 'Estado del Proyecto'
            }
          }
        }
      },
      {
        id: 'chart4',
        type: 'doughnut',
        data: {
          labels: ['Tiempo Transcurrido', 'Tiempo Restante'],
          datasets: [{
            label: 'Progreso del Cronograma',
            data: data.timelineProgress,
            backgroundColor: [
              'rgb(255, 99, 132)',
              'rgb(54, 162, 235)'
            ]
          }]
        },
        options: {
          responsive: true,
          plugins: {
            title: {
              display: true,
              text: 'Progreso del Cronograma'
            }
          }
        }
      }
    ];

    chartConfigs.forEach(config => {
      const ctx = document.getElementById(config.id);
      if (ctx) {
        new Chart(ctx, {
          type: config.type,
          data: config.data,
          options: config.options
        });
      }
    });
  }

  function exportStatisticsToPDF() {
    const pdf = new jsPDF();

    pdf.setFontSize(18);
    pdf.text('Estadísticas del Proyecto', 14, 22);

    const projectInfo = document.getElementById('projectInfo');
    pdf.setFontSize(12);
    pdf.text(projectInfo.innerText, 14, 35);

    let yOffset = 70;

    function addChartToPDF(chartId, title) {
      const canvas = document.getElementById(chartId);
      const imgData = canvas.toDataURL('image/png');
      pdf.addImage(imgData, 'PNG', 10, yOffset, 190, 100);
      pdf.setFontSize(14);
      pdf.text(title, 14, yOffset - 5);
      yOffset += 105;

      if (yOffset > 250) {
        pdf.addPage();
        yOffset = 20;
      }
    }

    addChartToPDF('chart1', 'Progreso de Tareas');
    addChartToPDF('chart2', 'Distribución de Recursos');
    addChartToPDF('chart3', 'Estado del Proyecto');
    addChartToPDF('chart4', 'Progreso del Cronograma');

    pdf.save('estadisticas_proyecto.pdf');

    // Cerrar el modal después de exportar
    const statisticsModal = bootstrap.Modal.getInstance(document.getElementById('viewModal'));
    if (statisticsModal) {
      statisticsModal.hide();
    }
  }

  document.getElementById('export-charts-pdf').addEventListener('click', exportStatisticsToPDF);

  // Inicialización
  loadProjectsTable();
  addTableButtonListeners();
});