-- data.sql
-- Sistema de Inventario - Base de datos completa
-- PostgreSQL

-- Eliminar tablas existentes (en orden inverso por dependencias)
DROP TABLE IF EXISTS historial_stock CASCADE;
DROP TABLE IF EXISTS detalle_ventas CASCADE;
DROP TABLE IF EXISTS ventas CASCADE;
DROP TABLE IF EXISTS tipos_pago CASCADE;
DROP TABLE IF EXISTS productos CASCADE;
DROP TABLE IF EXISTS categorias CASCADE;
DROP TABLE IF EXISTS clientes CASCADE;
DROP TABLE IF EXISTS usuarios CASCADE;
DROP TABLE IF EXISTS tasas_cambio CASCADE;
DROP TABLE IF EXISTS movimientos_inventario CASCADE;
DROP TABLE IF EXISTS proveedores CASCADE;

-- Crear tabla de usuarios
CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    rol VARCHAR(20) DEFAULT 'usuario',
    activo BOOLEAN DEFAULT TRUE,
    ultimo_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla de categorías
CREATE TABLE categorias (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla de proveedores
CREATE TABLE proveedores (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    rif VARCHAR(20) UNIQUE,
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion TEXT,
    contacto_nombre VARCHAR(100),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla de tipos de pago
CREATE TABLE tipos_pago (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    requiere_efectivo BOOLEAN DEFAULT FALSE,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla de productos
CREATE TABLE productos (
    id SERIAL PRIMARY KEY,
    codigo_sku VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL DEFAULT 0,
    precio_bs DECIMAL(10,2) DEFAULT 0,
    precio_costo DECIMAL(10,2) DEFAULT 0,
    precio_costo_bs DECIMAL(10,2) DEFAULT 0,
    moneda_base VARCHAR(3) DEFAULT 'USD',
    usar_precio_fijo_bs BOOLEAN DEFAULT FALSE,
    stock_actual INTEGER DEFAULT 0,
    stock_minimo INTEGER DEFAULT 5,
    categoria_id INTEGER,
    proveedor_id INTEGER,
    activo BOOLEAN DEFAULT TRUE,
    imagen_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Claves foráneas
    CONSTRAINT fk_producto_categoria 
        FOREIGN KEY (categoria_id) 
        REFERENCES categorias(id) 
        ON DELETE SET NULL,
        
    CONSTRAINT fk_producto_proveedor 
        FOREIGN KEY (proveedor_id) 
        REFERENCES proveedores(id) 
        ON DELETE SET NULL
);

-- Crear tabla de clientes
CREATE TABLE clientes (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    tipo_documento VARCHAR(20) DEFAULT 'V',
    numero_documento VARCHAR(20) UNIQUE,
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion TEXT,
    tipo_cliente VARCHAR(20) DEFAULT 'normal',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Crear tabla de ventas
CREATE TABLE ventas (
    id SERIAL PRIMARY KEY,
    numero_venta VARCHAR(50) UNIQUE NOT NULL,
    cliente_id INTEGER,
    usuario_id INTEGER NOT NULL,
    tipo_pago_id INTEGER,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    iva DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    moneda VARCHAR(3) DEFAULT 'USD',
    tasa_cambio DECIMAL(10,4),
    total_bs DECIMAL(10,2),
    estado VARCHAR(20) DEFAULT 'pendiente',
    metodo_pago VARCHAR(50),
    observaciones TEXT,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Agregar claves foráneas a ventas después de crear la tabla
ALTER TABLE ventas 
ADD CONSTRAINT fk_venta_cliente 
FOREIGN KEY (cliente_id) 
REFERENCES clientes(id) 
ON DELETE SET NULL;

ALTER TABLE ventas 
ADD CONSTRAINT fk_venta_usuario 
FOREIGN KEY (usuario_id) 
REFERENCES usuarios(id) 
ON DELETE RESTRICT;

ALTER TABLE ventas 
ADD CONSTRAINT fk_venta_tipo_pago 
FOREIGN KEY (tipo_pago_id) 
REFERENCES tipos_pago(id) 
ON DELETE SET NULL;

-- Crear tabla de detalles de venta
CREATE TABLE detalle_ventas (
    id SERIAL PRIMARY KEY,
    venta_id INTEGER NOT NULL,
    producto_id INTEGER NOT NULL,
    cantidad INTEGER NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    precio_unitario_bs DECIMAL(10,2),
    subtotal DECIMAL(10,2) NOT NULL,
    subtotal_bs DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Agregar claves foráneas a detalle_ventas después de crear la tabla
ALTER TABLE detalle_ventas 
ADD CONSTRAINT fk_detalle_venta 
FOREIGN KEY (venta_id) 
REFERENCES ventas(id) 
ON DELETE CASCADE;

ALTER TABLE detalle_ventas 
ADD CONSTRAINT fk_detalle_producto 
FOREIGN KEY (producto_id) 
REFERENCES productos(id) 
ON DELETE RESTRICT;

-- Crear tabla de tasas de cambio
CREATE TABLE tasas_cambio (
    id SERIAL PRIMARY KEY,
    tasa_cambio DECIMAL(10,4) NOT NULL,
    moneda_origen VARCHAR(3) DEFAULT 'USD',
    moneda_destino VARCHAR(3) DEFAULT 'VES',
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activa BOOLEAN DEFAULT TRUE,
    observaciones TEXT,
    usuario_id INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    
);

-- Agregar clave foránea a tasas_cambio después de crear la tabla
ALTER TABLE tasas_cambio 
ADD CONSTRAINT fk_tasa_usuario 
FOREIGN KEY (usuario_id) 
REFERENCES usuarios(id) 
ON DELETE SET NULL;

-- Crear tabla de historial de stock
CREATE TABLE historial_stock (
    id SERIAL PRIMARY KEY,
    producto_id INTEGER NOT NULL,
    cantidad_anterior INTEGER NOT NULL,
    cantidad_nueva INTEGER NOT NULL,
    diferencia INTEGER NOT NULL,
    tipo_movimiento VARCHAR(50) NOT NULL,
    referencia_id INTEGER,
    referencia_tabla VARCHAR(50),
    observaciones TEXT,
    usuario_id INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Agregar claves foráneas a historial_stock después de crear la tabla
ALTER TABLE historial_stock 
ADD CONSTRAINT fk_historial_producto 
FOREIGN KEY (producto_id) 
REFERENCES productos(id) 
ON DELETE CASCADE;

ALTER TABLE historial_stock 
ADD CONSTRAINT fk_historial_usuario 
FOREIGN KEY (usuario_id) 
REFERENCES usuarios(id) 
ON DELETE SET NULL;

-- Crear tabla de movimientos de inventario
CREATE TABLE movimientos_inventario (
    id SERIAL PRIMARY KEY,
    tipo_movimiento VARCHAR(50) NOT NULL,
    producto_id INTEGER NOT NULL,
    cantidad INTEGER NOT NULL,
    motivo TEXT,
    referencia VARCHAR(100),
    usuario_id INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Agregar claves foráneas a movimientos_inventario después de crear la tabla
ALTER TABLE movimientos_inventario 
ADD CONSTRAINT fk_movimiento_producto 
FOREIGN KEY (producto_id) 
REFERENCES productos(id) 
ON DELETE RESTRICT;

ALTER TABLE movimientos_inventario 
ADD CONSTRAINT fk_movimiento_usuario 
FOREIGN KEY (usuario_id) 
REFERENCES usuarios(id) 
ON DELETE RESTRICT;

-- ÍNDICES para mejor rendimiento

-- Índices para productos
CREATE INDEX idx_productos_sku ON productos(codigo_sku);
CREATE INDEX idx_productos_nombre ON productos(nombre);
CREATE INDEX idx_productos_activo ON productos(activo);
CREATE INDEX idx_productos_categoria ON productos(categoria_id);
CREATE INDEX idx_productos_stock ON productos(stock_actual);
CREATE INDEX idx_productos_precio_fijo ON productos(usar_precio_fijo_bs);

-- Índices para ventas
CREATE INDEX idx_ventas_numero ON ventas(numero_venta);
CREATE INDEX idx_ventas_cliente ON ventas(cliente_id);
CREATE INDEX idx_ventas_usuario ON ventas(usuario_id);
CREATE INDEX idx_ventas_tipo_pago ON ventas(tipo_pago_id);
CREATE INDEX idx_ventas_estado ON ventas(estado);
CREATE INDEX idx_ventas_fecha ON ventas(fecha_hora);
CREATE INDEX idx_ventas_created ON ventas(created_at);

-- Índices para detalle_ventas
CREATE INDEX idx_detalle_venta ON detalle_ventas(venta_id);
CREATE INDEX idx_detalle_producto ON detalle_ventas(producto_id);

-- Índices para clientes
CREATE INDEX idx_clientes_documento ON clientes(numero_documento);
CREATE INDEX idx_clientes_nombre ON clientes(nombre);
CREATE INDEX idx_clientes_activo ON clientes(activo);

-- Índices para tipos_pago
CREATE INDEX idx_tipos_pago_nombre ON tipos_pago(nombre);
CREATE INDEX idx_tipos_pago_activo ON tipos_pago(activo);

-- Índices para historial_stock
CREATE INDEX idx_historial_producto ON historial_stock(producto_id);
CREATE INDEX idx_historial_fecha ON historial_stock(created_at);
CREATE INDEX idx_historial_movimiento ON historial_stock(tipo_movimiento);

-- Índices para tasas_cambio
CREATE INDEX idx_tasas_activa ON tasas_cambio(activa);
CREATE INDEX idx_tasas_fecha ON tasas_cambio(fecha_actualizacion);

-- DATOS INICIALES

-- Insertar usuario administrador por defecto (password: admin123)
INSERT INTO usuarios (username, password_hash, nombre, email, rol, activo) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        'Administrador', 'admin@inventario.com', 'admin', TRUE);

-- Insertar tipos de pago por defecto
INSERT INTO tipos_pago (nombre, descripcion, requiere_efectivo) VALUES
('Efectivo', 'Pago en efectivo en moneda local', TRUE),
('Efectivo USD', 'Pago en efectivo en dólares', TRUE),
('Transferencia', 'Transferencia bancaria', FALSE),
('Pago Móvil', 'Pago a través de pago móvil', FALSE),
('Tarjeta de Débito', 'Pago con tarjeta de débito', FALSE),
('Tarjeta de Crédito', 'Pago con tarjeta de crédito', FALSE),
('Divisa', 'Pago en divisas físicas', TRUE),
('Mixto', 'Combinación de métodos de pago', FALSE),
('Crédito', 'Venta a crédito', FALSE);

-- Insertar categorías por defecto
INSERT INTO categorias (nombre, descripcion) VALUES
('Electrónica', 'Dispositivos electrónicos y componentes'),
('Informática', 'Equipos de computación y accesorios'),
('Oficina', 'Artículos de oficina y papelería'),
('Hogar', 'Artículos para el hogar'),
('Alimentos', 'Productos alimenticios'),
('Bebidas', 'Bebidas y refrescos'),
('Limpieza', 'Productos de limpieza'),
('Ropa', 'Prendas de vestir'),
('Ferretería', 'Herramientas y materiales de construcción'),
('Otros', 'Otras categorías');

-- Insertar proveedores por defecto
INSERT INTO proveedores (nombre, rif, telefono, email, contacto_nombre) VALUES
('Proveedor General S.A.', 'J-12345678-9', '0412-1234567', 'contacto@proveedorgeneral.com', 'Juan Pérez'),
('Distribuidora Nacional C.A.', 'J-87654321-0', '0414-7654321', 'ventas@distribuidoranacional.com', 'María González'),
('Importaciones Internacionales', 'J-11223344-5', '0424-9988776', 'info@importaciones.com', 'Carlos Rodríguez');

-- Insertar tasa de cambio inicial
INSERT INTO tasas_cambio (tasa_cambio, moneda_origen, moneda_destino, activa, observaciones) 
VALUES (36.50, 'USD', 'VES', TRUE, 'Tasa inicial del sistema');

-- Insertar algunos clientes por defecto
INSERT INTO clientes (nombre, tipo_documento, numero_documento, telefono, email, tipo_cliente) VALUES
('Cliente General', 'V', 'V-12345678', '0412-1112233', 'cliente@general.com', 'normal'),
('Empresa ABC C.A.', 'J', 'J-123456789', '0414-4445566', 'contacto@empresaabc.com', 'empresa'),
('Consumidor Final', 'V', 'V-87654321', '0426-7778899', NULL, 'normal');

-- Insertar productos de ejemplo
INSERT INTO productos (codigo_sku, nombre, descripcion, precio, precio_costo, stock_actual, stock_minimo, categoria_id, proveedor_id, activo) VALUES
('LAP-DEL-001', 'Laptop Dell Inspiron 15', 'Laptop Dell Inspiron 15, Intel Core i5, 8GB RAM, 256GB SSD', 850.00, 700.00, 10, 2, 2, 1, TRUE),
('MOUSE-LOG-001', 'Mouse Logitech M185', 'Mouse inalámbrico Logitech M185, color negro', 15.99, 10.50, 50, 10, 2, 1, TRUE),
('TECL-LOG-001', 'Teclado Logitech K120', 'Teclado USB Logitech K120, español', 19.99, 12.00, 30, 5, 2, 1, TRUE),
('MON-24-001', 'Monitor Samsung 24"', 'Monitor LED Samsung 24 pulgadas, Full HD', 199.99, 150.00, 15, 3, 1, 2, TRUE),
('CAF-COL-001', 'Café Colombia 500g', 'Café molido Colombia, paquete de 500 gramos', 8.99, 5.00, 100, 20, 5, 3, TRUE),
('REF-COC-001', 'Refrescola Coca-Cola 2L', 'Refrescola Coca-Cola, botella de 2 litros', 2.50, 1.50, 200, 50, 6, 3, TRUE),
('DET-LIM-001', 'Detergente Ariel 1kg', 'Detergente en polvo Ariel, paquete de 1kg', 6.99, 4.00, 80, 15, 7, 3, TRUE),
('CAM-POL-001', 'Camisa Polo Hombre', 'Camisa Polo para hombre, color azul, talla M', 25.00, 15.00, 40, 8, 8, 2, TRUE);

-- Actualizar precios en bolívares basados en la tasa de cambio
UPDATE productos 
SET precio_bs = ROUND(precio * 36.50, 2),
    precio_costo_bs = ROUND(precio_costo * 36.50, 2)
WHERE precio_bs = 0;

-- Insertar algunos productos con precio fijo en bolívares
INSERT INTO productos (codigo_sku, nombre, descripcion, precio, precio_bs, precio_costo, precio_costo_bs, usar_precio_fijo_bs, stock_actual, stock_minimo, categoria_id, activo) VALUES
('PAN-BIM-001', 'Pan Bimbo Grande', 'Pan de molde Bimbo, paquete grande', 2.50, 100.00, 1.80, 65.70, TRUE, 60, 10, 5, TRUE),
('LEC-PRO-001', 'Leche Protinal 1L', 'Leche en polvo Protinal, lata 1kg', 8.00, 300.00, 6.00, 219.00, TRUE, 45, 8, 5, TRUE);

-- FUNCIONES Y TRIGGERS

-- Función para actualizar timestamp de updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Triggers para actualizar updated_at
CREATE TRIGGER update_productos_updated_at 
    BEFORE UPDATE ON productos 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_categorias_updated_at 
    BEFORE UPDATE ON categorias 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_clientes_updated_at 
    BEFORE UPDATE ON clientes 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_usuarios_updated_at 
    BEFORE UPDATE ON usuarios 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_proveedores_updated_at 
    BEFORE UPDATE ON proveedores 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_tipos_pago_updated_at 
    BEFORE UPDATE ON tipos_pago 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Función para generar número de venta automático
CREATE OR REPLACE FUNCTION generar_numero_venta()
RETURNS TRIGGER AS $$
DECLARE
    year TEXT;
    month TEXT;
    day TEXT;
    sequence_num INTEGER;
    new_numero VARCHAR(50);
BEGIN
    -- Obtener fecha actual en componentes
    year := EXTRACT(YEAR FROM CURRENT_DATE);
    month := LPAD(EXTRACT(MONTH FROM CURRENT_DATE)::TEXT, 2, '0');
    day := LPAD(EXTRACT(DAY FROM CURRENT_DATE)::TEXT, 2, '0');
    
    -- Obtener el siguiente número de secuencia para el día
    SELECT COALESCE(MAX(SUBSTRING(numero_venta FROM 'V-\d{8}-(\d+)')::INTEGER), 0) + 1
    INTO sequence_num
    FROM ventas
    WHERE numero_venta LIKE 'V-' || year || month || day || '-%';
    
    -- Formatear número de venta
    new_numero := 'V-' || year || month || day || '-' || LPAD(sequence_num::TEXT, 4, '0');
    
    -- Asignar al nuevo registro
    NEW.numero_venta := new_numero;
    
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Trigger para generar número de venta automático
CREATE TRIGGER generar_numero_venta_trigger
    BEFORE INSERT ON ventas
    FOR EACH ROW
    EXECUTE FUNCTION generar_numero_venta();

-- Función para actualizar stock cuando se crea un detalle de venta
CREATE OR REPLACE FUNCTION actualizar_stock_venta()
RETURNS TRIGGER AS $$
BEGIN
    -- Actualizar stock del producto
    UPDATE productos 
    SET stock_actual = stock_actual - NEW.cantidad,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.producto_id;
    
    -- Registrar en historial de stock
    INSERT INTO historial_stock 
        (producto_id, cantidad_anterior, cantidad_nueva, diferencia, tipo_movimiento, referencia_id, referencia_tabla, observaciones)
    SELECT 
        p.id,
        p.stock_actual + NEW.cantidad,
        p.stock_actual,
        -NEW.cantidad,
        'venta',
        NEW.venta_id,
        'ventas',
        'Venta #' || (SELECT numero_venta FROM ventas WHERE id = NEW.venta_id) || ' - Producto: ' || p.nombre
    FROM productos p
    WHERE p.id = NEW.producto_id;
    
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Trigger para actualizar stock al crear detalle de venta
CREATE TRIGGER actualizar_stock_detalle_venta
    AFTER INSERT ON detalle_ventas
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_stock_venta();

-- Función para validar stock antes de vender
CREATE OR REPLACE FUNCTION validar_stock_venta()
RETURNS TRIGGER AS $$
DECLARE
    stock_disponible INTEGER;
BEGIN
    -- Obtener stock disponible
    SELECT stock_actual INTO stock_disponible
    FROM productos 
    WHERE id = NEW.producto_id;
    
    -- Validar stock
    IF stock_disponible < NEW.cantidad THEN
        RAISE EXCEPTION 'Stock insuficiente para el producto ID % (Stock disponible: %, Cantidad solicitada: %)', 
            NEW.producto_id, stock_disponible, NEW.cantidad;
    END IF;
    
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Trigger para validar stock antes de vender
CREATE TRIGGER validar_stock_detalle_venta
    BEFORE INSERT ON detalle_ventas
    FOR EACH ROW
    EXECUTE FUNCTION validar_stock_venta();

-- Función para actualizar totales de venta cuando se modifica el detalle
CREATE OR REPLACE FUNCTION actualizar_totales_venta()
RETURNS TRIGGER AS $$
BEGIN
    -- Actualizar subtotal y total de la venta
    UPDATE ventas v
    SET 
        subtotal = COALESCE((
            SELECT SUM(subtotal) 
            FROM detalle_ventas dv 
            WHERE dv.venta_id = COALESCE(NEW.venta_id, OLD.venta_id)
        ), 0),
        total = COALESCE((
            SELECT SUM(subtotal) 
            FROM detalle_ventas dv 
            WHERE dv.venta_id = COALESCE(NEW.venta_id, OLD.venta_id)
        ), 0) + COALESCE(v.iva, 0),
        total_bs = CASE 
            WHEN v.tasa_cambio IS NOT NULL THEN 
                (COALESCE((
                    SELECT SUM(subtotal) 
                    FROM detalle_ventas dv 
                    WHERE dv.venta_id = COALESCE(NEW.venta_id, OLD.venta_id)
                ), 0) + COALESCE(v.iva, 0)) * v.tasa_cambio
            ELSE v.total_bs
        END
    WHERE v.id = COALESCE(NEW.venta_id, OLD.venta_id);
    
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Trigger para actualizar totales de venta
CREATE TRIGGER actualizar_totales_detalle_venta
    AFTER INSERT OR UPDATE OR DELETE ON detalle_ventas
    FOR EACH ROW
    EXECUTE FUNCTION actualizar_totales_venta();

-- VISTAS ÚTILES

-- Vista para productos con información de categoría y proveedor
CREATE OR REPLACE VIEW vista_productos_completa AS
SELECT 
    p.*,
    c.nombre as categoria_nombre,
    c.activo as categoria_activa,
    pr.nombre as proveedor_nombre,
    pr.activo as proveedor_activo,
    CASE 
        WHEN p.stock_actual = 0 THEN 'sin_stock'
        WHEN p.stock_actual <= p.stock_minimo THEN 'bajo_stock'
        ELSE 'normal'
    END as estado_stock
FROM productos p
LEFT JOIN categorias c ON p.categoria_id = c.id
LEFT JOIN proveedores pr ON p.proveedor_id = pr.id;

-- Vista para ventas con información completa
CREATE OR REPLACE VIEW vista_ventas_completa AS
SELECT 
    v.*,
    c.nombre as cliente_nombre,
    c.numero_documento as cliente_documento,
    u.nombre as usuario_nombre,
    tp.nombre as tipo_pago_nombre,
    COUNT(dv.id) as total_items,
    SUM(dv.cantidad) as total_cantidad
FROM ventas v
LEFT JOIN clientes c ON v.cliente_id = c.id
LEFT JOIN usuarios u ON v.usuario_id = u.id
LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
LEFT JOIN detalle_ventas dv ON v.id = dv.venta_id
GROUP BY v.id, c.nombre, c.numero_documento, u.nombre, tp.nombre;

-- Vista para detalles de venta con información de productos
CREATE OR REPLACE VIEW vista_detalle_ventas_completo AS
SELECT 
    dv.*,
    v.numero_venta,
    v.fecha_hora,
    v.estado as venta_estado,
    p.codigo_sku,
    p.nombre as producto_nombre,
    p.descripcion as producto_descripcion,
    c.nombre as categoria_nombre
FROM detalle_ventas dv
JOIN ventas v ON dv.venta_id = v.id
JOIN productos p ON dv.producto_id = p.id
LEFT JOIN categorias c ON p.categoria_id = c.id;

-- Vista para productos bajo stock
CREATE OR REPLACE VIEW vista_productos_bajo_stock AS
SELECT 
    p.id,
    p.codigo_sku,
    p.nombre,
    p.descripcion,
    p.stock_actual,
    p.stock_minimo,
    p.precio,
    p.precio_bs,
    c.nombre as categoria_nombre,
    (p.stock_minimo - p.stock_actual) as diferencia,
    CASE 
        WHEN p.stock_actual = 0 THEN 'CRÍTICO'
        WHEN p.stock_actual <= (p.stock_minimo * 0.5) THEN 'ALTO'
        WHEN p.stock_actual <= p.stock_minimo THEN 'MEDIO'
        ELSE 'NORMAL'
    END as nivel_alerta
FROM productos p
LEFT JOIN categorias c ON p.categoria_id = c.id
WHERE p.stock_actual <= p.stock_minimo 
AND p.activo = TRUE
ORDER BY p.stock_actual ASC;

-- Vista para estadísticas de ventas por mes
CREATE OR REPLACE VIEW vista_estadisticas_ventas_mensual AS
SELECT 
    EXTRACT(YEAR FROM fecha_hora) as anio,
    EXTRACT(MONTH FROM fecha_hora) as mes,
    TO_CHAR(fecha_hora, 'Month') as nombre_mes,
    COUNT(*) as total_ventas,
    SUM(total) as total_ingresos_usd,
    SUM(total_bs) as total_ingresos_bs,
    AVG(total) as promedio_venta_usd,
    COUNT(DISTINCT cliente_id) as clientes_unicos
FROM ventas
WHERE estado = 'completada'
GROUP BY EXTRACT(YEAR FROM fecha_hora), EXTRACT(MONTH FROM fecha_hora), TO_CHAR(fecha_hora, 'Month')
ORDER BY anio DESC, mes DESC;

-- Vista para top 10 productos más vendidos
CREATE OR REPLACE VIEW vista_top_productos_vendidos AS
SELECT 
    p.id,
    p.codigo_sku,
    p.nombre,
    c.nombre as categoria_nombre,
    SUM(dv.cantidad) as total_vendido,
    SUM(dv.subtotal) as total_ingresos_usd,
    SUM(dv.subtotal_bs) as total_ingresos_bs,
    COUNT(DISTINCT dv.venta_id) as total_ventas
FROM productos p
LEFT JOIN detalle_ventas dv ON p.id = dv.producto_id
LEFT JOIN ventas v ON dv.venta_id = v.id
LEFT JOIN categorias c ON p.categoria_id = c.id
WHERE v.estado = 'completada' OR v.estado IS NULL
GROUP BY p.id, p.codigo_sku, p.nombre, c.nombre
ORDER BY total_vendido DESC, total_ingresos_usd DESC
LIMIT 10;

-- Vista para ventas por tipo de pago
CREATE OR REPlACE VIEW vista_ventas_por_tipo_pago AS
SELECT 
    tp.nombre as tipo_pago,
    COUNT(v.id) as total_ventas,
    SUM(v.total) as total_usd,
    SUM(v.total_bs) as total_bs,
    AVG(v.total) as promedio_usd,
    MIN(v.fecha_hora) as primera_venta,
    MAX(v.fecha_hora) as ultima_venta
FROM tipos_pago tp
LEFT JOIN ventas v ON tp.id = v.tipo_pago_id AND v.estado = 'completada'
GROUP BY tp.id, tp.nombre
ORDER BY total_usd DESC;

-- PROCEDIMIENTOS ALMACENADOS

-- Procedimiento para obtener reporte de ventas por rango de fechas
CREATE OR REPLACE PROCEDURE sp_reporte_ventas(
    fecha_inicio DATE,
    fecha_fin DATE
)
LANGUAGE plpgsql
AS $$
BEGIN
    -- Crear tabla temporal con resultados
    CREATE TEMP TABLE IF NOT EXISTS temp_reporte_ventas AS
    SELECT 
        v.numero_venta,
        v.fecha_hora,
        c.nombre as cliente,
        u.nombre as vendedor,
        tp.nombre as tipo_pago,
        v.total,
        v.total_bs,
        COUNT(dv.id) as items,
        STRING_AGG(p.nombre, ', ') as productos
    FROM ventas v
    LEFT JOIN clientes c ON v.cliente_id = c.id
    LEFT JOIN usuarios u ON v.usuario_id = u.id
    LEFT JOIN tipos_pago tp ON v.tipo_pago_id = tp.id
    LEFT JOIN detalle_ventas dv ON v.id = dv.venta_id
    LEFT JOIN productos p ON dv.producto_id = p.id
    WHERE v.fecha_hora::DATE BETWEEN fecha_inicio AND fecha_fin
    AND v.estado = 'completada'
    GROUP BY v.id, c.nombre, u.nombre, tp.nombre;
    
    -- Retornar resultados
    SELECT * FROM temp_reporte_ventas ORDER BY fecha_hora DESC;
    
    -- Limpiar tabla temporal
    DROP TABLE IF EXISTS temp_reporte_ventas;
END;
$$;

-- Procedimiento para generar alertas de inventario (VERSIÓN CORREGIDA)
CREATE OR REPLACE PROCEDURE sp_generar_alertas_inventario()
LANGUAGE plpgsql
AS $$
DECLARE
    prod_record RECORD;
    sin_stock_count INTEGER;
    bajo_stock_count INTEGER;
BEGIN
    -- Contar productos sin stock
    SELECT COUNT(*) INTO sin_stock_count
    FROM productos 
    WHERE stock_actual = 0 AND activo = TRUE;
    
    -- Contar productos con stock bajo
    SELECT COUNT(*) INTO bajo_stock_count
    FROM productos 
    WHERE stock_actual > 0 
    AND stock_actual <= stock_minimo 
    AND activo = TRUE;
    
    RAISE NOTICE '=== ALERTAS DE INVENTARIO ===';
    RAISE NOTICE '';
    RAISE NOTICE 'PRODUCTOS SIN STOCK:';
    
    -- Bucle corregido usando FOR IN SELECT
    FOR prod_record IN 
        SELECT codigo_sku, nombre, stock_actual 
        FROM productos 
        WHERE stock_actual = 0 AND activo = TRUE
        ORDER BY nombre
    LOOP
        RAISE NOTICE '  - % (%): Stock agotado', prod_record.nombre, prod_record.codigo_sku;
    END LOOP;
    
    RAISE NOTICE '';
    RAISE NOTICE 'PRODUCTOS CON STOCK BAJO:';
    
    FOR prod_record IN 
        SELECT codigo_sku, nombre, stock_actual, stock_minimo 
        FROM productos 
        WHERE stock_actual > 0 
        AND stock_actual <= stock_minimo 
        AND activo = TRUE
        ORDER BY stock_actual
    LOOP
        RAISE NOTICE '  - % (%): Stock: % (Mínimo: %)', 
            prod_record.nombre, prod_record.codigo_sku, 
            prod_record.stock_actual, prod_record.stock_minimo;
    END LOOP;
    
    RAISE NOTICE '';
    RAISE NOTICE 'TOTAL ALERTAS:';
    RAISE NOTICE '  - Sin stock: %', sin_stock_count;
    RAISE NOTICE '  - Stock bajo: %', bajo_stock_count;
END;
$$;

-- Función para buscar productos por término de búsqueda
CREATE OR REPLACE FUNCTION buscar_productos(
    termino_busqueda VARCHAR
)
RETURNS TABLE(
    id INTEGER,
    codigo_sku VARCHAR,
    nombre VARCHAR,
    descripcion TEXT,
    precio DECIMAL(10,2),
    precio_bs DECIMAL(10,2),
    stock_actual INTEGER,
    categoria_nombre VARCHAR,
    estado_stock VARCHAR
) 
LANGUAGE plpgsql
AS $$
BEGIN
    RETURN QUERY
    SELECT 
        p.id,
        p.codigo_sku,
        p.nombre,
        p.descripcion,
        p.precio,
        p.precio_bs,
        p.stock_actual,
        COALESCE(c.nombre, 'Sin categoría') as categoria_nombre,
        CASE 
            WHEN p.stock_actual = 0 THEN 'Sin stock'
            WHEN p.stock_actual <= p.stock_minimo THEN 'Bajo stock'
            ELSE 'Disponible'
        END as estado_stock
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE 
        p.activo = TRUE AND (
            p.codigo_sku ILIKE '%' || termino_busqueda || '%' OR
            p.nombre ILIKE '%' || termino_busqueda || '%' OR
            p.descripcion ILIKE '%' || termino_busqueda || '%' OR
            c.nombre ILIKE '%' || termino_busqueda || '%'
        )
    ORDER BY 
        CASE 
            WHEN p.stock_actual = 0 THEN 3
            WHEN p.stock_actual <= p.stock_minimo THEN 2
            ELSE 1
        END,
        p.nombre;
END;
$$;

-- MENSAJE FINAL
DO $$
DECLARE
    total_tablas INTEGER;
    total_registros INTEGER;
BEGIN
    -- Contar tablas creadas
    SELECT COUNT(*) INTO total_tablas
    FROM information_schema.tables 
    WHERE table_schema = 'public' 
    AND table_type = 'BASE TABLE';
    
    -- Contar registros insertados
    SELECT 
        (SELECT COUNT(*) FROM usuarios) +
        (SELECT COUNT(*) FROM categorias) +
        (SELECT COUNT(*) FROM proveedores) +
        (SELECT COUNT(*) FROM tipos_pago) +
        (SELECT COUNT(*) FROM clientes) +
        (SELECT COUNT(*) FROM productos) +
        (SELECT COUNT(*) FROM tasas_cambio)
    INTO total_registros;
    
    RAISE NOTICE '=========================================';
    RAISE NOTICE 'BASE DE DATOS CREADA EXITOSAMENTE!';
    RAISE NOTICE '=========================================';
    RAISE NOTICE '';
    RAISE NOTICE '📊 ESTADÍSTICAS DE LA INSTALACIÓN:';
    RAISE NOTICE '   Tablas creadas: %', total_tablas;
    RAISE NOTICE '   Vistas creadas: 9';
    RAISE NOTICE '   Funciones creadas: 6';
    RAISE NOTICE '   Triggers creados: 8';
    RAISE NOTICE '   Registros insertados: %', total_registros;
    RAISE NOTICE '';
    RAISE NOTICE '👤 USUARIO POR DEFECTO:';
    RAISE NOTICE '   Usuario: admin';
    RAISE NOTICE '   Password: admin123';
    RAISE NOTICE '   (Cambiar inmediatamente después del primer login)';
    RAISE NOTICE '';
    RAISE NOTICE '💳 TIPOS DE PAGO DISPONIBLES:';
    RAISE NOTICE '   - Efectivo';
    RAISE NOTICE '   - Transferencia';
    RAISE NOTICE '   - Pago Móvil';
    RAISE NOTICE '   - Tarjeta de Débito/Crédito';
    RAISE NOTICE '   - Mixto';
    RAISE NOTICE '   - Crédito';
    RAISE NOTICE '';
    RAISE NOTICE '📦 DATOS DE EJEMPLO INCLUIDOS:';
    RAISE NOTICE '   - 10 categorías';
    RAISE NOTICE '   - 3 proveedores';
    RAISE NOTICE '   - 10 productos';
    RAISE NOTICE '   - 3 clientes';
    RAISE NOTICE '   - Tasa de cambio inicial: 36.50 Bs/USD';
    RAISE NOTICE '';
    RAISE NOTICE '🚀 FUNCIONALIDADES INCLUIDAS:';
    RAISE NOTICE '   ✅ Validación de stock en tiempo real';
    RAISE NOTICE '   ✅ Números de venta automáticos';
    RAISE NOTICE '   ✅ Cálculo automático de totales';
    RAISE NOTICE '   ✅ Historial completo de movimientos';
    RAISE NOTICE '   ✅ Sistema dual USD/BS';
    RAISE NOTICE '   ✅ Precios fijos en bolívares';
    RAISE NOTICE '   ✅ Alertas de stock bajo';
    RAISE NOTICE '   ✅ Reportes integrados';
    RAISE NOTICE '   ✅ Búsqueda avanzada de productos';
    RAISE NOTICE '';
    RAISE NOTICE '📋 COMANDOS ÚTILES:';
    RAISE NOTICE '   -- Alertas de inventario';
    RAISE NOTICE '   CALL sp_generar_alertas_inventario();';
    RAISE NOTICE '';
    RAISE NOTICE '   -- Reporte de ventas';
    RAISE NOTICE '   CALL sp_reporte_ventas(''2024-01-01'', ''2024-12-31'');';
    RAISE NOTICE '';
    RAISE NOTICE '   -- Buscar productos';
    RAISE NOTICE '   SELECT * FROM buscar_productos(''laptop'');';
    RAISE NOTICE '';
    RAISE NOTICE '   -- Ver productos bajo stock';
    RAISE NOTICE '   SELECT * FROM vista_productos_bajo_stock;';
    RAISE NOTICE '';
    RAISE NOTICE '   -- Ver ventas recientes';
    RAISE NOTICE '   SELECT * FROM vista_ventas_completa ORDER BY fecha_hora DESC LIMIT 10;';
    RAISE NOTICE '';
    RAISE NOTICE '=========================================';
END $$;