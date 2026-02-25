// Variables globales
let productosSeleccionados = [];
let pagosRegistrados = [];

document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM cargado, inicializando...');
    
    // Verificar que los elementos existen antes de usarlos
    inicializarBusqueda();
    inicializarPagos();
    inicializarPaginacionPagos();
    
    // Actualizar contadores de productos (solo si existen los elementos)
    actualizarContadoresProductos();
    
    // Verificar tasa de cambio
    if (!window.tasaInfo || !window.tasaInfo.success) {
        showToast('error', 'No hay tasa de cambio configurada. Configure la tasa primero.');
    }

    if (window.productosData && window.productosData.length === 0) {
        showToast('warning', 'No hay productos disponibles. Debes crear productos primero.');
    }
});

// =========================================================================
// SISTEMA DE BÚSQUEDA DE PRODUCTOS
// =========================================================================

function inicializarBusqueda() {
    const buscarInput = document.getElementById('buscar-producto');
    const filtroCategoria = document.getElementById('filtro-categoria');
    
    if (!buscarInput || !filtroCategoria) return;

    buscarInput.addEventListener('input', function () {
        buscarProductos();
    });

    filtroCategoria.addEventListener('change', function () {
        buscarProductos();
    });

    buscarInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarProductos();
        }
    });
}

function buscarProductos() {
    const termino = document.getElementById('buscar-producto').value.trim();
    const categoria = document.getElementById('filtro-categoria').value;
    const resultadosDiv = document.getElementById('resultados-busqueda');
    const listaProductos = document.getElementById('lista-productos');
    const contadorResultados = document.getElementById('contador-resultados');

    if (!resultadosDiv || !listaProductos || !contadorResultados) return;

    if (!termino && !categoria) {
        resultadosDiv.classList.add('d-none');
        contadorResultados.textContent = '';
        return;
    }

    resultadosDiv.classList.remove('d-none');
    listaProductos.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
            Buscando productos...
        </div>
    `;

    const url = `crear.php?action=buscar_productos&q=${encodeURIComponent(termino)}&categoria=${encodeURIComponent(categoria)}`;

    fetch(url)
        .then(response => response.json())
        .then(data => {
            mostrarResultadosBusqueda(data);
        })
        .catch(error => {
            console.error('Error en búsqueda:', error);
            listaProductos.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al buscar productos
                </div>
            `;
        });
}

function mostrarResultadosBusqueda(data) {
    const listaProductos = document.getElementById('lista-productos');
    const contador = document.getElementById('contador-resultados');

    if (!listaProductos || !contador) return;

    if (!data.success || data.data.length === 0) {
        listaProductos.innerHTML = `
            <div class="alert alert-warning mb-0">
                <i class="fas fa-search me-2"></i>
                No se encontraron productos que coincidan con la búsqueda.
            </div>
        `;
        contador.textContent = '0 productos encontrados';
        return;
    }

    let html = '';
    let contadorProductos = 0;

    data.data.forEach(producto => {
        const yaSeleccionado = productosSeleccionados.some(p => p.id === producto.id);

        let stockClase = 'stock-alto';
        if (producto.stock_actual <= 10) stockClase = 'stock-medio';
        if (producto.stock_actual <= 3) stockClase = 'stock-bajo';

        let precioMostrar = '';
        if (producto.usar_precio_fijo_bs) {
            precioMostrar = `${parseFloat(producto.precio_bs).toFixed(2)} Bs (Fijo)`;
        } else {
            precioMostrar = `${parseFloat(producto.precio).toFixed(2)} USD`;
        }

        // Escapar el JSON para pasarlo correctamente
        const productoJson = JSON.stringify(producto).replace(/'/g, "\\'").replace(/"/g, '&quot;');

        html += `
            <div class="list-group-item producto-busqueda ${yaSeleccionado ? 'seleccionado' : ''}" 
                 data-producto-id="${producto.id}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <strong>${producto.nombre}</strong>
                            ${producto.usar_precio_fijo_bs ?
                '<span class="badge bg-warning text-dark ms-2">Precio Fijo</span>' :
                '<span class="badge bg-info ms-2">USD</span>'}
                            <span class="badge ${stockClase} ms-2">
                                Stock: ${producto.stock_actual}
                            </span>
                        </div>
                        <div class="producto-info small">
                            ${producto.codigo_sku ? `<span class="me-3"><i class="fas fa-barcode"></i> ${producto.codigo_sku}</span>` : ''}
                            ${producto.categoria_nombre ? `<span class="me-3"><i class="fas fa-tag"></i> ${producto.categoria_nombre}</span>` : ''}
                            <span class="${producto.usar_precio_fijo_bs ? 'text-warning' : 'text-success'}">
                                <i class="fas ${producto.usar_precio_fijo_bs ? 'fa-bolt' : 'fa-dollar-sign'}"></i> ${precioMostrar}
                            </span>
                            ${!producto.usar_precio_fijo_bs && window.tasaCambio > 0 ?
                `<span class="ms-3"><i class="fas fa-calculator"></i> ${(parseFloat(producto.precio) * window.tasaCambio).toFixed(2)} Bs</span>` : ''}
                        </div>
                    </div>
                    <div>
                        ${yaSeleccionado ?
                '<span class="badge bg-success"><i class="fas fa-check"></i> Agregado</span>' :
                `<button class="btn btn-sm btn-primary" onclick='seleccionarProducto(${productoJson})'>
                            <i class="fas fa-plus"></i> Agregar
                         </button>`}
                    </div>
                </div>
            </div>
        `;
        contadorProductos++;
    });

    listaProductos.innerHTML = html;
    contador.textContent = `${contadorProductos} producto${contadorProductos !== 1 ? 's' : ''} encontrado${contadorProductos !== 1 ? 's' : ''}`;
}

