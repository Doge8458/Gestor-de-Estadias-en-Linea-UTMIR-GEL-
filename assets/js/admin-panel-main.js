        const toggleSwitches = document.querySelectorAll('.theme-checkbox');
        const currentTheme = localStorage.getItem('theme');
        if (currentTheme) {
            document.documentElement.setAttribute('data-theme', currentTheme);
            if (currentTheme === 'light') toggleSwitches.forEach(sw => sw.checked = true);
        }
        function switchTheme(e) {
            const isChecked = e.target.checked;
            toggleSwitches.forEach(sw => sw.checked = isChecked);
            if (isChecked) { document.documentElement.setAttribute('data-theme', 'light'); localStorage.setItem('theme', 'light'); } 
            else { document.documentElement.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark'); }    
        }
        toggleSwitches.forEach(sw => sw.addEventListener('change', switchTheme, false));

        function toggleDetails(detailId, rowElement) {
            var detailsPanel = document.getElementById(detailId);
            if (detailsPanel.style.display === "table-row") {
                detailsPanel.style.display = "none"; rowElement.classList.remove("active");
            } else {
                document.querySelectorAll('.detail-row').forEach(el => el.style.display = 'none');
                document.querySelectorAll('.main-row').forEach(el => el.classList.remove("active"));
                detailsPanel.style.display = "table-row"; rowElement.classList.add("active");
            }
        }

        let idEliminarGlobal = null;
        function abrirModalDelete(id, matricula) { idEliminarGlobal = id; document.getElementById('modalMatriculaTexto').innerText = matricula; document.getElementById('modalConfirmacion').classList.add('active'); }
        function abrirModalCalendario() { document.getElementById('modalCalendario').classList.add('active'); }
        function cerrarModal(idModal) { document.getElementById(idModal).classList.remove('active'); if (idModal === 'modalConfirmacion') idEliminarGlobal = null; }
        function ejecutarEliminacion() { if(idEliminarGlobal !== null) window.location.href = "../api/delete_entrega.php?id=" + idEliminarGlobal; }


        document.getElementById('btnAbrirModalCalendario')?.addEventListener('click', abrirModalCalendario);

        window.onload = function() {
            document.getElementById('currentYear').textContent = new Date().getFullYear();
            
            flatpickr("#rango_fechas", {
                mode: "range",
                minDate: "today",
                showMonths: 2,
                locale: "es",
                dateFormat: "Y-m-d",
                conjunction: " a "
            });

            // Lógica del Contador y Calendario Visual Administrativo
            const data = window.adminPanelData || {};
            const fechaFinStr = data.fechaFin || "";
            const fechaInicioStr = data.fechaInicio || "";
            
            if(fechaFinStr !== "" && fechaInicioStr !== "") {
                // Instanciar Calendario de Previsualización (Navegable pero no seleccionable)
                flatpickr("#calendario_admin_preview", {
                    mode: "range",
                    inline: true,
                    showMonths: 1,
                    locale: "es",
                    defaultDate: [fechaInicioStr, fechaFinStr]
                });

                const countDownDate = new Date(fechaFinStr.replace(/-/g, "/")).getTime();
                const startDate = new Date(fechaInicioStr.replace(/-/g, "/")).getTime();
                
                const x = setInterval(function() {
                    const now = new Date().getTime();
                    
                    if (now < startDate) {
                        document.getElementById("cd-dias").innerText = "--";
                        document.getElementById("cd-horas").innerText = "--";
                        document.getElementById("cd-mins").innerText = "--";
                        document.getElementById("cd-segs").innerText = "--";
                    } else {
                        const distance = countDownDate - now;

                        if (distance < 0) {
                            clearInterval(x);
                            document.getElementById("cd-dias").innerText = "00";
                            document.getElementById("cd-horas").innerText = "00";
                            document.getElementById("cd-mins").innerText = "00";
                            document.getElementById("cd-segs").innerText = "00";
                        } else {
                            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                            document.getElementById("cd-dias").innerText = days.toString().padStart(2, '0');
                            document.getElementById("cd-horas").innerText = hours.toString().padStart(2, '0');
                            document.getElementById("cd-mins").innerText = minutes.toString().padStart(2, '0');
                            document.getElementById("cd-segs").innerText = seconds.toString().padStart(2, '0');
                        }
                    }
                }, 1000);
            }
        };
    
