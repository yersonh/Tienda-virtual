-- Objetos faltantes sugeridos segun el inventario actual de Oracle Cloud.
-- Ejecutar por bloques en SQL Developer.
-- Este script NO reemplaza los objetos que ya tienes; agrega procedimientos,
-- funciones, vistas y una secuencia posible que faltan para completar CRUD.

--------------------------------------------------------------------------------
-- 1. SECUENCIA POSIBLE FALTANTE: PRODUCTO_IMAGEN
--------------------------------------------------------------------------------

DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_count
    FROM USER_SEQUENCES
    WHERE SEQUENCE_NAME = 'SEQ_PRODUCTO_IMAGEN';

    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'CREATE SEQUENCE SEQ_PRODUCTO_IMAGEN START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE';
    END IF;
END;
/

CREATE OR REPLACE TRIGGER TRG_PRODUCTO_IMAGEN_ID
BEFORE INSERT ON PRODUCTO_IMAGEN
FOR EACH ROW
BEGIN
    IF :NEW.ID_IMAGEN IS NULL THEN
        SELECT SEQ_PRODUCTO_IMAGEN.NEXTVAL
        INTO :NEW.ID_IMAGEN
        FROM DUAL;
    END IF;

    :NEW.ORDEN := NVL(:NEW.ORDEN, 1);
END;
/

--------------------------------------------------------------------------------
-- 2. FUNCIONES FALTANTES DE VALIDACION
--------------------------------------------------------------------------------

CREATE OR REPLACE FUNCTION FN_USUARIO_EXISTE (
    p_username IN VARCHAR2
) RETURN NUMBER
AS
    v_total NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_total
    FROM USUARIO
    WHERE LOWER(TRIM(USERNAME)) = LOWER(TRIM(p_username));

    RETURN CASE WHEN v_total > 0 THEN 1 ELSE 0 END;
END;
/

CREATE OR REPLACE FUNCTION FN_CORREO_EXISTE (
    p_correo IN VARCHAR2,
    p_id_persona_excluir IN NUMBER DEFAULT NULL
) RETURN NUMBER
AS
    v_total NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_total
    FROM PERSONA
    WHERE LOWER(TRIM(CORREO)) = LOWER(TRIM(p_correo))
      AND (p_id_persona_excluir IS NULL OR ID_PERSONA <> p_id_persona_excluir);

    RETURN CASE WHEN v_total > 0 THEN 1 ELSE 0 END;
END;
/

CREATE OR REPLACE FUNCTION FN_TELEFONO_EXISTE (
    p_telefono IN VARCHAR2,
    p_id_persona_excluir IN NUMBER DEFAULT NULL
) RETURN NUMBER
AS
    v_total NUMBER;
    v_telefono VARCHAR2(30);
BEGIN
    v_telefono := REGEXP_REPLACE(p_telefono, '[^0-9]', '');

    SELECT COUNT(*)
    INTO v_total
    FROM PERSONA
    WHERE TELEFONO = v_telefono
      AND (p_id_persona_excluir IS NULL OR ID_PERSONA <> p_id_persona_excluir);

    RETURN CASE WHEN v_total > 0 THEN 1 ELSE 0 END;
END;
/

CREATE OR REPLACE FUNCTION FN_PEDIDO_PUEDE_CANCELARSE (
    p_id_pedido IN NUMBER,
    p_id_usuario IN NUMBER DEFAULT NULL
) RETURN NUMBER
AS
    v_total NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_total
    FROM PEDIDO p
    JOIN VENTA v ON v.ID_VENTA = p.ID_VENTA
    WHERE p.ID_PEDIDO = p_id_pedido
      AND p.ID_ESTADO IN (1, 2)
      AND (p_id_usuario IS NULL OR v.ID_USUARIO = p_id_usuario);

    RETURN CASE WHEN v_total > 0 THEN 1 ELSE 0 END;
END;
/

CREATE OR REPLACE FUNCTION FN_DIRECCION_PREDETERMINADA (
    p_id_usuario IN NUMBER
) RETURN NUMBER
AS
    v_id_direccion DIRECCION_USUARIO.ID_DIRECCION%TYPE;
