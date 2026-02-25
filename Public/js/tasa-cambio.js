/**
 * Funciones específicas para el módulo de Tasa de Cambio
 */

class TasaCambio {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.actualizarWidgetTasa();
        this.inicializarGrafico();
    }

    bindEvents() {
        // Auto-cálculo en tiempo real
        $('#tasa_cambio').on('input', function () {
            const tasa = $(this).val();
            if (tasa && !isNaN(tasa)) {
                $('#previewBs').text('Bs ' + parseFloat(tasa).toFixed(4));
            }
        });

        // Botón para obtener tasa del BCV (ejemplo)
        $('#btnObtenerBCV').on('click', function () {
            TasaCambio.obtenerTasaBCV();
        });
    }

    static obtenerTasaBCV() {
        // Ejemplo de integración con API externa
        showLoading();

        // Esta es una implementación de ejemplo
        // En producción, deberías usar una API real del BCV
        $.ajax({
            url: 'https://api.exchangerate-api.com/v4/latest/USD',
            method: 'GET',
            success: function (response) {
                hideLoading();
                if (response.rates && response.rates.VES) {
                    $('#tasa_cambio').val(response.rates.VES.toFixed(4));
                    alert('Tasa obtenida del BCV: ' + response.rates.VES.toFixed(4) + ' Bs por $1 USD');
                } else {
                    alert('No se pudo obtener la tasa del BCV. Ingrese manualmente.');
                }
            },
            error: function () {
                hideLoading();
                alert('Error al conectar con el servicio de tasas. Ingrese manualmente.');
            }
        });
    }

    actualizarWidgetTasa() {
        // Actualizar widget en navbar si existe
        $.ajax({
            url: '../Controllers/TasaCambioController.php?action=getCurrentRate',
            method: 'GET',
            success: function (response) {
                if (response.success && response.data) {
                    const tasa = response.data.tasa_cambio;
                    const fecha = new Date(response.data.fecha_actualizacion);

                    // Actualizar widget
                    $('.tasa-widget').each(function () {
                        $(this).html(`
                            <span class="badge bg-success">
                                <i class="fas fa-dollar-sign"></i>
                                $1 = Bs ${parseFloat(tasa).toFixed(2)}
                            </span>
                            <small class="text-muted d-block">
                                ${fecha.toLocaleDateString()}
                            </small>
                        `);
                    });
                }
            }
        });
    }

    inicializarGrafico() {
        // Inicializar gráfico de histórico si existe el canvas
        const ctx = document.getElementById('graficoTasas');
        if (!ctx) return;

        $.ajax({
            url: '../Controllers/TasaCambioController.php?action=getChartData',
            method: 'GET',
            success: function (response) {
                if (response.success && response.data) {
                    const data = response.data;
                    const fechas = data.map(item => item.fecha);
                    const tasas = data.map(item => item.tasa_cambio);

                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: fechas,
                            datasets: [{
                                label: 'Tasa de Cambio (Bs/$)',
                                data: tasas,
                                borderColor: 'rgb(75, 192, 192)',
                                tension: 0.1,
                                fill: false
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: true,
                                    text: 'Evolución de la Tasa de Cambio'
                                }
                            }
                        }
                    });
                }
            }
        });
    }

    // Método estático para actualizar precios cuando cambia la tasa
    static onTasaChange(nuevaTasa) {
        // Emitir evento personalizado
        $(document).trigger('tasaCambioActualizada', [nuevaTasa]);

        // Actualizar todos los campos de precio en tiempo real
        $('[data-moneda="USD"][data-convertir="true"]').each(function () {
            const precioUsd = parseFloat($(this).data('valor') || $(this).text().replace(/[^0-9.-]+/g, ""));
            const precioBs = precioUsd * nuevaTasa;
            const target = $(this).data('target');
            if (target) {
                $(target).text('Bs ' + precioBs.toFixed(2));
            }
        });
    }
}

// Inicializar cuando el DOM esté listo
$(document).ready(function () {
    window.tasaCambioApp = new TasaCambio();

    // Escuchar evento de cambio de tasa
    $(document).on('tasaCambioActualizada', function (event, nuevaTasa) {
        console.log('Tasa actualizada:', nuevaTasa);
        // Aquí puedes agregar más lógica que dependa del cambio de tasa
    });
});