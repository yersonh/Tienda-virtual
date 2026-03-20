CREATE TABLE public.persona (
    id_persona SERIAL PRIMARY KEY,
    nombres VARCHAR(64) NOT NULL,
    apellidos VARCHAR(64) NOT NULL,
    cc VARCHAR(20) UNIQUE NOT NULL,
    correo VARCHAR(128),
    telefono VARCHAR(16),
    direccion VARCHAR(256),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE public.tipo_usuario (
    id_tipo SERIAL PRIMARY KEY,
    nombre VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO public.tipo_usuario (nombre)
VALUES 
('Admin'),
('Vendedor'),
('ClienteTiendaV');

CREATE TABLE public.usuario (
    id_usuario SERIAL PRIMARY KEY,
    id_persona INTEGER NOT NULL,
    id_tipo INTEGER NOT NULL,
    username VARCHAR(24) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    estado VARCHAR(16) DEFAULT 'Activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_persona
        FOREIGN KEY (id_persona)
        REFERENCES public.persona(id_persona),
    CONSTRAINT fk_usuario_tipo
        FOREIGN KEY (id_tipo)
        REFERENCES public.tipo_usuario(id_tipo)
);
CREATE TABLE public.proveedor (
    id_proveedor SERIAL PRIMARY KEY,
    nombre VARCHAR(64) NOT NULL,
    rut_p VARCHAR(64) UNIQUE,
    direccion VARCHAR(256),
    telefono VARCHAR(16),
    correo VARCHAR(128),
    estado VARCHAR(16) DEFAULT 'Activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE public.producto (
    id_producto SERIAL PRIMARY KEY,
    nombre VARCHAR(64) NOT NULL,
    codigo INTEGER UNIQUE NOT NULL,
    descripcion VARCHAR(256),
    precio NUMERIC(10,2) NOT NULL CHECK (precio >= 0),
    stock_p INTEGER NOT NULL DEFAULT 0 CHECK (stock_p >= 0),
    estado VARCHAR(20) NOT NULL DEFAULT 'Activo' CHECK (estado IN ('Activo', 'Inactivo', 'Stock bajo', 'Agotado')),
    id_categoria INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key
    CONSTRAINT fk_producto_categoria 
        FOREIGN KEY (id_categoria) 
        REFERENCES public.categoria_producto(id_categoria)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);

-- Comentarios para documentación
COMMENT ON TABLE public.producto IS 'Catálogo de productos';
COMMENT ON COLUMN public.producto.id_producto IS 'Identificador único del producto';
COMMENT ON COLUMN public.producto.nombre IS 'Nombre del producto (máx 64 caracteres)';
COMMENT ON COLUMN public.producto.codigo IS 'Código único del producto';
COMMENT ON COLUMN public.producto.descripcion IS 'Descripción detallada del producto';
COMMENT ON COLUMN public.producto.precio IS 'Precio del producto (máx 10 dígitos, 2 decimales)';
COMMENT ON COLUMN public.producto.stock_p IS 'Cantidad en inventario';
COMMENT ON COLUMN public.producto.estado IS 'Estado del producto: Activo, Inactivo, Stock bajo, Agotado';
COMMENT ON COLUMN public.producto.id_categoria IS 'Categoría a la que pertenece el producto';

-- Trigger para actualizar updated_at automáticamente
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_producto_updated_at
    BEFORE UPDATE ON public.producto
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Índices para mejorar rendimiento
CREATE INDEX idx_producto_nombre ON public.producto(nombre);
CREATE INDEX idx_producto_codigo ON public.producto(codigo);
CREATE INDEX idx_producto_id_categoria ON public.producto(id_categoria);
CREATE INDEX idx_producto_estado ON public.producto(estado);

-- Ejemplo de inserción
INSERT INTO public.producto (nombre, codigo, descripcion, precio, stock_p, estado, id_categoria) VALUES
('Laptop Dell XPS 15', 1001, 'Laptop de alta gama con procesador Intel i7', 3200000.00, 12, 'Activo', 1),
('Router Cisco 3900', 1002, 'Router empresarial con VPN', 850000.00, 3, 'Stock bajo', 2),
('Windows 11 Pro', 1003, 'Licencia digital de Windows 11 Pro', 420000.00, 999, 'Activo', 3);

CREATE OR REPLACE VIEW public.vista_inventario AS
SELECT 
    p.id_producto,
    p.codigo,
    p.nombre,
    c.nombre AS categoria,
    p.stock_p,
    CASE 
        WHEN p.stock_p = 0 THEN 'Agotado'
        WHEN p.stock_p < 5 THEN 'Stock bajo'
        ELSE 'Con existencias'
    END AS nivel_stock,
    CASE 
        WHEN p.stock_p = 0 THEN true
        WHEN p.stock_p < 5 THEN true
        ELSE false
    END AS alerta_stock,
    CASE 
        WHEN p.stock_p = 0 THEN 'rojo'
        WHEN p.stock_p < 5 THEN 'amarillo'
        ELSE 'verde'
    END AS color_alerta
FROM public.producto p
LEFT JOIN public.categoria_producto c ON c.id_categoria = p.id_categoria
WHERE p.estado = true  -- Solo productos activos (cambié 'activo' por 'estado')
ORDER BY 
    CASE 
        WHEN p.stock_p = 0 THEN 1
        WHEN p.stock_p < 5 THEN 2
        ELSE 3
    END,
    p.nombre;

-- Comentarios
COMMENT ON VIEW public.vista_inventario IS 'Vista de inventario con niveles de stock calculados automáticamente';
COMMENT ON COLUMN public.vista_inventario.nivel_stock IS 'Nivel de stock: Agotado, Stock bajo, Con existencias';
COMMENT ON COLUMN public.vista_inventario.alerta_stock IS 'True si el producto requiere atención (stock bajo o agotado)';
COMMENT ON COLUMN public.vista_inventario.color_alerta IS 'Color sugerido para UI: rojo, amarillo, verde';

-- Ver todos los productos en inventario (solo activos)
SELECT * FROM public.vista_inventario;

-- Ver productos con alerta
SELECT * FROM public.vista_inventario WHERE alerta_stock = true;

-- Ver estadísticas
SELECT nivel_stock, COUNT(*) 
FROM public.vista_inventario 
GROUP BY nivel_stock;