BEGIN
    SELECT ID_DIRECCION
    INTO v_id_direccion
    FROM DIRECCION_USUARIO
    WHERE ID_USUARIO = p_id_usuario
      AND NVL(ES_PREDETERMINADA, 0) = 1
    ORDER BY ID_DIRECCION DESC
    FETCH FIRST 1 ROWS ONLY;

    RETURN v_id_direccion;
EXCEPTION
    WHEN NO_DATA_FOUND THEN
        RETURN NULL;
END;
/

CREATE OR REPLACE FUNCTION FN_TARJETA_VENCIDA (
    p_fecha_expiracion IN DATE
) RETURN NUMBER
AS
BEGIN
    IF p_fecha_expiracion IS NULL THEN
        RETURN 1;
    END IF;

    RETURN CASE
        WHEN LAST_DAY(p_fecha_expiracion) < TRUNC(SYSDATE) THEN 1
        ELSE 0
    END;
END;
/

--------------------------------------------------------------------------------
-- 3. VISTAS FALTANTES O DE APOYO ADMINISTRATIVO
--------------------------------------------------------------------------------

CREATE OR REPLACE VIEW V_PRODUCTOS_ADMIN AS
SELECT
    p.ID_PRODUCTO,
    p.NOMBRE,
    p.CODIGO,
    p.DESCRIPCION,
    p.PRECIO,
    p.ESTADO,
    p.ID_CATEGORIA,
    c.NOMBRE AS CATEGORIA_NOMBRE,
    img.IMAGEN
FROM PRODUCTO p
JOIN CATEGORIA_PRODUCTO c ON c.ID_CATEGORIA = p.ID_CATEGORIA
LEFT JOIN (
    SELECT
        ID_PRODUCTO,
        MIN(URL) KEEP (DENSE_RANK FIRST ORDER BY NVL(ORDEN, 999999), ID_IMAGEN) AS IMAGEN
    FROM PRODUCTO_IMAGEN
    GROUP BY ID_PRODUCTO
) img ON img.ID_PRODUCTO = p.ID_PRODUCTO;
/

CREATE OR REPLACE VIEW V_DIRECCIONES_USUARIO AS
SELECT
    ID_DIRECCION,
    ID_USUARIO,
    NOMBRE,
    APELLIDO,
    DIRECCION,
    CIUDAD,
    BARRIO,
    TELEFONO,
    TELEFONO_ALT,
    INFO_ADICIONAL,
    NVL(ES_PREDETERMINADA, 0) AS ES_PREDETERMINADA,
    CREATED_AT
FROM DIRECCION_USUARIO;
/

CREATE OR REPLACE VIEW V_PAGOS_PEDIDO AS
SELECT
    p.ID_PAGO,
    p.ID_VENTA,
    pe.ID_PEDIDO,
    p.ID_METODO,
    mp.FORMA_PAGO,
    p.MONTO,
    p.FECHA,
    p.ESTADO
FROM PAGO p
JOIN VENTA v ON v.ID_VENTA = p.ID_VENTA
JOIN PEDIDO pe ON pe.ID_VENTA = v.ID_VENTA
JOIN METODO_PAGO mp ON mp.ID_METODO = p.ID_METODO;
/

CREATE OR REPLACE VIEW V_FACTURA_PEDIDO AS
SELECT
    pe.ID_PEDIDO,
    v.ID_VENTA,
    v.ID_USUARIO,
    u.USERNAME,

    per.NOMBRES,
    per.APELLIDOS,
    per.CORREO,

    v.FECHA,

    SUM(dv.SUBTOTAL) AS SUBTOTAL,

    v.IVA,
    v.ENVIO,
    v.TOTAL,

    ep.NOMBRE AS ESTADO_PEDIDO,

    dp.NOMBRE_RECEPTOR,
    dp.APELLIDO_RECEPTOR,
    dp.DIRECCION_ENVIO,
    dp.CIUDAD,
    dp.BARRIO,
    dp.TELEFONO_RECEPTOR

FROM PEDIDO pe

JOIN VENTA v
    ON v.ID_VENTA = pe.ID_VENTA

