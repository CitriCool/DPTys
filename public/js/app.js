document.addEventListener('DOMContentLoaded', function () {
    // Cambiar el rol en uso recarga la página con el menú y permisos de ese rol
    document.getElementById('rolActivo')?.addEventListener('change', function () {
        this.form.submit();
    });

    // Confirmación antes de acciones importantes: <form data-confirmar="mensaje">
    document.querySelectorAll('form[data-confirmar]').forEach(form => {
        form.addEventListener('submit', function (e) {
            if (form.dataset.confirmado) return;
            e.preventDefault();
            Swal.fire({
                title: '¿Confirmar?',
                text: form.dataset.confirmar,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar'
            }).then(({ isConfirmed }) => {
                if (isConfirmed) {
                    form.dataset.confirmado = '1';
                    form.requestSubmit();
                }
            });
        });
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
});
