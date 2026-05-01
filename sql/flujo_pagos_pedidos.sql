DECLARE
    PROCEDURE add_column_if_missing(
        p_table_name IN VARCHAR2,
        p_column_name IN VARCHAR2,
        p_definition IN VARCHAR2
    )
    AS
        v_count NUMBER;
    BEGIN
        SELECT COUNT(*) INTO v_count
        FROM USER_TAB_COLUMNS
        WHERE TABLE_NAME = UPPER(p_table_name)
        AND COLUMN_NAME = UPPER(p_column_name);

        IF v_count = 0 THEN
            EXECUTE IMMEDIATE 'ALTER TABLE ' || p_table_name || ' ADD ' || p_column_name || ' ' || p_definition;
        END IF;
    END;
BEGIN
    add_column_if_missing('VENTA', 'SUBTOTAL', 'NUMBER(10,2)');
    add_column_if_missing('VENTA', 'IVA', 'NUMBER(10,2)');
    add_column_if_missing('VENTA', 'ENVIO', 'NUMBER(10,2)');
    add_column_if_missing('VENTA', 'METODO_PAGO', 'VARCHAR2(50)');
    add_column_if_missing('VENTA', 'ESTADO', 'VARCHAR2(20)');
    add_column_if_missing('VENTA', 'FECHA_PAGO', 'DATE');
    add_column_if_missing('DETALLE_VENTA', 'PRECIO_UNITARIO', 'NUMBER(10,2)');
    add_column_if_missing('DETALLE_VENTA', 'SUBTOTAL', 'NUMBER(10,2)');
    add_column_if_missing('DETALLE_VENTA', 'NOMBRE_PRODUCTO', 'VARCHAR2(150)');
END;
/

CREATE OR REPLACE PROCEDURE PC_CREAR_VENTA (
    p_id_usuario IN NUMBER,
    p_subtotal IN NUMBER,
    p_iva IN NUMBER,
    p_envio IN NUMBER,
    p_id_cliente IN NUMBER,
    p_metodo_pago IN VARCHAR2,
    p_estado IN VARCHAR2,
    p_id_venta OUT NUMBER
)
AS
    v_total NUMBER(10,2);
BEGIN
    v_total := NVL(p_subtotal, 0) + NVL(p_iva, 0) + NVL(p_envio, 0);

    INSERT INTO VENTA (
        ID_USUARIO,
        ID_CLIENTE,
        ID_TIPO,
        FECHA,
        TOTAL,
        SUBTOTAL,
        IVA,
        ENVIO,
        METODO_PAGO,
        ESTADO,
        FECHA_PAGO
    )
    VALUES (
        p_id_usuario,
        p_id_cliente,
        2,
        SYSDATE,
        v_total,
        NVL(p_subtotal, 0),
        NVL(p_iva, 0),
        NVL(p_envio, 0),
        p_metodo_pago,
        NVL(p_estado, 'PENDIENTE'),
        CASE WHEN p_metodo_pago IS NULL THEN NULL ELSE SYSDATE END
    )
    RETURNING ID_VENTA INTO p_id_venta;
END;
/

CREATE OR REPLACE PROCEDURE PC_INSERTAR_DETALLE_VENTA (
    p_id_venta IN NUMBER,
    p_id_producto IN NUMBER,
    p_cantidad IN NUMBER
)
AS
    v_precio_unitario NUMBER(10,2);
    v_nombre_producto VARCHAR2(150);
    v_subtotal NUMBER(10,2);
BEGIN
    SELECT PRECIO, SUBSTR(NOMBRE, 1, 150)
    INTO v_precio_unitario, v_nombre_producto
    FROM PRODUCTO
    WHERE ID_PRODUCTO = p_id_producto;

    v_subtotal := NVL(v_precio_unitario, 0) * NVL(p_cantidad, 0);

    INSERT INTO DETALLE_VENTA (
        ID_VENTA,
        ID_PRODUCTO,
        CANTIDAD,
        PRECIO_UNITARIO,
        SUBTOTAL,
        NOMBRE_PRODUCTO
    )
    VALUES (
        p_id_venta,
        p_id_producto,
        p_cantidad,
        v_precio_unitario,
        v_subtotal,
        v_nombre_producto
    );
END;
/

CREATE OR REPLACE PROCEDURE SP_PROCESAR_PAGO (
    p_id_venta IN NUMBER,
    p_metodo IN NUMBER,
    p_monto IN NUMBER
)
AS
    v_metodo_pago VARCHAR2(50);
BEGIN
    v_metodo_pago := CASE p_metodo
        WHEN 1 THEN 'Tarjeta'
        WHEN 2 THEN 'PSE'
        WHEN 3 THEN 'Transferencia'
        WHEN 4 THEN 'Contraentrega'
        ELSE 'Otro'
    END;

    INSERT INTO PAGO (
        ID_PAGO,
        ID_VENTA,
        ID_METODO,
        MONTO,
        FECHA
    )
    VALUES (
        SEQ_PAGO.NEXTVAL,
        p_id_venta,
        p_metodo,
        p_monto,
        SYSTIMESTAMP
    );

    UPDATE VENTA
    SET METODO_PAGO = v_metodo_pago,
        ESTADO = 'PAGADA',
        FECHA_PAGO = SYSDATE
    WHERE ID_VENTA = p_id_venta;

END;
/

CREATE OR REPLACE TRIGGER TRG_PAGO_COMPLETADO
AFTER INSERT ON PAGO
FOR EACH ROW
BEGIN
    UPDATE PEDIDO
    SET ID_ESTADO = 2
    WHERE ID_VENTA = :NEW.ID_VENTA
    AND ID_ESTADO = 1;
END;
/

DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*)
    INTO v_count
    FROM USER_CONSTRAINTS
    WHERE CONSTRAINT_NAME = 'UK_PAGO_VENTA'
    AND TABLE_NAME = 'PAGO';

    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'ALTER TABLE PAGO ADD CONSTRAINT UK_PAGO_VENTA UNIQUE (ID_VENTA)';
    END IF;
END;
/

CREATE OR REPLACE VIEW V_PEDIDOS_USUARIO AS
SELECT
    p.ID_PEDIDO,
    v.FECHA,
    v.TOTAL,
    e.NOMBRE AS ESTADO,
    v.ID_USUARIO
FROM PEDIDO p
JOIN VENTA v ON p.ID_VENTA = v.ID_VENTA
JOIN ESTADO_PEDIDO e ON p.ID_ESTADO = e.ID_ESTADO;
/