JOIN DETALLE_VENTA dv
    ON dv.ID_VENTA = v.ID_VENTA

JOIN USUARIO u
    ON u.ID_USUARIO = v.ID_USUARIO

JOIN PERSONA per
    ON per.ID_PERSONA = u.ID_PERSONA

JOIN ESTADO_PEDIDO ep
    ON ep.ID_ESTADO = pe.ID_ESTADO

LEFT JOIN DIRECCION_PEDIDO dp
    ON dp.ID_DIRECCION_PEDIDO = pe.ID_DIRECCION_PEDIDO

GROUP BY
    pe.ID_PEDIDO,
    v.ID_VENTA,
    v.ID_USUARIO,
    u.USERNAME,
    per.NOMBRES,
    per.APELLIDOS,
    per.CORREO,
    v.FECHA,
    v.IVA,
    v.ENVIO,
    v.TOTAL,
    ep.NOMBRE,
    dp.NOMBRE_RECEPTOR,
    dp.APELLIDO_RECEPTOR,
    dp.DIRECCION_ENVIO,
    dp.CIUDAD,
    dp.BARRIO,
    dp.TELEFONO_RECEPTOR;
/

CREATE OR REPLACE VIEW V_ADMIN_PEDIDOS AS
SELECT
    pe.ID_PEDIDO,
    pe.ID_VENTA,
    pe.ID_ESTADO,
    ep.NOMBRE AS ESTADO,
    v.ID_USUARIO,
    per.NOMBRES || ' ' || per.APELLIDOS AS CLIENTE,
    per.CORREO,
    v.FECHA,
    v.TOTAL,
    dp.CIUDAD,
    dp.BARRIO,
    dp.DIRECCION_ENVIO
FROM PEDIDO pe
JOIN VENTA v ON v.ID_VENTA = pe.ID_VENTA
JOIN USUARIO u ON u.ID_USUARIO = v.ID_USUARIO
JOIN PERSONA per ON per.ID_PERSONA = u.ID_PERSONA
JOIN ESTADO_PEDIDO ep ON ep.ID_ESTADO = pe.ID_ESTADO
LEFT JOIN DIRECCION_PEDIDO dp ON dp.ID_DIRECCION_PEDIDO = pe.ID_DIRECCION_PEDIDO;
/

--------------------------------------------------------------------------------
-- 4. PROCEDIMIENTOS USUARIO / PERFIL / RECUPERACION
--------------------------------------------------------------------------------

CREATE OR REPLACE PROCEDURE SP_REGISTRAR_USUARIO (
    p_nombres IN VARCHAR2,
    p_apellidos IN VARCHAR2,
    p_cc IN VARCHAR2,
    p_correo IN VARCHAR2,
    p_telefono IN VARCHAR2,
    p_direccion IN VARCHAR2,
    p_username IN VARCHAR2,
    p_password_hash IN VARCHAR2,
    p_id_tipo IN NUMBER,
    p_id_usuario OUT NUMBER
)
AS
    v_id_persona NUMBER;
BEGIN
    IF FN_CORREO_EXISTE(p_correo) = 1 THEN
        RAISE_APPLICATION_ERROR(-20510, 'El correo ya esta registrado');
    END IF;

    IF FN_TELEFONO_EXISTE(p_telefono) = 1 THEN
        RAISE_APPLICATION_ERROR(-20511, 'El telefono ya esta registrado');
    END IF;

    IF FN_USUARIO_EXISTE(p_username) = 1 THEN
        RAISE_APPLICATION_ERROR(-20512, 'El usuario ya esta registrado');
    END IF;

    INSERT INTO PERSONA (
        NOMBRES,
        APELLIDOS,
        CC,
        CORREO,
        TELEFONO,
        DIRECCION
    ) VALUES (
        TRIM(p_nombres),
        TRIM(p_apellidos),
        TRIM(p_cc),
        LOWER(TRIM(p_correo)),
        REGEXP_REPLACE(p_telefono, '[^0-9]', ''),
        TRIM(p_direccion)
    )
    RETURNING ID_PERSONA INTO v_id_persona;

    INSERT INTO USUARIO (
        ID_PERSONA,
        ID_TIPO,
        USERNAME,
        PASSWORD,
        ESTADO
    ) VALUES (
        v_id_persona,
        NVL(p_id_tipo, 3),
        LOWER(TRIM(p_username)),
        p_password_hash,
        'ACTIVO'
    )
    RETURNING ID_USUARIO INTO p_id_usuario;
