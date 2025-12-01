-- Crear base de datos
CREATE DATABASE inventario;
\c inventario;

-- Tabla de categorías
CREATE TABLE categorias (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de tipos de pago
CREATE TABLE tipos_pago (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de clientes
CREATE TABLE clientes (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    documento_identidad VARCHAR(35),
    email VARCHAR(100),
    telefono VARCHAR(20),
    direccion TEXT,
    activo BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de productos
CREATE TABLE productos (
    id SERIAL PRIMARY KEY,
    codigo_sku VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL CHECK (precio >= 0),
    precio_bs DECIMAL(10,2) DEFAULT 0 CHECK (precio_bs >= 0),
    precio_costo DECIMAL(10,2) DEFAULT 0 CHECK (precio_costo >= 0),
    precio_costo_bs DECIMAL(10,2) DEFAULT 0 CHECK (precio_costo_bs >= 0),
    moneda_base VARCHAR(3) DEFAULT 'USD',
    stock_actual INTEGER DEFAULT 0 CHECK (stock_actual >= 0),
    stock_minimo INTEGER DEFAULT 5 CHECK (stock_minimo >= 0),
    categoria_id INTEGER REFERENCES categorias(id),
    activo BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de tasas de cambio
CREATE TABLE tasas_cambio (
    id SERIAL PRIMARY KEY,
    moneda_origen VARCHAR(3) DEFAULT 'USD',
    moneda_destino VARCHAR(3) DEFAULT 'VES',
    tasa_cambio DECIMAL(10,4) NOT NULL CHECK (tasa_cambio > 0),
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activa BOOLEAN DEFAULT TRUE,
    usuario_actualizacion VARCHAR(100) DEFAULT 'Sistema',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de ventas
CREATE TABLE ventas (
    id SERIAL PRIMARY KEY,
    numero_venta SERIAL UNIQUE,
    cliente_id INTEGER REFERENCES clientes(id),
    total DECIMAL(10,2) NOT NULL CHECK (total >= 0),
    total_bs DECIMAL(10,2) DEFAULT 0 CHECK (total_bs >= 0),
    tasa_cambio_utilizada DECIMAL(10,4) DEFAULT 1,
    tipo_pago_id INTEGER REFERENCES tipos_pago(id),
    estado VARCHAR(20) DEFAULT 'pendiente' CHECK (estado IN ('pendiente', 'completada', 'cancelada')),
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de detalles de venta
CREATE TABLE detalle_ventas (
    id SERIAL PRIMARY KEY,
    venta_id INTEGER REFERENCES ventas(id) ON DELETE CASCADE,
    producto_id INTEGER REFERENCES productos(id),
    cantidad INTEGER NOT NULL CHECK (cantidad > 0),
    precio_unitario DECIMAL(10,2) NOT NULL CHECK (precio_unitario >= 0),
    precio_unitario_bs DECIMAL(10,2) DEFAULT 0 CHECK (precio_unitario_bs >= 0),
    subtotal DECIMAL(10,2) NOT NULL CHECK (subtotal >= 0),
    subtotal_bs DECIMAL(10,2) DEFAULT 0 CHECK (subtotal_bs >= 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de historial de stock
CREATE TABLE historial_stock (
    id SERIAL PRIMARY KEY,
    producto_id INTEGER NOT NULL REFERENCES productos(id) ON DELETE CASCADE,
    cantidad_anterior INTEGER NOT NULL DEFAULT 0,
    cantidad_nueva INTEGER NOT NULL DEFAULT 0,
    diferencia INTEGER NOT NULL DEFAULT 0,
    tipo_movimiento VARCHAR(20) NOT NULL CHECK (tipo_movimiento IN ('entrada', 'salida', 'ajuste', 'sin_cambio')),
    referencia_id INTEGER NULL,
    tipo_referencia VARCHAR(50) NULL,
    observaciones TEXT,
    usuario VARCHAR(100) NOT NULL DEFAULT 'Sistema',
    fecha_hora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices para mejorar el rendimiento
CREATE INDEX idx_productos_categoria ON productos(categoria_id);
CREATE INDEX idx_productos_activo ON productos(activo);
CREATE INDEX idx_ventas_cliente ON ventas(cliente_id);
CREATE INDEX idx_ventas_estado ON ventas(estado);
CREATE INDEX idx_ventas_fecha ON ventas(fecha_hora);
CREATE INDEX idx_detalle_ventas_venta ON detalle_ventas(venta_id);
CREATE INDEX idx_detalle_ventas_producto ON detalle_ventas(producto_id);
CREATE INDEX idx_historial_stock_producto ON historial_stock(producto_id);
CREATE INDEX idx_historial_stock_fecha ON historial_stock(fecha_hora);
CREATE INDEX idx_tasas_cambio_activa ON tasas_cambio(activa);
CREATE INDEX idx_tasas_cambio_fecha ON tasas_cambio(fecha_actualizacion);

-- Datos iniciales
INSERT INTO categorias (nombre, descripcion) VALUES 
('Panadería', 'Productos de panadería y pastelería'),
('Bebidas', 'Bebidas alcoholicas y no alcoholicas'),
('Lácteos', 'Productos lácteos y derivados'),
('Abarrotes', 'Productos de abarrotes y despensa');

INSERT INTO tipos_pago (nombre, descripcion) VALUES 
('Efectivo', 'Pago en efectivo'),
('Tarjeta de Crédito', 'Pago con tarjeta de crédito'),
('Tarjeta de Débito', 'Pago con tarjeta de débito'),
('Transferencia', 'Transferencia bancaria'),
('Pago Móvil', 'Pago a través de aplicaciones móviles');

INSERT INTO clientes (nombre, email, telefono, direccion, documento_identidad) VALUES 
('Cliente General', 'cliente@general.com', '0000-0000', 'Dirección general', 'V-00000000'),
('Juan Pérez', 'juan@email.com', '1234-5678', 'Ciudad, Zona 1', 'V-12345678'),
('María García', 'maria@email.com', '2345-6789', 'Ciudad, Zona 2', 'V-23456789'),
('Carlos López', 'carlos@email.com', '3456-7890', 'Ciudad, Zona 3', 'V-34567890');

-- Insertar tasa inicial
INSERT INTO tasas_cambio (moneda_origen, moneda_destino, tasa_cambio, usuario_actualizacion) 
VALUES ('USD', 'VES', 36.50, 'Sistema');

-- Insertar productos de ejemplo con precios en USD y BS
INSERT INTO productos (codigo_sku, nombre, descripcion, precio, precio_bs, precio_costo, precio_costo_bs, categoria_id, stock_actual) VALUES 
('PAN-001', 'Pan Francés', 'Pan francés fresco del día', 1.50, 54.75, 0.75, 27.38, 1, 50),
('PAN-002', 'Croissant', 'Croissant de mantequilla', 2.00, 73.00, 1.00, 36.50, 1, 30),
('BEB-001', 'Cerveza Polar', 'Cerveza polar 350ml', 1.20, 43.80, 0.60, 21.90, 2, 100),
('BEB-002', 'Agua Mineral', 'Agua mineral 500ml', 0.80, 29.20, 0.40, 14.60, 2, 80),
('LAC-001', 'Leche Entera', 'Leche entera 1 litro', 1.80, 65.70, 1.20, 43.80, 3, 40),
('ABA-001', 'Arroz', 'Arroz blanco 1kg', 1.50, 54.75, 1.00, 36.50, 4, 60);

-- Trigger para actualizar updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_categorias_updated_at BEFORE UPDATE ON categorias FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_clientes_updated_at BEFORE UPDATE ON clientes FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_productos_updated_at BEFORE UPDATE ON productos FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_tasas_cambio_updated_at BEFORE UPDATE ON tasas_cambio FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_tipos_pago_updated_at BEFORE UPDATE ON tipos_pago FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Función para actualizar precios en BS automáticamente cuando cambia la tasa
CREATE OR REPLACE FUNCTION actualizar_precios_bs()
RETURNS TRIGGER AS $$
BEGIN
    -- Si es una nueva tasa activa, actualizar todos los precios en BS
    IF NEW.activa = TRUE AND (OLD.activa = FALSE OR OLD IS NULL) THEN
        UPDATE productos 
        SET precio_bs = ROUND(precio * NEW.tasa_cambio, 2),
            precio_costo_bs = ROUND(precio_costo * NEW.tasa_cambio, 2),
            updated_at = CURRENT_TIMESTAMP;
    END IF;
    
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER actualizar_precios_bs_trigger 
    AFTER INSERT OR UPDATE ON tasas_cambio 
    FOR EACH ROW EXECUTE FUNCTION actualizar_precios_bs();

-- Función para calcular subtotales automáticamente en detalles de venta
CREATE OR REPLACE FUNCTION calcular_subtotales_venta()
RETURNS TRIGGER AS $$
DECLARE
    tasa_actual DECIMAL(10,4);
BEGIN
    -- Obtener la tasa de cambio actual
    SELECT tasa_cambio INTO tasa_actual 
    FROM tasas_cambio 
    WHERE activa = TRUE 
    ORDER BY fecha_actualizacion DESC 
    LIMIT 1;
    
    -- Calcular subtotales
    NEW.subtotal = NEW.cantidad * NEW.precio_unitario;
    NEW.precio_unitario_bs = ROUND(NEW.precio_unitario * tasa_actual, 2);
    NEW.subtotal_bs = ROUND(NEW.subtotal * tasa_actual, 2);
    
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER calcular_subtotales_venta_trigger 
    BEFORE INSERT OR UPDATE ON detalle_ventas 
    FOR EACH ROW EXECUTE FUNCTION calcular_subtotales_venta();

-- Función para actualizar el total de la venta
CREATE OR REPLACE FUNCTION actualizar_total_venta()
RETURNS TRIGGER AS $$
DECLARE
    total_venta DECIMAL(10,2);
    total_venta_bs DECIMAL(10,2);
    tasa_actual DECIMAL(10,4);
BEGIN
    -- Calcular totales de la venta
    SELECT SUM(subtotal), SUM(subtotal_bs) INTO total_venta, total_venta_bs
    FROM detalle_ventas 
    WHERE venta_id = NEW.venta_id;
    
    -- Obtener tasa actual
    SELECT tasa_cambio INTO tasa_actual 
    FROM tasas_cambio 
    WHERE activa = TRUE 
    ORDER BY fecha_actualizacion DESC 
    LIMIT 1;
    
    -- Actualizar la venta
    UPDATE ventas 
    SET total = COALESCE(total_venta, 0),
        total_bs = COALESCE(total_venta_bs, 0),
        tasa_cambio_utilizada = tasa_actual
    WHERE id = NEW.venta_id;
    
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER actualizar_total_venta_trigger 
    AFTER INSERT OR UPDATE OR DELETE ON detalle_ventas 
    FOR EACH ROW EXECUTE FUNCTION actualizar_total_venta();

-- Función para actualizar stock automáticamente
CREATE OR REPLACE FUNCTION actualizar_stock_producto()
RETURNS TRIGGER AS $$
BEGIN
    -- Actualizar stock del producto cuando se complete una venta
    IF TG_OP = 'UPDATE' AND NEW.estado = 'completada' AND OLD.estado != 'completada' THEN
        UPDATE productos p
        SET stock_actual = stock_actual - dv.cantidad,
            updated_at = CURRENT_TIMESTAMP
        FROM detalle_ventas dv
        WHERE dv.venta_id = NEW.id AND p.id = dv.producto_id;
        
        -- Registrar en historial de stock
        INSERT INTO historial_stock (producto_id, cantidad_anterior, cantidad_nueva, diferencia, tipo_movimiento, referencia_id, tipo_referencia, observaciones)
        SELECT 
            dv.producto_id,
            p.stock_actual as cantidad_anterior,
            p.stock_actual - dv.cantidad as cantidad_nueva,
            -dv.cantidad as diferencia,
            'salida' as tipo_movimiento,
            NEW.id as referencia_id,
            'venta' as tipo_referencia,
            'Venta completada #' || NEW.numero_venta as observaciones
        FROM detalle_ventas dv
        JOIN productos p ON p.id = dv.producto_id
        WHERE dv.venta_id = NEW.id;
    END IF;
    
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER actualizar_stock_producto_trigger 
    AFTER UPDATE ON ventas 
    FOR EACH ROW EXECUTE FUNCTION actualizar_stock_producto();