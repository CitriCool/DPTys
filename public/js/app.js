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

    // Los mensajes de éxito flotantes se ocultan solos
    document.querySelectorAll('.alerta-flotante').forEach(alerta => {
        setTimeout(() => bootstrap.Alert.getOrCreateInstance(alerta).close(), 7000);
    });

    // Tooltips de Bootstrap
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
});