END;
/

CREATE OR REPLACE PROCEDURE SP_ACTUALIZAR_PERFIL (
    p_id_usuario IN NUMBER,
    p_nombres IN VARCHAR2,
    p_apellidos IN VARCHAR2,
    p_correo IN VARCHAR2,
    p_telefono IN VARCHAR2,
    p_direccion IN VARCHAR2
)
AS
    v_id_persona NUMBER;
BEGIN
    SELECT ID_PERSONA
    INTO v_id_persona
    FROM USUARIO
    WHERE ID_USUARIO = p_id_usuario;

    IF FN_CORREO_EXISTE(p_correo, v_id_persona) = 1 THEN
        RAISE_APPLICATION_ERROR(-20513, 'El correo ya esta en uso');
    END IF;

    IF FN_TELEFONO_EXISTE(p_telefono, v_id_persona) = 1 THEN
        RAISE_APPLICATION_ERROR(-20514, 'El telefono ya esta en uso');
    END IF;

    UPDATE PERSONA
    SET NOMBRES = TRIM(p_nombres),
        APELLIDOS = TRIM(p_apellidos),
        CORREO = LOWER(TRIM(p_correo)),
        TELEFONO = REGEXP_REPLACE(p_telefono, '[^0-9]', ''),
        DIRECCION = TRIM(p_direccion)
    WHERE ID_PERSONA = v_id_persona;
END;
/

CREATE OR REPLACE PROCEDURE SP_CAMBIAR_PASSWORD (
    p_id_usuario IN NUMBER,
    p_password_hash IN VARCHAR2
)
AS
BEGIN
    UPDATE USUARIO
    SET PASSWORD = p_password_hash
    WHERE ID_USUARIO = p_id_usuario;

    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20515, 'Usuario no encontrado');
    END IF;
END;
/

CREATE OR REPLACE PROCEDURE SP_GENERAR_TOKEN_RECUPERACION (
    p_id_usuario IN NUMBER,
    p_token IN VARCHAR2
)
AS
BEGIN
    INSERT INTO PASSWORD_RESETS (
        USUARIO_ID,
        TOKEN,
        EXPIRES_AT,
        USED
    ) VALUES (
        p_id_usuario,
        p_token,
        CURRENT_TIMESTAMP + INTERVAL '1' DAY,
        0
    );
END;
/

CREATE OR REPLACE PROCEDURE SP_MARCAR_TOKEN_USADO (
    p_token IN VARCHAR2
)
AS
BEGIN
    UPDATE PASSWORD_RESETS
    SET USED = 1
    WHERE TOKEN = p_token;
END;
/

--------------------------------------------------------------------------------
-- 5. PROCEDIMIENTOS PRODUCTOS / IMAGENES
--------------------------------------------------------------------------------

CREATE OR REPLACE PROCEDURE SP_CREAR_PRODUCTO (
    p_nombre IN VARCHAR2,
    p_codigo IN NUMBER,
    p_descripcion IN VARCHAR2,
    p_precio IN NUMBER,
    p_estado IN VARCHAR2,
    p_id_categoria IN NUMBER,
    p_id_producto OUT NUMBER
)
AS
BEGIN
    INSERT INTO PRODUCTO (
        NOMBRE,
        CODIGO,
        DESCRIPCION,
        PRECIO,
        ESTADO,
        ID_CATEGORIA
    ) VALUES (
        TRIM(p_nombre),
        p_codigo,
        p_descripcion,
        p_precio,
        NVL(p_estado, 'Activo'),
        p_id_categoria
    )
    RETURNING ID_PRODUCTO INTO p_id_producto;
END;
/