function seleccionarProducto(producto) {
    if (productosSeleccionados.some(p => p.id === producto.id)) {
        showToast('info', 'Este producto ya está en la lista');
        return;
    }

    if (producto.stock_actual <= 0) {
        showToast('error', 'Producto sin stock disponible');
        return;
    }

    let precioUSD = 0;
    if (!producto.usar_precio_fijo_bs && producto.precio) {
        precioUSD = parseFloat(producto.precio);
    }

    const precioBSExacto = parseFloat(producto.precio_bs) || 0;

    productosSeleccionados.push({
        id: producto.id,
        nombre: producto.nombre,
        precio: precioUSD,
        precio_bs: precioBSExacto,
        usar_precio_fijo_bs: producto.usar_precio_fijo_bs,
        stock_actual: producto.stock_actual,
        cantidad: 1,
        sku: producto.codigo_sku || '',
        categoria: producto.categoria_nombre || ''
    });

    actualizarListaProductos();
    actualizarContadoresProductos();
    calcularTotalVenta();
    buscarProductos();
    showToast('success', 'Producto agregado correctamente');
}

function actualizarListaProductos() {
    const container = document.getElementById('productos-container');
    const mensajeSinProductos = document.getElementById('mensaje-sin-productos');
    const btnGuardar = document.getElementById('btn-guardar-venta');

    if (!container) return;

    if (productosSeleccionados.length === 0) {
        if (mensajeSinProductos) {
            mensajeSinProductos.classList.remove('d-none');
        } else {
            container.innerHTML = `
                <div class="text-center text-muted py-4" id="mensaje-sin-productos">
                    <i class="fas fa-box-open fa-2x mb-2"></i>
                    <p>No hay productos agregados. Busca y selecciona productos para comenzar.</p>
                </div>
            `;
        }
        if (btnGuardar) btnGuardar.disabled = true;
        return;
    }

    if (mensajeSinProductos) {
        mensajeSinProductos.classList.add('d-none');
    }

    let html = '';

    productosSeleccionados.forEach((producto, index) => {
        let subtotalUSD = 0;
        let subtotalBS = 0;

        if (producto.usar_precio_fijo_bs) {
            subtotalBS = producto.cantidad * producto.precio_bs;
            subtotalUSD = window.tasaCambio > 0 ? subtotalBS / window.tasaCambio : 0;
        } else {
            subtotalUSD = producto.cantidad * producto.precio;
            subtotalBS = subtotalUSD * window.tasaCambio;
        }

        html += `
            <div class="producto-seleccionado p-3 border-bottom" data-index="${index}">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <strong>${producto.nombre}</strong>
                        <div class="small text-muted">
                            ${producto.sku ? `<div><i class="fas fa-barcode"></i> ${producto.sku}</div>` : ''}
                            <div>
                                <i class="fas fa-box"></i> Stock: ${producto.stock_actual}
                                ${producto.usar_precio_fijo_bs ?
                `<span class="badge bg-warning text-dark ms-2">Precio Fijo BS</span>` :
                `<span class="badge bg-info ms-2">USD</span>`}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center">
                            <button class="btn btn-sm btn-outline-secondary" onclick="cambiarCantidad(${index}, -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" 
                                   class="form-control form-control-sm text-center mx-2" 
                                   value="${producto.cantidad}" 
                                   min="1" 
                                   max="${producto.stock_actual}"
                                   onchange="actualizarCantidad(${index}, this.value)"
                                   style="width: 70px;">
                            <button class="btn btn-sm btn-outline-secondary" onclick="cambiarCantidad(${index}, 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-end">
                            <div><strong>Subtotal:</strong></div>
                            ${producto.usar_precio_fijo_bs ?
                `<div class="text-warning fw-bold">${subtotalBS.toFixed(2)} Bs</div>
                             <div class="small">$${subtotalUSD.toFixed(2)} USD</div>` :
                `<div class="text-success fw-bold">$${subtotalUSD.toFixed(2)} USD</div>
                             <div class="small">${subtotalBS.toFixed(2)} Bs</div>`}
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <button class="btn btn-sm btn-danger" onclick="eliminarProductoSeleccionado(${index})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <input type="hidden" name="productos[]" value="${producto.id}">
                <input type="hidden" name="cantidades[]" value="${producto.cantidad}">
                <input type="hidden" name="precios[]" value="${producto.precio}">
                <input type="hidden" name="es_precio_fijo[]" value="${producto.usar_precio_fijo_bs ? '1' : '0'}">
            </div>
        `;
    });

    container.innerHTML = html;
    if (btnGuardar) btnGuardar.disabled = false;
}

