    // 1. Enviar el TXT a PHP y mostrar la tabla
    document.getElementById('formCargaTxt').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        // Ruta hacia tu API
        fetch('../api/procesar_txt.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(respuesta => {
            if(respuesta.status === 'success') {
                const tbody = document.getElementById('cuerpoTabla');
                tbody.innerHTML = ''; 
                
                if(respuesta.data.length === 0){
                    alert("No se encontró ninguna de esas matrículas en la base de datos.");
                    return;
                }

                respuesta.data.forEach(alumno => {
                    const yaAcreditado = (alumno.acreditado == 1);
                    const badge = yaAcreditado ? '<span style="color: #00a86b; font-weight: bold;">Activo</span>' : '<span style="color: #ef4444; font-weight: bold;">Bloqueado</span>';
                    const checkbox = yaAcreditado ? `<input type="checkbox" disabled checked title="Ya está habilitado">` : `<input type="checkbox" class="chk-alumno" value="${alumno.matricula}">`;

                    tbody.innerHTML += `
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 10px;">${checkbox}</td>
                            <td style="padding: 10px;"><b>${alumno.matricula}</b></td>
                            <td style="padding: 10px;">${alumno.nombre_completo}</td>
                            <td style="padding: 10px;">${badge}</td>
                        </tr>
                    `;
                });

                document.getElementById('panelResultados').style.display = 'block';
                alert(`Lectura Exitosa. Se encontraron ${respuesta.data.length} alumnos válidos.`);
            }
        })
        .catch(error => console.error('Error:', error));
    });

    // Función para el Checkbox Maestro
    function seleccionarTodos(source) {
        const checkboxes = document.querySelectorAll('.chk-alumno');
        checkboxes.forEach(chk => chk.checked = source.checked);
    }

    // 2. Mandar la orden de habilitar a la Base de Datos
    function habilitarSeleccionados() {
        const seleccionados = [];
        document.querySelectorAll('.chk-alumno:checked').forEach(chk => seleccionados.push(chk.value));

        if (seleccionados.length === 0) {
            alert("Debe seleccionar al menos un alumno para habilitar.");
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
            if(respuesta.status === 'success') {
                alert("¡Proceso Completado! " + respuesta.message);
                // Escondemos la tabla y limpiamos el input
                document.getElementById('panelResultados').style.display = 'none';
                document.getElementById('formCargaTxt').reset();
                document.getElementById('fileNameDisplay').innerText = "Haz clic o arrastre tu archivo .txt aquí";
            }
        });
    }