CREATE OR REPLACE PROCEDURE SP_ACTUALIZAR_PRODUCTO (
    p_id_producto IN NUMBER,
    p_nombre IN VARCHAR2,
    p_codigo IN NUMBER,
    p_descripcion IN VARCHAR2,
    p_precio IN NUMBER,
    p_estado IN VARCHAR2,
    p_id_categoria IN NUMBER
)
AS
BEGIN
    UPDATE PRODUCTO
    SET NOMBRE = TRIM(p_nombre),
        CODIGO = p_codigo,
        DESCRIPCION = p_descripcion,
        PRECIO = p_precio,
        ESTADO = p_estado,
        ID_CATEGORIA = p_id_categoria
    WHERE ID_PRODUCTO = p_id_producto;

    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20520, 'Producto no encontrado');
    END IF;
END;
/

CREATE OR REPLACE PROCEDURE SP_ELIMINAR_PRODUCTO (
    p_id_producto IN NUMBER
)
AS
BEGIN
    DELETE FROM PRODUCTO_IMAGEN
    WHERE ID_PRODUCTO = p_id_producto;

    DELETE FROM PRODUCTO
    WHERE ID_PRODUCTO = p_id_producto;

    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20521, 'Producto no encontrado');
    END IF;
END;
/

CREATE OR REPLACE PROCEDURE SP_GUARDAR_IMAGEN_PRODUCTO (
    p_id_producto IN NUMBER,
    p_url IN VARCHAR2,
    p_orden IN NUMBER
)
AS
BEGIN
    INSERT INTO PRODUCTO_IMAGEN (
        ID_PRODUCTO,
        URL,
        ORDEN
    ) VALUES (
        p_id_producto,
        p_url,
        NVL(p_orden, 1)
    );
END;
/

CREATE OR REPLACE PROCEDURE SP_ELIMINAR_IMAGEN_PRODUCTO (
    p_id_imagen IN NUMBER
)
AS
BEGIN
    DELETE FROM PRODUCTO_IMAGEN
    WHERE ID_IMAGEN = p_id_imagen;

    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20522, 'Imagen no encontrada');
    END IF;
END;
/

--------------------------------------------------------------------------------
-- 6. PROCEDIMIENTOS DIRECCIONES
--------------------------------------------------------------------------------

CREATE OR REPLACE PROCEDURE SP_GUARDAR_DIRECCION_USUARIO (
    p_id_usuario IN NUMBER,
    p_nombre IN VARCHAR2,
    p_apellido IN VARCHAR2,
    p_direccion IN VARCHAR2,
    p_ciudad IN VARCHAR2,
    p_barrio IN VARCHAR2,
    p_telefono IN VARCHAR2,
    p_telefono_alt IN VARCHAR2,
    p_info_adicional IN VARCHAR2,
    p_es_predeterminada IN NUMBER,
    p_id_direccion OUT NUMBER
)
AS
    v_pred NUMBER(1);
    v_total NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_total
    FROM DIRECCION_USUARIO
    WHERE ID_USUARIO = p_id_usuario;

    v_pred := CASE
        WHEN NVL(p_es_predeterminada, 0) = 1 OR v_total = 0 THEN 1
        ELSE 0
    END;

    IF v_pred = 1 THEN
        UPDATE DIRECCION_USUARIO
        SET ES_PREDETERMINADA = 0
        WHERE ID_USUARIO = p_id_usuario;
    END IF;

    INSERT INTO DIRECCION_USUARIO (
        ID_USUARIO,
        NOMBRE,
        APELLIDO,
        DIRECCION,
        CIUDAD,
        BARRIO,
        TELEFONO,
        TELEFONO_ALT,
        INFO_ADICIONAL,
        ES_PREDETERMINADA,
        CREATED_AT
    ) VALUES (
        p_id_usuario,
        TRIM(p_nombre),
        TRIM(p_apellido),
        TRIM(p_direccion),
        TRIM(p_ciudad),
        TRIM(p_barrio),
        REGEXP_REPLACE(p_telefono, '[^0-9]', ''),
        REGEXP_REPLACE(p_telefono_alt, '[^0-9]', ''),
        p_info_adicional,
        v_pred,
        SYSDATE
    )
    RETURNING ID_DIRECCION INTO p_id_direccion;
