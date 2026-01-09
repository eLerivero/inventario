// Variables globales
let productosSeleccionados = [];

document.addEventListener('DOMContentLoaded', function () {
    inicializarBusqueda();
    actualizarContadores();

    if (!window.tasaInfo) {
        showToast('error', 'No hay tasa de cambio configurada. Configure la tasa primero.');
    }

    if (window.productosData && window.productosData.length === 0) {
        showToast('warning', 'No hay productos disponibles. Debes crear productos primero.');
    }
});

// Sistema de búsqueda avanzada
function inicializarBusqueda() {
    const buscarInput = document.getElementById('buscar-producto');
    const filtroCategoria = document.getElementById('filtro-categoria');

    // Búsqueda en tiempo real
    buscarInput.addEventListener('input', function () {
        buscarProductos();
    });

    // Filtro por categoría
    filtroCategoria.addEventListener('change', function () {
        buscarProductos();
    });

    // Prevenir envío del formulario al presionar Enter en la búsqueda
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

    // Si no hay término y no hay categoría, ocultar resultados
    if (!termino && !categoria) {
        document.getElementById('resultados-busqueda').classList.add('d-none');
        document.getElementById('contador-resultados').textContent = '';
        return;
    }

    // Mostrar loading
    const resultadosDiv = document.getElementById('resultados-busqueda');
    const listaProductos = document.getElementById('lista-productos');

    resultadosDiv.classList.remove('d-none');
    listaProductos.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary me-2"></div>
            Buscando productos...
        </div>
    `;

    // Hacer búsqueda en el servidor
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
        // Verificar si el producto ya está seleccionado
        const yaSeleccionado = productosSeleccionados.some(p => p.id === producto.id);

        // Determinar clase de stock
        let stockClase = 'stock-alto';
        if (producto.stock_actual <= 10) stockClase = 'stock-medio';
        if (producto.stock_actual <= 3) stockClase = 'stock-bajo';

        // Precio a mostrar
        let precioMostrar = '';
        if (producto.usar_precio_fijo_bs) {
            precioMostrar = `${parseFloat(producto.precio_bs).toFixed(2)} Bs (Fijo)`;
        } else {
            precioMostrar = `${parseFloat(producto.precio).toFixed(2)} USD`;
        }

        html += `
            <div class="list-group-item producto-busqueda ${yaSeleccionado ? 'seleccionado' : ''}" 
                 data-producto-id="${producto.id}"
                 onclick="${!yaSeleccionado ? `seleccionarProducto(${JSON.stringify(producto).replace(/"/g, '&quot;')})` : ''}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-1">
                            <strong>${producto.nombre}</strong>
                            ${producto.usar_precio_fijo_bs ?
                '<span class="badge precio-fijo-badge ms-2">Precio Fijo</span>' :
                '<span class="badge precio-normal-badge ms-2">USD</span>'}
                            <span class="badge ${stockClase} badge-stock ms-2">
                                Stock: ${producto.stock_actual}
                            </span>
                        </div>
                        <div class="producto-info">
                            ${producto.codigo_sku ? `<span class="me-3"><i class="fas fa-barcode"></i> ${producto.codigo_sku}</span>` : ''}
                            ${producto.categoria_nombre ? `<span class="me-3"><i class="fas fa-tag"></i> ${producto.categoria_nombre}</span>` : ''}
                            ${producto.usar_precio_fijo_bs ?
                `<span class="precio-fijo"><i class="fas fa-bolt"></i> ${precioMostrar}</span>` :
                `<span class="precio-normal"><i class="fas fa-dollar-sign"></i> ${precioMostrar}</span>`}
                            ${!producto.usar_precio_fijo_bs && window.tasaCambio > 0 ?
                `<span class="ms-3"><i class="fas fa-calculator"></i> ${(parseFloat(producto.precio) * window.tasaCambio).toFixed(2)} Bs</span>` : ''}
                        </div>
                    </div>
                    <div>
                        ${yaSeleccionado ?
                '<span class="badge bg-success"><i class="fas fa-check"></i> Agregado</span>' :
                '<button class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Agregar</button>'}
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
    // Verificar si ya está seleccionado
    if (productosSeleccionados.some(p => p.id === producto.id)) {
        showToast('info', 'Este producto ya está en la lista');
        return;
    }

    // Verificar stock
    if (producto.stock_actual <= 0) {
        showToast('error', 'Producto sin stock disponible');
        return;
    }

    // CRÍTICO: Para productos con precio fijo, el controlador usará precio_bs directamente
    // Para productos normales, usar precio USD
    let precioUSD = 0;

    if (!producto.usar_precio_fijo_bs && producto.precio) {
        precioUSD = parseFloat(producto.precio);
    }

    // IMPORTANTE: Guardar el precio BS EXACTO de la BD
    const precioBSExacto = parseFloat(producto.precio_bs) || 0;

    // Agregar producto a la lista
    productosSeleccionados.push({
        id: producto.id,
        nombre: producto.nombre,
        precio: precioUSD, // Para precio fijo, este puede ser 0
        precio_bs: precioBSExacto, // ¡EXACTO de la BD!
        usar_precio_fijo_bs: producto.usar_precio_fijo_bs,
        stock_actual: producto.stock_actual,
        cantidad: 1,
        sku: producto.codigo_sku || '',
        categoria: producto.categoria_nombre || ''
    });

    // Actualizar interfaz
    actualizarListaProductos();
    actualizarContadores();
    calcularTotal();

    // Mostrar mensaje
    showToast('success', 'Producto agregado correctamente');
    buscarProductos();
}

function actualizarListaProductos() {
    const container = document.getElementById('productos-container');
    const mensajeSinProductos = document.getElementById('mensaje-sin-productos');

    if (productosSeleccionados.length === 0) {
        // ... código para lista vacía ...
        return;
    }

    let html = '';

    productosSeleccionados.forEach((producto, index) => {
        let subtotalUSD = 0;
        let subtotalBS = 0;
        let precioMostrar = '';
        let precioUnitario = '';

        if (producto.usar_precio_fijo_bs) {
            // CRÍTICO: Para precio fijo, usar EXACTAMENTE el precio BS
            subtotalBS = producto.cantidad * producto.precio_bs;
            precioMostrar = `${producto.precio_bs.toFixed(2)} Bs`;
            precioUnitario = `${producto.precio_bs.toFixed(2)} Bs (Precio Fijo)`;

            // Calcular USD solo para visualización (no para cálculo real)
            if (window.tasaCambio > 0) {
                subtotalUSD = subtotalBS / window.tasaCambio;
                precioUnitario += ` ($${(producto.precio_bs / window.tasaCambio).toFixed(4)} USD)`;
            }
        } else {
            // Producto normal
            subtotalUSD = producto.cantidad * producto.precio;
            subtotalBS = subtotalUSD * window.tasaCambio;
            precioMostrar = `$${producto.precio.toFixed(2)} USD`;
            precioUnitario = `$${producto.precio.toFixed(2)} USD`;
        }

        html += `
            <div class="producto-seleccionado p-3 border-bottom" data-index="${index}">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <strong>${producto.nombre}</strong>
                        <div class="producto-info small text-muted">
                            ${producto.sku ? `<div><i class="fas fa-barcode"></i> ${producto.sku}</div>` : ''}
                            ${producto.categoria ? `<div><i class="fas fa-tag"></i> ${producto.categoria}</div>` : ''}
                            <div>
                                <i class="fas fa-box"></i> Stock: ${producto.stock_actual} 
                                ${producto.usar_precio_fijo_bs ?
                `| <i class="fas fa-lock text-warning"></i> Precio Fijo BS` :
                `| <i class="fas fa-dollar-sign text-success"></i> Precio USD`}
                            </div>
                            <div class="mt-1">
                                <small>${precioUnitario}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="cantidad-input-container d-flex align-items-center">
                            <button class="btn btn-sm btn-outline-secondary btn-cantidad" onclick="cambiarCantidad(${index}, -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" 
                                   class="form-control form-control-sm text-center mx-2" 
                                   value="${producto.cantidad}" 
                                   min="1" 
                                   max="${producto.stock_actual}"
                                   onchange="actualizarCantidad(${index}, this.value)"
                                   style="width: 70px;">
                            <button class="btn btn-sm btn-outline-secondary btn-cantidad" onclick="cambiarCantidad(${index}, 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                            <small class="ms-2 text-muted">máx ${producto.stock_actual}</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-end">
                            <div><strong>Subtotal:</strong></div>
                            ${producto.usar_precio_fijo_bs ?
                `<div class="text-warning fw-bold"><i class="fas fa-bolt"></i> ${subtotalBS.toFixed(2)} Bs</div>
                                 <div><small>($${subtotalUSD.toFixed(2)} USD)</small></div>` :
                `<div class="text-success fw-bold">$${subtotalUSD.toFixed(2)} USD</div>
                                 <div><small>(${subtotalBS.toFixed(2)} Bs)</small></div>`}
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <button class="btn btn-sm btn-danger" onclick="eliminarProductoSeleccionado(${index})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Campos ocultos para el formulario -->
                <input type="hidden" name="productos[]" value="${producto.id}">
                <input type="hidden" name="cantidades[]" value="${producto.cantidad}">
                <input type="hidden" name="precios[]" value="${producto.precio}">
                <input type="hidden" name="es_precio_fijo[]" value="${producto.usar_precio_fijo_bs ? '1' : '0'}">
            </div>
        `;
    });

    container.innerHTML = html;
    document.getElementById('btn-guardar-venta').disabled = productosSeleccionados.length === 0;
}

function calcularTotal() {
    let subtotalUSD = 0;
    let subtotalBS = 0;

    productosSeleccionados.forEach(producto => {
        if (producto.usar_precio_fijo_bs) {
            // CRÍTICO: Para precio fijo, multiplicar por el precio BS EXACTO
            const subtotalItemBS = producto.cantidad * producto.precio_bs;
            const subtotalItemUSD = window.tasaCambio > 0 ? (subtotalItemBS / window.tasaCambio) : 0;

            subtotalBS += subtotalItemBS;
            subtotalUSD += subtotalItemUSD;
        } else {
            // Producto normal
            const subtotalItemUSD = producto.cantidad * producto.precio;
            const subtotalItemBS = subtotalItemUSD * window.tasaCambio;

            subtotalUSD += subtotalItemUSD;
            subtotalBS += subtotalItemBS;
        }
    });

    document.getElementById('total-usd').textContent = '$' + subtotalUSD.toFixed(2);
    document.getElementById('total-bs').textContent = 'Bs ' + subtotalBS.toFixed(2);
    document.getElementById('tasa-cambio').textContent = (window.tasaCambio || 0).toFixed(2) + ' Bs/$';

    actualizarContadores();
}

function actualizarListaProductos() {
    const container = document.getElementById('productos-container');
    const mensajeSinProductos = document.getElementById('mensaje-sin-productos');

    if (productosSeleccionados.length === 0) {
        if (!mensajeSinProductos) {
            container.innerHTML = `
                <div class="text-center text-muted py-4" id="mensaje-sin-productos">
                    <i class="fas fa-box-open fa-2x mb-2"></i>
                    <p>No hay productos agregados. Busca y selecciona productos para comenzar.</p>
                </div>
            `;
        } else {
            mensajeSinProductos.classList.remove('d-none');
        }

        // Deshabilitar botón de guardar
        document.getElementById('btn-guardar-venta').disabled = true;
        return;
    }

    // Ocultar mensaje si existe
    if (mensajeSinProductos) {
        mensajeSinProductos.classList.add('d-none');
    }

    let html = '';

    productosSeleccionados.forEach((producto, index) => {
        let subtotalUSD = 0;
        let subtotalBS = 0;
        let precioMostrar = '';
        let precioUnitario = '';

        if (producto.usar_precio_fijo_bs) {
            // Para productos con precio fijo
            subtotalBS = producto.cantidad * producto.precio_bs;
            precioMostrar = `${producto.precio_bs.toFixed(2)} Bs`;
            precioUnitario = producto.precio_bs.toFixed(2);

            // Calcular equivalente en USD para mostrar (solo visual)
            if (window.tasaCambio > 0) {
                subtotalUSD = subtotalBS / window.tasaCambio;
                precioUnitario += ` ($${(producto.precio_bs / window.tasaCambio).toFixed(2)} USD)`;
            }
        } else {
            // Para productos normales
            subtotalUSD = producto.cantidad * producto.precio;
            subtotalBS = subtotalUSD * window.tasaCambio;
            precioMostrar = `$${producto.precio.toFixed(2)} USD`;
            precioUnitario = `$${producto.precio.toFixed(2)} USD`;
        }

        html += `
            <div class="producto-seleccionado p-3" data-index="${index}">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <strong>${producto.nombre}</strong>
                        <div class="producto-info">
                            ${producto.sku ? `<div><i class="fas fa-barcode"></i> ${producto.sku}</div>` : ''}
                            ${producto.categoria ? `<div><i class="fas fa-tag"></i> ${producto.categoria}</div>` : ''}
                            <div>
                                <i class="fas fa-box"></i> Stock: ${producto.stock_actual} 
                                ${producto.usar_precio_fijo_bs ?
                `| <i class="fas fa-lock text-warning"></i> Precio Fijo BS` :
                `| <i class="fas fa-dollar-sign text-success"></i> Precio USD`}
                            </div>
                            <div class="mt-1">
                                <small>Precio unitario: ${precioUnitario}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="cantidad-input-container">
                            <button class="btn btn-sm btn-outline-secondary btn-cantidad" onclick="cambiarCantidad(${index}, -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" 
                                   class="form-control form-control-sm text-center" 
                                   value="${producto.cantidad}" 
                                   min="1" 
                                   max="${producto.stock_actual}"
                                   onchange="actualizarCantidad(${index}, this.value)"
                                   data-producto-index="${index}">
                            <button class="btn btn-sm btn-outline-secondary btn-cantidad" onclick="cambiarCantidad(${index}, 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                            <small class="ms-2 text-muted">max ${producto.stock_actual}</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-end">
                            <div><strong>Subtotal:</strong></div>
                            ${producto.usar_precio_fijo_bs ?
                `<div class="text-warning"><i class="fas fa-bolt"></i> ${subtotalBS.toFixed(2)} Bs</div>
     ${window.tasaCambio > 0 ? `<div><small>($${subtotalUSD.toFixed(2)} USD)</small></div>` : ''}` :
                `<div class="text-success">$${subtotalUSD.toFixed(2)} USD</div>
     <div><small>(${subtotalBS.toFixed(2)} Bs)</small></div>`}
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <button class="btn btn-sm btn-danger" onclick="eliminarProductoSeleccionado(${index})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Campos ocultos para el formulario -->
                <input type="hidden" name="productos[]" value="${producto.id}">
                <input type="hidden" name="cantidades[]" value="${producto.cantidad}">
                <input type="hidden" name="precios[]" value="${producto.precio}">
                <input type="hidden" name="es_precio_fijo[]" value="${producto.usar_precio_fijo_bs ? '1' : '0'}">
            </div>
        `;
    });

    container.innerHTML = html;

    // Habilitar botón de guardar
    document.getElementById('btn-guardar-venta').disabled = productosSeleccionados.length === 0;
}
function actualizarCantidad(index, nuevaCantidad) {
    nuevaCantidad = parseInt(nuevaCantidad);

    if (isNaN(nuevaCantidad) || nuevaCantidad < 1) {
        nuevaCantidad = 1;
    }

    // Verificar stock máximo
    const stockMaximo = productosSeleccionados[index].stock_actual;
    if (nuevaCantidad > stockMaximo) {
        nuevaCantidad = stockMaximo;
        showToast('warning', 'No hay suficiente stock disponible');
    }

    productosSeleccionados[index].cantidad = nuevaCantidad;
    actualizarListaProductos();
    calcularTotal();
}

function cambiarCantidad(index, cambio) {
    const nuevaCantidad = productosSeleccionados[index].cantidad + cambio;
    actualizarCantidad(index, nuevaCantidad);
}

function eliminarProductoSeleccionado(index) {
    productosSeleccionados.splice(index, 1);
    actualizarListaProductos();
    actualizarContadores();
    calcularTotal();
    buscarProductos(); // Actualizar búsqueda para quitar "agregado"
    showToast('info', 'Producto eliminado de la lista');
}

function actualizarContadores() {
    const contadorProductos = document.getElementById('contador-productos');
    const cantidadTotalProductos = document.getElementById('cantidad-total-productos');

    // Contar productos únicos
    contadorProductos.textContent = productosSeleccionados.length;

    // Contar cantidad total de productos
    const totalItems = productosSeleccionados.reduce((total, producto) => total + producto.cantidad, 0);
    cantidadTotalProductos.textContent = totalItems;
}

function calcularTotal() {
    let subtotalUSD = 0;
    let subtotalBS = 0;

    productosSeleccionados.forEach(producto => {
        if (producto.usar_precio_fijo_bs) {
            // **PRODUCTO CON PRECIO FIJO EN BS**
            // Calcular BS directamente del precio fijo
            const subtotalItemBS = producto.cantidad * producto.precio_bs;

            // Calcular USD dividiendo por tasa (solo para visualización)
            const subtotalItemUSD = window.tasaCambio > 0 ? (subtotalItemBS / window.tasaCambio) : 0;

            subtotalBS += subtotalItemBS;
            subtotalUSD += subtotalItemUSD;

            // **DEBUG EN CONSOLA**
            console.log("Producto precio fijo: " + producto.nombre);
            console.log("  Precio BS fijo: " + producto.precio_bs);
            console.log("  Subtotal BS: " + subtotalItemBS);
            console.log("  Subtotal USD: " + subtotalItemUSD);

        } else {
            // **PRODUCTO SIN PRECIO FIJO**
            const subtotalItemUSD = producto.cantidad * producto.precio;
            const subtotalItemBS = subtotalItemUSD * window.tasaCambio;

            subtotalUSD += subtotalItemUSD;
            subtotalBS += subtotalItemBS;
        }
    });

    // Formatear con 2 decimales exactos
    document.getElementById('total-usd').textContent = '$' + subtotalUSD.toFixed(2);
    document.getElementById('total-bs').textContent = 'Bs ' + subtotalBS.toFixed(2);

    actualizarContadores();
}

function limpiarBusqueda() {
    document.getElementById('buscar-producto').value = '';
    document.getElementById('filtro-categoria').value = '';
    document.getElementById('resultados-busqueda').classList.add('d-none');
    document.getElementById('contador-resultados').textContent = '';
}

// Funciones para gestión de clientes
function crearCliente() {
    const form = document.getElementById('formNuevoCliente');
    const formData = new FormData(form);
    formData.append('action', 'crear_cliente');

    // Mostrar loading
    const submitBtn = document.querySelector('#modalNuevoCliente .btn-primary');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';
    submitBtn.disabled = true;

    fetch('crear.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('cliente-message').innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        ${data.message}
                    </div>
                `;

                actualizarSelectClientes(data.id, formData.get('nombre'), formData.get('numero_documento'));

                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevoCliente'));
                    modal.hide();
                    form.reset();
                    document.getElementById('cliente-message').innerHTML = '';
                }, 2000);
            } else {
                document.getElementById('cliente-message').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('cliente-message').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error de conexión: ${error}
                </div>
            `;
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
}

function actualizarSelectClientes(clienteId, clienteNombre, documento) {
    const select = document.getElementById('cliente_id');
    const option = document.createElement('option');
    option.value = clienteId;
    option.text = clienteNombre + (documento ? ` (${documento})` : '');
    option.selected = true;

    select.appendChild(option);
}

function limpiarFormulario() {
    if (confirm('¿Estás seguro de que deseas limpiar todo el formulario? Se perderán todos los datos ingresados.')) {
        document.getElementById('formVenta').reset();
        productosSeleccionados = [];
        actualizarListaProductos();
        actualizarContadores();
        calcularTotal();
        limpiarBusqueda();
        showToast('info', 'Formulario limpiado correctamente.');
    }
}

function showToast(type, message) {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    const toast = document.createElement('div');

    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'warning' ? 'warning' : type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
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

// Limpiar mensajes del modal cuando se cierre
document.getElementById('modalNuevoCliente').addEventListener('hidden.bs.modal', function () {
    document.getElementById('formNuevoCliente').reset();
    document.getElementById('cliente-message').innerHTML = '';
});