function actualizarCantidad(index, nuevaCantidad) {
    nuevaCantidad = parseInt(nuevaCantidad);

    if (isNaN(nuevaCantidad) || nuevaCantidad < 1) {
        nuevaCantidad = 1;
    }

    const stockMaximo = productosSeleccionados[index].stock_actual;
    if (nuevaCantidad > stockMaximo) {
        nuevaCantidad = stockMaximo;
        showToast('warning', 'No hay suficiente stock disponible');
    }

    productosSeleccionados[index].cantidad = nuevaCantidad;
    actualizarListaProductos();
    calcularTotalVenta();
}

function cambiarCantidad(index, cambio) {
    const nuevaCantidad = productosSeleccionados[index].cantidad + cambio;
    actualizarCantidad(index, nuevaCantidad);
}

function eliminarProductoSeleccionado(index) {
    productosSeleccionados.splice(index, 1);
    actualizarListaProductos();
    actualizarContadoresProductos();
    calcularTotalVenta();
    buscarProductos();
    showToast('info', 'Producto eliminado de la lista');
}

function actualizarContadoresProductos() {
    const contadorProductos = document.getElementById('contador-productos');
    const cantidadTotalProductos = document.getElementById('cantidad-total-productos');

    if (contadorProductos) {
        contadorProductos.textContent = productosSeleccionados.length;
    }

    if (cantidadTotalProductos) {
        const totalItems = productosSeleccionados.reduce((total, producto) => total + producto.cantidad, 0);
        cantidadTotalProductos.textContent = totalItems;
    }
}

function calcularTotalVenta() {
    let subtotalUSD = 0;
    let subtotalBS = 0;

    productosSeleccionados.forEach(producto => {
        if (producto.usar_precio_fijo_bs) {
            const subtotalItemBS = producto.cantidad * producto.precio_bs;
            const subtotalItemUSD = window.tasaCambio > 0 ? subtotalItemBS / window.tasaCambio : 0;
            subtotalBS += subtotalItemBS;
            subtotalUSD += subtotalItemUSD;
        } else {
            const subtotalItemUSD = producto.cantidad * producto.precio;
            const subtotalItemBS = subtotalItemUSD * window.tasaCambio;
            subtotalUSD += subtotalItemUSD;
            subtotalBS += subtotalItemBS;
        }
    });

    const totalUsdElement = document.getElementById('total-usd');
    const totalBsElement = document.getElementById('total-bs');
    const tasaCambioElement = document.getElementById('tasa-cambio');

    if (totalUsdElement) totalUsdElement.textContent = '$' + subtotalUSD.toFixed(2);
    if (totalBsElement) totalBsElement.textContent = 'Bs ' + subtotalBS.toFixed(2);
    if (tasaCambioElement) {
        tasaCambioElement.textContent = (window.tasaCambio || 0).toFixed(2) + ' Bs/$';
    }

    actualizarContadoresProductos();
    actualizarResumenPagos(); // Actualizar también el resumen de pagos
}