END;
/

CREATE OR REPLACE PROCEDURE SP_ACTUALIZAR_DIRECCION_USUARIO (
    p_id_direccion IN NUMBER,
    p_id_usuario IN NUMBER,
    p_nombre IN VARCHAR2,
    p_apellido IN VARCHAR2,
    p_direccion IN VARCHAR2,
    p_ciudad IN VARCHAR2,
    p_barrio IN VARCHAR2,
    p_telefono IN VARCHAR2,
    p_telefono_alt IN VARCHAR2,
    p_info_adicional IN VARCHAR2,
    p_es_predeterminada IN NUMBER
)
AS
BEGIN
    IF NVL(p_es_predeterminada, 0) = 1 THEN
        UPDATE DIRECCION_USUARIO
        SET ES_PREDETERMINADA = 0
        WHERE ID_USUARIO = p_id_usuario;
    END IF;

    UPDATE DIRECCION_USUARIO
    SET NOMBRE = TRIM(p_nombre),
        APELLIDO = TRIM(p_apellido),
        DIRECCION = TRIM(p_direccion),
        CIUDAD = TRIM(p_ciudad),
        BARRIO = TRIM(p_barrio),
        TELEFONO = REGEXP_REPLACE(p_telefono, '[^0-9]', ''),
        TELEFONO_ALT = REGEXP_REPLACE(p_telefono_alt, '[^0-9]', ''),
        INFO_ADICIONAL = p_info_adicional,
        ES_PREDETERMINADA = CASE WHEN NVL(p_es_predeterminada, 0) = 1 THEN 1 ELSE ES_PREDETERMINADA END
    WHERE ID_DIRECCION = p_id_direccion
      AND ID_USUARIO = p_id_usuario;

    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20530, 'Direccion no encontrada');
    END IF;
END;
/

CREATE OR REPLACE PROCEDURE SP_ELIMINAR_DIRECCION_USUARIO (
    p_id_direccion IN NUMBER,
    p_id_usuario IN NUMBER
)
AS
    v_total NUMBER;
    v_pred NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_total
    FROM DIRECCION_USUARIO
    WHERE ID_USUARIO = p_id_usuario;

    IF v_total <= 1 THEN
        RAISE_APPLICATION_ERROR(-20531, 'No puedes eliminar la unica direccion');
    END IF;

    SELECT NVL(ES_PREDETERMINADA, 0)
    INTO v_pred
    FROM DIRECCION_USUARIO
    WHERE ID_DIRECCION = p_id_direccion
      AND ID_USUARIO = p_id_usuario;

    DELETE FROM DIRECCION_USUARIO
    WHERE ID_DIRECCION = p_id_direccion
      AND ID_USUARIO = p_id_usuario;

    IF v_pred = 1 THEN
        UPDATE DIRECCION_USUARIO
        SET ES_PREDETERMINADA = 1
        WHERE ID_DIRECCION = (
            SELECT MIN(ID_DIRECCION)
            FROM DIRECCION_USUARIO
            WHERE ID_USUARIO = p_id_usuario
        );
    END IF;
END;
/

CREATE OR REPLACE PROCEDURE SP_ACTUALIZAR_DIRECCION_PEDIDO (
    p_id_pedido IN NUMBER,
    p_nombre_receptor IN VARCHAR2,
    p_apellido_receptor IN VARCHAR2,
    p_direccion_envio IN VARCHAR2,
    p_ciudad IN VARCHAR2,
    p_barrio IN VARCHAR2,
    p_telefono_receptor IN VARCHAR2,
    p_telefono_alterno IN VARCHAR2,
    p_informacion_adicional IN VARCHAR2
)
AS
BEGIN
    UPDATE DIRECCION_PEDIDO dp
    SET NOMBRE_RECEPTOR = TRIM(p_nombre_receptor),
        APELLIDO_RECEPTOR = TRIM(p_apellido_receptor),
        DIRECCION_ENVIO = TRIM(p_direccion_envio),
        CIUDAD = TRIM(p_ciudad),
        BARRIO = TRIM(p_barrio),
        TELEFONO_RECEPTOR = REGEXP_REPLACE(p_telefono_receptor, '[^0-9]', ''),
        TELEFONO_ALTERNO = REGEXP_REPLACE(p_telefono_alterno, '[^0-9]', ''),
        INFORMACION_ADICIONAL = p_informacion_adicional,
        UPDATED_AT = SYSDATE
    WHERE dp.ID_PEDIDO = p_id_pedido
      AND EXISTS (
          SELECT 1
          FROM PEDIDO p
          WHERE p.ID_PEDIDO = dp.ID_PEDIDO
            AND p.ID_ESTADO = 1
      );

    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20532, 'Solo puedes editar direcciones de pedidos pendientes');
    END IF;
