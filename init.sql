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