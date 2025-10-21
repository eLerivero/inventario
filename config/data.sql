-- Ejecutar en PostgreSQL
CREATE DATABASE sistema_inventario;

\c sistema_inventario;

-- Extensión para UUID (opcional)
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Tabla CATEGORIA
CREATE TABLE categorias (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla TIPO_PAGO
CREATE TABLE tipos_pago (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla CLIENTE
CREATE TABLE clientes (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telefono VARCHAR(20),
    direccion TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla PRODUCTO
CREATE TABLE productos (
    id SERIAL PRIMARY KEY,
    codigo_sku VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL CHECK (precio >= 0),
    precio_costo DECIMAL(10,2) CHECK (precio_costo >= 0),
    stock_actual INTEGER DEFAULT 0 CHECK (stock_actual >= 0),
    stock_minimo INTEGER DEFAULT 5 CHECK (stock_minimo >= 0),
    categoria_id INTEGER,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- Tabla VENTA
CREATE TABLE ventas (
    id SERIAL PRIMARY KEY,
    numero_venta VARCHAR(20) UNIQUE NOT NULL,
    cliente_id INTEGER,
    total DECIMAL(10,2) NOT NULL CHECK (total >= 0),
    tipo_pago_id INTEGER NOT NULL,
    estado VARCHAR(20) CHECK (estado IN ('pendiente', 'completada', 'cancelada')) DEFAULT 'completada',
    fecha_hora TIMESTAMP NOT NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,
    FOREIGN KEY (tipo_pago_id) REFERENCES tipos_pago(id)
);

-- Tabla DETALLE_VENTA
CREATE TABLE detalle_ventas (
    id SERIAL PRIMARY KEY,
    venta_id INTEGER NOT NULL,
    producto_id INTEGER NOT NULL,
    cantidad INTEGER NOT NULL CHECK (cantidad > 0),
    precio_unitario DECIMAL(10,2) NOT NULL CHECK (precio_unitario >= 0),
    subtotal DECIMAL(10,2) NOT NULL CHECK (subtotal >= 0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id)
);

-- Tabla HISTORIAL_STOCK
CREATE TABLE historial_stock (
    id SERIAL PRIMARY KEY,
    producto_id INTEGER NOT NULL,
    cantidad_anterior INTEGER NOT NULL,
    cantidad_nueva INTEGER NOT NULL,
    diferencia INTEGER NOT NULL,
    tipo_movimiento VARCHAR(20) CHECK (tipo_movimiento IN ('entrada', 'salida', 'ajuste', 'venta', 'compra')),
    referencia_id INTEGER,
    tipo_referencia VARCHAR(20) CHECK (tipo_referencia IN ('venta', 'compra', 'ajuste_manual')),
    observaciones TEXT,
    usuario VARCHAR(50) DEFAULT 'sistema',
    fecha_hora TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
);

-- Índices para mejor performance
CREATE INDEX idx_productos_sku ON productos(codigo_sku);
CREATE INDEX idx_productos_activo ON productos(activo);
CREATE INDEX idx_ventas_fecha ON ventas(fecha_hora);
CREATE INDEX idx_ventas_cliente ON ventas(cliente_id);
CREATE INDEX idx_detalle_venta_producto ON detalle_ventas(producto_id);
CREATE INDEX idx_historial_stock_producto ON historial_stock(producto_id);
CREATE INDEX idx_historial_stock_fecha ON historial_stock(fecha_hora);

-- Función para actualizar updated_at (PostgreSQL)
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Triggers para updated_at
CREATE TRIGGER update_categorias_updated_at BEFORE UPDATE ON categorias FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_clientes_updated_at BEFORE UPDATE ON clientes FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_productos_updated_at BEFORE UPDATE ON productos FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Función para generar número de venta automático
CREATE OR REPLACE FUNCTION generar_numero_venta()
RETURNS TRIGGER AS $$
DECLARE
    ultimo_numero INTEGER;
    nuevo_numero VARCHAR(20);
BEGIN
    SELECT COALESCE(MAX(CAST(SUBSTRING(numero_venta FROM 'V-(\d+)-(\d+)') AS INTEGER)), 0) 
    INTO ultimo_numero 
    FROM ventas 
    WHERE DATE_PART('year', created_at) = DATE_PART('year', CURRENT_DATE);
    
    nuevo_numero := 'V-' || DATE_PART('year', CURRENT_DATE) || '-' || LPAD((ultimo_numero + 1)::TEXT, 4, '0');
    
    NEW.numero_venta := nuevo_numero;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_generar_numero_venta 
BEFORE INSERT ON ventas 
FOR EACH ROW 
EXECUTE FUNCTION generar_numero_venta();

-- Datos iniciales
INSERT INTO categorias (nombre, descripcion) VALUES 
('Electrónicos', 'Productos electrónicos y tecnología'),
('Ropa', 'Prendas de vestir'),
('Hogar', 'Artículos para el hogar'),
('Deportes', 'Artículos deportivos'),
('Libros', 'Libros y material educativo');

INSERT INTO tipos_pago (nombre, descripcion) VALUES 
('Efectivo', 'Pago en efectivo'),
('Tarjeta Crédito', 'Pago con tarjeta de crédito'),
('Tarjeta Débito', 'Pago con tarjeta de débito'),
('Transferencia', 'Transferencia bancaria'),
('Cheque', 'Pago con cheque');

INSERT INTO clientes (nombre, email, telefono, direccion) VALUES 
('Cliente General', 'general@cliente.com', '0000-0000', 'Dirección general'),
('Juan Pérez', 'juan@email.com', '1234-5678', 'Calle 123, Ciudad'),
('María García', 'maria@email.com', '8765-4321', 'Avenida 456, Ciudad');

INSERT INTO productos (codigo_sku, nombre, descripcion, precio, precio_costo, stock_actual, stock_minimo, categoria_id) VALUES 
('SKU-001', 'Laptop HP 15"', 'Laptop HP 15 pulgadas, 8GB RAM, 256GB SSD', 899.99, 650.00, 15, 3, 1),
('SKU-002', 'Smartphone Samsung', 'Teléfono inteligente Android, 128GB', 499.99, 350.00, 25, 5, 1),
('SKU-003', 'Camiseta Básica', 'Camiseta de algodón 100%', 19.99, 8.50, 100, 20, 2),
('SKU-004', 'Silla Oficina', 'Silla ergonómica para oficina', 199.99, 120.00, 8, 2, 3),
('SKU-005', 'Balón Fútbol', 'Balón oficial tamaño 5', 29.99, 15.00, 30, 10, 4);