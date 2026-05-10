(function () {
    const formCargaTxt = document.getElementById('formCargaTxt');
    const fileInput = document.getElementById('archivo_txt');
    const fileNameDisplay = document.getElementById('fileNameDisplay');
    const panelResultados = document.getElementById('panelResultados');
    const cuerpoTabla = document.getElementById('cuerpoTabla');
    const checkAll = document.getElementById('chkTodos');
    const btnHabilitar = document.getElementById('btnHabilitarSeleccionados');

    if (!formCargaTxt) return;

    fileInput?.addEventListener('change', () => {
        const fileName = fileInput.files && fileInput.files[0] ? fileInput.files[0].name : 'Haz clic o arrastra tu archivo .txt aquí';
        fileNameDisplay.textContent = fileName;
    });

    formCargaTxt.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch('../api/procesar_txt.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(respuesta => {
                if (respuesta.status !== 'success') return;

                cuerpoTabla.innerHTML = '';

                if (respuesta.data.length === 0) {
                    alert('No se encontró ninguna de esas matrículas en la base de datos.');
                    return;
                }

                respuesta.data.forEach(alumno => {
                    const yaAcreditado = (alumno.acreditado == 1);
                    const badgeClass = yaAcreditado ? 'txt-status-active' : 'txt-status-blocked';
                    const badgeText = yaAcreditado ? 'Activo' : 'Bloqueado';
                    const checkbox = yaAcreditado
                        ? '<input type="checkbox" disabled checked title="Ya está habilitado">'
                        : `<input type="checkbox" class="chk-alumno" value="${alumno.matricula}">`;

                    cuerpoTabla.innerHTML += `
                        <tr class="txt-results-row">
                            <td class="txt-cell">${checkbox}</td>
                            <td class="txt-cell"><b>${alumno.matricula}</b></td>
                            <td class="txt-cell">${alumno.nombre_completo}</td>
                            <td class="txt-cell"><span class="${badgeClass}">${badgeText}</span></td>
                        </tr>
                    `;
                });

                panelResultados.style.display = 'block';
                alert(`Lectura Exitosa. Se encontraron ${respuesta.data.length} alumnos válidos.`);
            })
            .catch(error => console.error('Error:', error));
    });

    checkAll?.addEventListener('change', (e) => {
        const checkboxes = document.querySelectorAll('.chk-alumno');
        checkboxes.forEach(chk => chk.checked = e.target.checked);
    });

    btnHabilitar?.addEventListener('click', () => {
        const seleccionados = [];
        document.querySelectorAll('.chk-alumno:checked').forEach(chk => seleccionados.push(chk.value));

        if (seleccionados.length === 0) {
            alert('Debe seleccionar al menos un alumno para habilitar.');
            return;
        }

        const formData = new FormData();
        formData.append('matriculas_habilitar', JSON.stringify(seleccionados));

        fetch('../api/procesar_txt.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(respuesta => {
                if (respuesta.status === 'success') {
                    alert('¡Proceso Completado! ' + respuesta.message);
                    panelResultados.style.display = 'none';
                    formCargaTxt.reset();
                    fileNameDisplay.textContent = 'Haz clic o arrastra tu archivo .txt aquí';
                }
            });
    });
})();