function limpiarBusqueda() {
    const buscarInput = document.getElementById('buscar-producto');
    const filtroCategoria = document.getElementById('filtro-categoria');
    const resultadosDiv = document.getElementById('resultados-busqueda');
    const contadorResultados = document.getElementById('contador-resultados');

    if (buscarInput) buscarInput.value = '';
    if (filtroCategoria) filtroCategoria.value = '';
    if (resultadosDiv) resultadosDiv.classList.add('d-none');
    if (contadorResultados) contadorResultados.textContent = '';
}

// =========================================================================
// SISTEMA DE PAGOS MÚLTIPLES MEJORADO
// =========================================================================

function inicializarPagos() {
    pagosRegistrados = [];
    
    const tipoPagoSelect = document.getElementById('nuevo_pago_tipo');
    const montoUSD = document.getElementById('nuevo_pago_monto_usd');
    const montoBS = document.getElementById('nuevo_pago_monto_bs');
    
    if (tipoPagoSelect) {
        tipoPagoSelect.addEventListener('change', function() {
            actualizarInterfazPorMoneda();
            calcularEquivalenciaPago();
        });
    }
    
    if (montoUSD) {
        montoUSD.addEventListener('input', function() {
            const tipoPagoSelect = document.getElementById('nuevo_pago_tipo');
            if (tipoPagoSelect && tipoPagoSelect.value) {
                const selectedOption = tipoPagoSelect.options[tipoPagoSelect.selectedIndex];
                const moneda = selectedOption.dataset.moneda;
                
                if (moneda === 'BS') {
                    // Si es BS, el usuario ingresó USD pero queremos que ingrese BS
                    // Por ahora, calculamos BS desde USD
                    const montoUsd = parseFloat(this.value) || 0;
                    const montoBs = montoUsd * window.tasaCambio;
                    if (montoBS) montoBS.value = montoBs.toFixed(2);
                } else {
                    // Si es USD, calcular BS
                    calcularEquivalenciaPago();
                }
            }
        });
    }
    
    if (montoBS) {
        montoBS.addEventListener('input', function() {
            const tipoPagoSelect = document.getElementById('nuevo_pago_tipo');
            if (tipoPagoSelect && tipoPagoSelect.value) {
                const selectedOption = tipoPagoSelect.options[tipoPagoSelect.selectedIndex];
                const moneda = selectedOption.dataset.moneda;
                
                if (moneda === 'BS') {
                    // Si es BS, calcular USD a partir de BS
                    const montoBs = parseFloat(this.value) || 0;
                    const montoUsd = window.tasaCambio > 0 ? montoBs / window.tasaCambio : 0;
                    if (montoUSD) montoUSD.value = montoUsd.toFixed(2);
                }
            }
        });
    }
    
    actualizarListaPagos();
    actualizarResumenPagos();
}

function inicializarPaginacionPagos() {
    // Crear contenedor de paginación si no existe
    const pagosContainer = document.getElementById('pagos-container');
    if (pagosContainer && !document.getElementById('pagos-pagination')) {
        const paginationDiv = document.createElement('div');
        paginationDiv.id = 'pagos-pagination';
        paginationDiv.className = 'mt-2 d-flex justify-content-end';
        pagosContainer.parentNode.insertBefore(paginationDiv, pagosContainer.nextSibling);
    }
}

function actualizarInterfazPorMoneda() {
    const tipoPagoSelect = document.getElementById('nuevo_pago_tipo');
    const labelMontoUSD = document.querySelector('label[for="nuevo_pago_monto_usd"]');
    const labelMontoBS = document.querySelector('label[for="nuevo_pago_monto_bs"]');
    const montoUSD = document.getElementById('nuevo_pago_monto_usd');
    const montoBS = document.getElementById('nuevo_pago_monto_bs');
    
    if (!tipoPagoSelect || !tipoPagoSelect.value) return;
    
    const selectedOption = tipoPagoSelect.options[tipoPagoSelect.selectedIndex];
    const moneda = selectedOption.dataset.moneda;
    
    if (moneda === 'BS') {
        // Para pagos en BS: el campo principal es BS
        if (labelMontoUSD) labelMontoUSD.innerHTML = 'Monto en USD (referencia)';
        if (labelMontoBS) labelMontoBS.innerHTML = 'Monto en BS *';
        if (montoUSD) montoUSD.placeholder = '0.00 USD (ref)';
        if (montoBS) {
            montoBS.readOnly = false;
            montoBS.classList.add('border-primary');
            montoBS.placeholder = 'Ingrese monto en BS';
        }
    } else {
        // Para pagos en USD o mixtos
        if (labelMontoUSD) labelMontoUSD.innerHTML = 'Monto en USD *';
        if (labelMontoBS) labelMontoBS.innerHTML = 'Equivalente en Bs';
        if (montoUSD) montoUSD.placeholder = '0.00';
        if (montoBS) {
            montoBS.readOnly = true;
            montoBS.classList.remove('border-primary');
            montoBS.placeholder = 'Se calcula automáticamente';
        }
    }
}