END;
/

--------------------------------------------------------------------------------
-- 7. PROCEDIMIENTOS PEDIDOS Y METODOS DE PAGO
--------------------------------------------------------------------------------

CREATE OR REPLACE PROCEDURE SP_CANCELAR_PEDIDO (
    p_id_pedido IN NUMBER,
    p_id_usuario IN NUMBER
)
AS
BEGIN
    UPDATE PEDIDO p
    SET p.ID_ESTADO = 5
    WHERE p.ID_PEDIDO = p_id_pedido
      AND p.ID_ESTADO IN (1, 2)
      AND EXISTS (
          SELECT 1
          FROM VENTA v
          WHERE v.ID_VENTA = p.ID_VENTA
            AND v.ID_USUARIO = p_id_usuario
      );

    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20540, 'No se puede cancelar el pedido');
    END IF;
END;
/

CREATE OR REPLACE PROCEDURE SP_ACTUALIZAR_METODO_PAGO (
    p_id_metodo_pago_usuario IN NUMBER,
    p_id_usuario IN NUMBER,
    p_titular IN VARCHAR2,
    p_fecha_expiracion IN VARCHAR2,
    p_es_predeterminado IN NUMBER
)
AS
BEGIN
    IF NVL(p_es_predeterminado, 0) = 1 THEN
        UPDATE METODO_PAGO_USUARIO
        SET ES_PREDETERMINADO = 0
        WHERE ID_USUARIO = p_id_usuario;
    END IF;

    UPDATE METODO_PAGO_USUARIO
    SET TITULAR = TRIM(p_titular),
        FECHA_EXPIRACION = TO_DATE(p_fecha_expiracion, 'YYYY-MM-DD'),
        ES_PREDETERMINADO = CASE WHEN NVL(p_es_predeterminado, 0) = 1 THEN 1 ELSE 0 END
    WHERE ID_METODO_PAGO_USUARIO = p_id_metodo_pago_usuario
      AND ID_USUARIO = p_id_usuario
      AND ACTIVO = 1;

    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20550, 'Metodo de pago no encontrado');
    END IF;
END;
/

CREATE OR REPLACE PROCEDURE SP_ELIMINAR_METODO_PAGO (
    p_id_metodo_pago_usuario IN NUMBER,
    p_id_usuario IN NUMBER
)
AS
BEGIN
    DELETE FROM METODO_PAGO_USUARIO
    WHERE ID_METODO_PAGO_USUARIO = p_id_metodo_pago_usuario
      AND ID_USUARIO = p_id_usuario;

    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20551, 'Metodo de pago no encontrado');
    END IF;
END;
/

CREATE OR REPLACE PROCEDURE SP_PREDETERMINAR_METODO_PAGO (
    p_id_metodo_pago_usuario IN NUMBER,
    p_id_usuario IN NUMBER
)
AS
BEGIN
    UPDATE METODO_PAGO_USUARIO
    SET ES_PREDETERMINADO = 0
    WHERE ID_USUARIO = p_id_usuario;

    UPDATE METODO_PAGO_USUARIO
    SET ES_PREDETERMINADO = 1
    WHERE ID_METODO_PAGO_USUARIO = p_id_metodo_pago_usuario
      AND ID_USUARIO = p_id_usuario
      AND ACTIVO = 1;

    IF SQL%ROWCOUNT = 0 THEN
        RAISE_APPLICATION_ERROR(-20552, 'Metodo de pago no encontrado');
    END IF;
END;
/
