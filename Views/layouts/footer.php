                    </div> <!-- End content-wrapper -->
                    </div> <!-- End px-md-4 -->
                    </div> <!-- End content-area -->

                    <!-- Footer Container -->
                    <div class="footer-container">
                        <footer class="bg-dark text-white py-4">
                            <div class="container-fluid">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <p class="mb-0">
                                            <i class="fas fa-copyright me-1"></i>
                                            <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>
                                            <span class="text-white-50">v<?php echo SITE_VERSION; ?></span>
                                        </p>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <p class="mb-0 text-white-50">
                                            <i class="fas fa-heart text-danger me-1"></i>
                                            Desarrollado con PHP y PostgreSQL
                                            <?php if (defined('APP_ENV') && APP_ENV === 'development'): ?>
                                                <span class="badge bg-warning text-dark ms-2">@rescata</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </footer>
                    </div> <!-- End footer-container -->
                    </div> <!-- End main-content -->
                    </div> <!-- End app-container -->

                    <!-- Mobile Overlay (solo para móviles) -->
                    <div class="mobile-overlay d-md-none"></div>

                    <!-- Bootstrap JS -->
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                    <!-- DataTables JS -->
                    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
                    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
                    <!-- Custom JS -->
                    <script src="<?php echo $js_path; ?>app.js"></script>

                    <script>
                        // Inicializar componentes específicos de la página
                        document.addEventListener('DOMContentLoaded', function() {
                            // Sidebar toggle functionality
                            const sidebarToggle = document.getElementById('sidebarToggle');
                            const sidebarContainer = document.querySelector('.sidebar-container');
                            const mobileOverlay = document.querySelector('.mobile-overlay');

                            if (sidebarToggle && sidebarContainer) {
                                sidebarToggle.addEventListener('click', function() {
                                    const sidebar = document.querySelector('.sidebar');
                                    sidebar.classList.toggle('collapsed');

                                    // En móvil, mostrar/ocultar sidebar completo
                                    if (window.innerWidth <= 768) {
                                        sidebarContainer.classList.toggle('mobile-open');
                                        if (mobileOverlay) {
                                            mobileOverlay.style.display = sidebarContainer.classList.contains('mobile-open') ? 'block' : 'none';
                                        }
                                    }

                                    // Guardar estado en localStorage
                                    const isCollapsed = sidebar.classList.contains('collapsed');
                                    localStorage.setItem('sidebarCollapsed', isCollapsed);
                                });

                                // Cargar estado guardado
                                const savedState = localStorage.getItem('sidebarCollapsed');
                                if (savedState === 'true') {
                                    document.querySelector('.sidebar').classList.add('collapsed');
                                }

                                // Cerrar sidebar en móvil al hacer clic en overlay
                                if (mobileOverlay) {
                                    mobileOverlay.addEventListener('click', function() {
                                        sidebarContainer.classList.remove('mobile-open');
                                        mobileOverlay.style.display = 'none';
                                    });
                                }
                            }

                            // Inicializar tooltips
                            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                            const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                                return new bootstrap.Tooltip(tooltipTriggerEl);
                            });

                            // Auto-cerrar alerts después de 5 segundos
                            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
                            alerts.forEach(alert => {
                                setTimeout(() => {
                                    if (alert && alert.parentNode) {
                                        const bsAlert = new bootstrap.Alert(alert);
                                        bsAlert.close();
                                    }
                                }, 5000);
                            });
                        });

                        // Función global para mostrar loading
                        function showLoading() {
                            document.body.classList.add('loading');
                        }

                        function hideLoading() {
                            document.body.classList.remove('loading');
                        }

                        // Función para confirmar eliminaciones
                        function confirmDelete(message = '¿Estás seguro de que deseas eliminar este registro?') {
                            return confirm(message);
                        }
                    </script>
                    </body>

                    </html>