function calcularEquivalenciaPago() {
    const tipoPagoSelect = document.getElementById('nuevo_pago_tipo');
    const montoUSD = parseFloat(document.getElementById('nuevo_pago_monto_usd').value) || 0;
    const montoBsInput = document.getElementById('nuevo_pago_monto_bs');
    
    if (!tipoPagoSelect || !tipoPagoSelect.value || !montoBsInput) return;
    
    const selectedOption = tipoPagoSelect.options[tipoPagoSelect.selectedIndex];
    const moneda = selectedOption.dataset.moneda;
    
    if (moneda === 'BS') {
        // Para BS, el cálculo se hace desde el campo BS (que es editable)
        // Esta función se llama cuando cambia USD, pero para BS no hacemos nada
        return;
    } else {
        // Para USD, calcular BS automáticamente
        const montoBs = montoUSD * window.tasaCambio;
        montoBsInput.value = montoBs.toFixed(2);
    }
}

function agregarPago() {
    const tipoPagoSelect = document.getElementById('nuevo_pago_tipo');
    const montoUSD = parseFloat(document.getElementById('nuevo_pago_monto_usd').value);
    const montoBS = parseFloat(document.getElementById('nuevo_pago_monto_bs').value) || 0;
    
    if (!tipoPagoSelect || !tipoPagoSelect.value) {
        showToast('error', 'Debes seleccionar un tipo de pago');
        return;
    }
    
    const selectedOption = tipoPagoSelect.options[tipoPagoSelect.selectedIndex];
    const moneda = selectedOption.dataset.moneda;
    
    // Validar según la moneda
    if (moneda === 'BS') {
        if (isNaN(montoBS) || montoBS <= 0) {
            showToast('error', 'Debes ingresar un monto válido en BS');
            return;
        }
        // Si es BS, el montoUSD se calcula automáticamente
        document.getElementById('nuevo_pago_monto_usd').value = (montoBS / window.tasaCambio).toFixed(2);
    } else {
        if (isNaN(montoUSD) || montoUSD <= 0) {
            showToast('error', 'Debes ingresar un monto válido en USD');
            return;
        }
    }
    
    // Obtener valores actualizados
    const montoUSDFinal = parseFloat(document.getElementById('nuevo_pago_monto_usd').value);
    const montoBSFinal = parseFloat(document.getElementById('nuevo_pago_monto_bs').value);
    
    const tipoPagoId = parseInt(tipoPagoSelect.value);
    const tipoPagoNombre = selectedOption.dataset.nombre;
    
    // Verificar que no exceda el total pendiente
    const totalVentaUSD = parseFloat(document.getElementById('total-usd').textContent.replace('$', '')) || 0;
    const totalPagadoUSD = pagosRegistrados.reduce((sum, p) => sum + p.monto_usd, 0);
    const pendienteUSD = totalVentaUSD - totalPagadoUSD;
    
    if (montoUSDFinal > pendienteUSD + 0.01) {
        showToast('warning', `El monto excede el pendiente. Pendiente: $${pendienteUSD.toFixed(2)}`);
        return;
    }
    
    const nuevoPago = {
        id: Date.now() + Math.random(),
        tipo_pago_id: tipoPagoId,
        tipo_pago_nombre: tipoPagoNombre,
        moneda: moneda,
        monto_usd: montoUSDFinal,
        monto_bs: montoBSFinal,
        es_usd: (moneda === 'USD')
    };
    
    pagosRegistrados.push(nuevoPago);
    actualizarListaPagos();
    actualizarResumenPagos();
    
    // Limpiar formulario
    tipoPagoSelect.value = '';
    document.getElementById('nuevo_pago_monto_usd').value = '0.00';
    document.getElementById('nuevo_pago_monto_bs').value = '0.00';
    
    // Restaurar interfaz por defecto
    const labelMontoUSD = document.querySelector('label[for="nuevo_pago_monto_usd"]');
    const labelMontoBS = document.querySelector('label[for="nuevo_pago_monto_bs"]');
    const montoBSInput = document.getElementById('nuevo_pago_monto_bs');
    
    if (labelMontoUSD) labelMontoUSD.innerHTML = 'Monto en USD *';
    if (labelMontoBS) labelMontoBS.innerHTML = 'Equivalente en Bs';
    if (montoBSInput) {
        montoBSInput.readOnly = true;
        montoBSInput.classList.remove('border-primary');
        montoBSInput.placeholder = 'Se calcula automáticamente';
    }
    
    showToast('success', 'Pago agregado correctamente');
}

