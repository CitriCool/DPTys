document.addEventListener('DOMContentLoaded', function () {
    // Cambiar el rol en uso recarga la página con el menú y permisos de ese rol
    document.getElementById('rolActivo')?.addEventListener('change', function () {
        this.form.submit();
    });

    // Confirmación antes de acciones importantes.
    // <form data-confirmar="mensaje"> confirma cualquier envío;
    // <button data-confirmar="mensaje"> confirma solo cuando se usa ese botón.
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function (e) {
            const boton = e.submitter;
            const mensaje = boton?.dataset.confirmar || form.dataset.confirmar;
            if (!mensaje || form.dataset.confirmado) return;
            if (!form.checkValidity()) return; // que el navegador muestre primero lo que falta
            e.preventDefault();
            Swal.fire({
                title: boton?.dataset.confirmarTitulo || form.dataset.confirmarTitulo || '¿Confirmar?',
                text: mensaje,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                focusCancel: true
            }).then(({ isConfirmed }) => {
                if (isConfirmed) {
                    form.dataset.confirmado = '1';
                    form.requestSubmit(boton || undefined);
                }
            });
        });
    });

    // Un campo obligatorio solo para cierto botón: <button data-requiere="#idCampo">
    document.querySelectorAll('form button').forEach(boton => boton.addEventListener('click', function () {
        boton.form?.querySelectorAll('button[data-requiere]').forEach(b => {
            const campo = document.querySelector(b.dataset.requiere);
            if (campo) campo.required = (b === boton);
        });
    }));

    // Volver al lugar donde se actuó después de guardar: <form data-volver="id-del-elemento">
    document.querySelectorAll('form[data-volver]').forEach(form => {
        form.addEventListener('submit', function () {
            const pestana = form.closest('.tab-pane')?.id || '';
            try { sessionStorage.setItem('volverA', JSON.stringify({ url: location.pathname, ancla: form.dataset.volver, pestana })); } catch (e) {}
        });
    });

    // Evitar envíos dobles: deshabilita los botones una vez enviado el formulario
    // (los datos ya se capturaron al momento del envío, así que no se pierde el valor del botón)
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function (e) {
            setTimeout(() => {
                if (e.defaultPrevented) return;
                form.querySelectorAll('button:not([type=button])').forEach(b => b.disabled = true);
            }, 0);
        });
    });
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('form button:disabled').forEach(b => { if (!b.dataset.deshabilitado) b.disabled = false; });
        document.querySelectorAll('form[data-confirmado]').forEach(f => delete f.dataset.confirmado);
    });

    // Filtro rápido de tablas: <input data-filtrar="#idTabla">
    document.querySelectorAll('[data-filtrar]').forEach(input => {
        const tabla = document.querySelector(input.dataset.filtrar);
        input.addEventListener('input', function () {
            const termino = this.value.toLowerCase();
            tabla?.querySelectorAll('tbody tr').forEach(fila => {
                fila.style.display = fila.textContent.toLowerCase().includes(termino) ? '' : 'none';
            });
        });
    });

    // Avisos tipo toast: dpToast('mensaje', 'exito'|'error'|'info')
    window.dpToast = function (mensaje, tipo = 'exito') {
        const contenedor = document.getElementById('toasts');
        if (!contenedor) return;
        const colores = { exito: 'var(--dp-ok)', error: 'var(--dp-peligro)', info: 'var(--dp-info)' };
        const iconos = { exito: 'fa-check-circle', error: 'fa-exclamation-circle', info: 'fa-info-circle' };
        const toast = document.createElement('div');
        toast.className = 'toast-dp';
        toast.setAttribute('role', 'status');
        toast.style.borderLeftColor = colores[tipo];
        toast.innerHTML = `<i class="fas ${iconos[tipo]} toast-icono" style="color:${colores[tipo]}"></i><span></span><button type="button" class="btn-close" aria-label="Cerrar"></button>`;
        toast.querySelector('span').textContent = mensaje;
        const cerrar = () => { toast.classList.add('saliendo'); setTimeout(() => toast.remove(), 300); };
        toast.querySelector('.btn-close').addEventListener('click', cerrar);
        contenedor.appendChild(toast);
        setTimeout(cerrar, 6000);
    };
    const inicial = document.getElementById('toastInicial');
    if (inicial) dpToast(inicial.dataset.mensaje, 'exito');

    // Modo oscuro
    document.getElementById('botonTema')?.addEventListener('click', function () {
        const oscuro = document.body.classList.toggle('modo-oscuro');
        try { localStorage.setItem('dptys-tema', oscuro ? 'oscuro' : 'claro'); } catch (e) {}
        document.dispatchEvent(new CustomEvent('dptys:tema', { detail: { oscuro } }));
    });

    // Tablas que se vuelven tarjetas en el celular: toma las etiquetas del encabezado
    document.querySelectorAll('table.tabla-tarjetas').forEach(tabla => {
        const titulos = [...tabla.querySelectorAll('thead th')].map(th => th.textContent.trim());
        tabla.querySelectorAll('tbody tr').forEach(fila => {
            [...fila.children].forEach((celda, i) => {
                if (!celda.hasAttribute('data-etiqueta') && titulos[i]) celda.setAttribute('data-etiqueta', titulos[i]);
            });
        });
    });

    // Esqueleto de carga al filtrar o paginar listados: <div class="cargando-contenedor" data-cargando-con="#idForm">
    document.querySelectorAll('[data-cargando-con]').forEach(caja => {
        const activar = () => caja.classList.add('cargando');
        document.querySelector(caja.dataset.cargandoCon)?.addEventListener('submit', activar);
        caja.querySelectorAll('.pagination a, thead a').forEach(a => a.addEventListener('click', activar));
    });
    window.addEventListener('pageshow', () => document.querySelectorAll('.cargando').forEach(c => c.classList.remove('cargando')));

    // Línea de tiempo: en pantallas angostas, mostrar el hito actual
    document.querySelectorAll('.linea-tiempo').forEach(linea => {
        const actual = linea.querySelector('.actual');
        if (actual && linea.scrollWidth > linea.clientWidth) {
            linea.scrollLeft = actual.offsetLeft - linea.clientWidth / 2 + actual.offsetWidth / 2;
        }
    });

    // Tooltips de Bootstrap
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
});