function actualizarListaPagos() {
    const container = document.getElementById('pagos-container');
    const mensajeSinPagos = document.getElementById('mensaje-sin-pagos');
    
    if (!container) return;
    
    if (pagosRegistrados.length === 0) {
        if (mensajeSinPagos) {
            mensajeSinPagos.classList.remove('d-none');
        } else {
            container.innerHTML = `
                <div class="text-center text-muted py-4" id="mensaje-sin-pagos">
                    <i class="fas fa-money-bill fa-2x mb-2"></i>
                    <p>No hay pagos registrados. Agrega uno o más métodos de pago.</p>
                </div>
            `;
        }
        return;
    }
    
    if (mensajeSinPagos) {
        mensajeSinPagos.classList.add('d-none');
    }
    
    // Paginación simple (mostrar últimos 5 pagos)
    const pagosVisibles = pagosRegistrados.slice(-5);
    let html = '<div class="list-group">';
    
    pagosVisibles.forEach(pago => {
        const fecha = new Date().toLocaleTimeString();
        const esBS = pago.moneda === 'BS';
        
        html += `
            <div class="list-group-item list-group-item-action" data-pago-id="${pago.id}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${pago.tipo_pago_nombre}</strong>
                        <br>
                        <small class="text-muted">${fecha}</small>
                    </div>
                    <div class="text-end">
                        <div class="${esBS ? 'text-warning' : 'text-success'} fw-bold">
                            ${esBS ? 'Bs' : '$'} ${esBS ? pago.monto_bs.toFixed(2) : pago.monto_usd.toFixed(2)}
                        </div>
                        <small>
                            ${esBS ? 
                `$${pago.monto_usd.toFixed(2)} USD` : 
                `Bs ${pago.monto_bs.toFixed(2)}`}
                        </small>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger" onclick="eliminarPago(${pago.id})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    
    if (pagosRegistrados.length > 5) {
        html += `<div class="text-end mt-2">
            <small class="text-muted">Mostrando últimos 5 de ${pagosRegistrados.length} pagos</small>
        </div>`;
    }
    
    container.innerHTML = html;
}

function eliminarPago(pagoId) {
    pagosRegistrados = pagosRegistrados.filter(p => p.id !== pagoId);
    actualizarListaPagos();
    actualizarResumenPagos();
    showToast('info', 'Pago eliminado');
}

function actualizarResumenPagos() {
    const totalVentaUSD = parseFloat(document.getElementById('total-usd')?.textContent.replace('$', '')) || 0;
    const totalVentaBS = parseFloat(document.getElementById('total-bs')?.textContent.replace('Bs', '')) || 0;
    
    let totalUSDRecibido = 0;
    let totalBSRecibido = 0;
    
    pagosRegistrados.forEach(pago => {
        totalUSDRecibido += pago.monto_usd;
        totalBSRecibido += pago.monto_bs;
    });
    
    // Actualizar elementos del DOM
    const elementos = {
        'total-usd-recibido': '$' + totalUSDRecibido.toFixed(2),
        'total-bs-recibido': 'Bs ' + totalBSRecibido.toFixed(2),
        'total-pagos-badge': 'Total Pagado: $' + totalUSDRecibido.toFixed(2),
        'pagado-usd': '$' + totalUSDRecibido.toFixed(2),
        'pagado-bs': 'Bs ' + totalBSRecibido.toFixed(2),
        'total-venta-resumen': `$${totalVentaUSD.toFixed(2)} / Bs ${totalVentaBS.toFixed(2)}`,
        'total-pagado': '$' + totalUSDRecibido.toFixed(2)
    };
    
    for (const [id, valor] of Object.entries(elementos)) {
        const elemento = document.getElementById(id);
        if (elemento) elemento.textContent = valor;
    }
    
    // Calcular vuelto
    const vuelto = totalUSDRecibido - totalVentaUSD;
    const vueltoElement = document.getElementById('vuelto');
    if (vueltoElement) {
        vueltoElement.textContent = (vuelto >= 0 ? '+' : '-') + '$' + Math.abs(vuelto).toFixed(2);
    }
    
    // Actualizar barra de progreso
    const progreso = totalVentaUSD > 0 ? (totalUSDRecibido / totalVentaUSD) * 100 : 0;
    const progresoBar = document.getElementById('progreso-pago-bar');
    const progresoTexto = document.getElementById('progreso-pago-texto');
    const alertaInsuficiente = document.getElementById('alerta-pago-insuficiente');
    const alertaExcedente = document.getElementById('alerta-pago-excedente');
    const estadoPagoTexto = document.getElementById('estado-pago-texto');
    
    if (progresoBar) {
        progresoBar.style.width = Math.min(progreso, 100) + '%';
        progresoBar.textContent = Math.min(progreso, 100).toFixed(0) + '%';
        progresoBar.setAttribute('aria-valuenow', progreso);
    }
    
    if (progresoTexto) {
        progresoTexto.textContent = `$${totalUSDRecibido.toFixed(2)} de $${totalVentaUSD.toFixed(2)} (${progreso.toFixed(0)}%)`;
    }
    
    // Actualizar estado y color
    if (estadoPagoTexto) {
        if (progreso >= 100) {
            estadoPagoTexto.textContent = 'Completado';
            estadoPagoTexto.className = 'text-success';
            if (progresoBar) {
                progresoBar.classList.remove('bg-warning', 'bg-danger');
                progresoBar.classList.add('bg-success');
            }
        } else if (progreso > 0) {
            estadoPagoTexto.textContent = 'Pago Parcial';
            estadoPagoTexto.className = 'text-warning';
            if (progresoBar) {
                progresoBar.classList.remove('bg-success', 'bg-danger');
                progresoBar.classList.add('bg-warning');
            }
        } else {
            estadoPagoTexto.textContent = 'Pendiente';
            estadoPagoTexto.className = 'text-danger';
            if (progresoBar) {
                progresoBar.classList.remove('bg-success', 'bg-warning');
                progresoBar.classList.add('bg-danger');
            }
        }
    }
    
    // Mostrar alertas
    if (alertaInsuficiente && alertaExcedente) {
        if (totalUSDRecibido < totalVentaUSD - 0.01) {
            const faltante = totalVentaUSD - totalUSDRecibido;
            document.getElementById('monto-faltante').textContent = '$' + faltante.toFixed(2);
            alertaInsuficiente.classList.remove('d-none');
            alertaExcedente.classList.add('d-none');
        } else if (totalUSDRecibido > totalVentaUSD + 0.01) {
            const excedente = totalUSDRecibido - totalVentaUSD;
            document.getElementById('monto-excedente').textContent = '$' + excedente.toFixed(2);
            alertaInsuficiente.classList.add('d-none');
            alertaExcedente.classList.remove('d-none');
        } else {
            alertaInsuficiente.classList.add('d-none');
            alertaExcedente.classList.add('d-none');
        }
    }
    
    // Habilitar/deshabilitar botón de guardar
    const btnGuardar = document.getElementById('btn-guardar-venta');
    const clienteSelect = document.getElementById('cliente_id');
    
    if (btnGuardar) {
        if (productosSeleccionados.length > 0 && 
            clienteSelect && clienteSelect.value && 
            pagosRegistrados.length > 0 && 
            totalUSDRecibido >= totalVentaUSD - 0.01) {
            btnGuardar.disabled = false;
        } else {
            btnGuardar.disabled = true;
        }
    }
}

// =========================================================================
// FUNCIONES PARA CLIENTES
// =========================================================================

function crearCliente() {
    const form = document.getElementById('formNuevoCliente');
    if (!form) return;
    
    const formData = new FormData(form);
    formData.append('action', 'crear_cliente');

    const submitBtn = document.querySelector('#modalNuevoCliente .btn-primary');
    if (!submitBtn) return;
    
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';
    submitBtn.disabled = true;

    fetch('crear.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            const clienteMessage = document.getElementById('cliente-message');
            if (!clienteMessage) return;
            
            if (data.success) {
                clienteMessage.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        ${data.message}
                    </div>
                `;

                actualizarSelectClientes(data.id, formData.get('nombre'), formData.get('numero_documento'));

                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevoCliente'));
                    if (modal) modal.hide();
                    form.reset();
                    clienteMessage.innerHTML = '';
                }, 2000);
            } else {
                clienteMessage.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            const clienteMessage = document.getElementById('cliente-message');
            if (clienteMessage) {
                clienteMessage.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error de conexión: ${error}
                    </div>
                `;
            }
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
}

function actualizarSelectClientes(clienteId, clienteNombre, documento) {
    const select = document.getElementById('cliente_id');
    if (!select) return;
    
    const option = document.createElement('option');
    option.value = clienteId;
    option.text = clienteNombre + (documento ? ` (${documento})` : '');
    option.selected = true;
    select.appendChild(option);
}

// =========================================================================
// FUNCIONES UTILITARIAS
// =========================================================================

function limpiarFormulario() {
    if (confirm('¿Estás seguro de que deseas limpiar todo el formulario? Se perderán todos los datos ingresados.')) {
        document.getElementById('formVenta')?.reset();
        productosSeleccionados = [];
        pagosRegistrados = [];
        actualizarListaProductos();
        actualizarContadoresProductos();
        calcularTotalVenta();
        actualizarListaPagos();
        actualizarResumenPagos();
        limpiarBusqueda();
        showToast('info', 'Formulario limpiado correctamente.');
    }
}

function showToast(type, message) {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    const toast = document.createElement('div');

    let bgColor = 'bg-info';
    let icon = 'fa-info-circle';
    
    if (type === 'success') {
        bgColor = 'bg-success';
        icon = 'fa-check-circle';
    } else if (type === 'error') {
        bgColor = 'bg-danger';
        icon = 'fa-exclamation-triangle';
    } else if (type === 'warning') {
        bgColor = 'bg-warning';
        icon = 'fa-exclamation-triangle';
    }

    toast.className = `toast align-items-center text-white ${bgColor} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas ${icon} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    toastContainer.appendChild(toast);

    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();

    toast.addEventListener('hidden.bs.toast', function () {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// Event listeners para modales
document.addEventListener('DOMContentLoaded', function() {
    const modalNuevoCliente = document.getElementById('modalNuevoCliente');
    if (modalNuevoCliente) {
        modalNuevoCliente.addEventListener('hidden.bs.modal', function () {
            document.getElementById('formNuevoCliente')?.reset();
            const clienteMessage = document.getElementById('cliente-message');
            if (clienteMessage) clienteMessage.innerHTML = '';
        });
    }
});

// Validación antes de enviar el formulario
document.addEventListener('DOMContentLoaded', function() {
    const formVenta = document.getElementById('formVenta');
    if (formVenta) {
        formVenta.addEventListener('submit', function(e) {
            if (pagosRegistrados.length === 0) {
                e.preventDefault();
                showToast('error', 'Debes registrar al menos un pago');
                return;
            }
            
            const totalVentaUSD = parseFloat(document.getElementById('total-usd')?.textContent.replace('$', '')) || 0;
            const totalPagadoUSD = pagosRegistrados.reduce((sum, p) => sum + p.monto_usd, 0);
            
            if (totalPagadoUSD < totalVentaUSD - 0.01) {
                e.preventDefault();
                showToast('error', 'El total pagado es insuficiente para completar la venta');
                return;
            }
            
            // Eliminar campos ocultos previos
            const inputsOcultos = formVenta.querySelectorAll('input[name^="pagos["]');
            inputsOcultos.forEach(input => input.remove());
            
            // Crear campos ocultos para cada pago
            pagosRegistrados.forEach((pago, index) => {
                const inputTipoPago = document.createElement('input');
                inputTipoPago.type = 'hidden';
                inputTipoPago.name = `pagos[${index}][tipo_pago_id]`;
                inputTipoPago.value = pago.tipo_pago_id;
                formVenta.appendChild(inputTipoPago);
                
                const inputMontoUSD = document.createElement('input');
                inputMontoUSD.type = 'hidden';
                inputMontoUSD.name = `pagos[${index}][monto_usd]`;
                inputMontoUSD.value = pago.monto_usd.toFixed(2);
                formVenta.appendChild(inputMontoUSD);
                
                const inputMontoBS = document.createElement('input');
                inputMontoBS.type = 'hidden';
                inputMontoBS.name = `pagos[${index}][monto_bs]`;
                inputMontoBS.value = pago.monto_bs.toFixed(2);
                formVenta.appendChild(inputMontoBS);
            });
        });
    }
});
