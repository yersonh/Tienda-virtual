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
    add_column_if_missing('VENTA', 'FECHA', 'DATE');
    add_column_if_missing('DETALLE_VENTA', 'PRECIO_UNITARIO', 'NUMBER(10,2)');
    add_column_if_missing('DETALLE_VENTA', 'SUBTOTAL', 'NUMBER(10,2)');
    add_column_if_missing('DETALLE_VENTA', 'NOMBRE_PRODUCTO', 'VARCHAR2(150)');
    add_column_if_missing('PAGO', 'ID_TRANSACCION_WOMPI', 'VARCHAR2(120)');
    add_column_if_missing('PAGO', 'REFERENCIA_WOMPI', 'VARCHAR2(120)');
    add_column_if_missing('PAGO', 'METODO_REAL', 'VARCHAR2(80)');
    add_column_if_missing('PAGO', 'JSON_RESPUESTA', 'CLOB');
END;
/

CREATE OR REPLACE PROCEDURE PC_CREAR_VENTA (
    p_id_usuario IN NUMBER,
    p_subtotal IN NUMBER,
    p_iva IN NUMBER,
    p_envio IN NUMBER,
    p_id_cliente IN NUMBER,
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
        ENVIO
    )
    VALUES (
        p_id_usuario,
        p_id_cliente,
        2,
        SYSDATE,
        v_total,
        NVL(p_subtotal, 0),
        NVL(p_iva, 0),
        NVL(p_envio, 0)
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

BEGIN
    EXECUTE IMMEDIATE 'DROP TRIGGER TRG_PAGO_COMPLETADO';
EXCEPTION
    WHEN OTHERS THEN
        IF SQLCODE != -4080 THEN
            RAISE;
        END IF;
END;
/

CREATE OR REPLACE PROCEDURE SP_PROCESAR_PAGO (
    p_id_venta IN NUMBER,
    p_metodo IN NUMBER,
    p_estado IN VARCHAR2,
    p_transaccion_wompi IN VARCHAR2,
    p_referencia_wompi IN VARCHAR2,
    p_metodo_real IN VARCHAR2,
    p_json_respuesta IN CLOB
)
AS
    v_monto NUMBER(10,2);
    v_id_pago PAGO.ID_PAGO%TYPE;
    v_estado_pago_actual PAGO.ESTADO%TYPE;
    v_tx_pago_actual PAGO.ID_TRANSACCION_WOMPI%TYPE;
    v_estado VARCHAR2(30);
    v_stock_proc NUMBER;
BEGIN
    IF p_id_venta IS NULL OR p_id_venta <= 0 THEN
        RAISE_APPLICATION_ERROR(-20001, 'Venta invalida');
    END IF;

    IF p_metodo IS NULL OR p_metodo NOT IN (1, 2, 3, 4) THEN
        RAISE_APPLICATION_ERROR(-20002, 'Metodo de pago invalido');
    END IF;

    v_estado := UPPER(TRIM(p_estado));
    IF v_estado NOT IN ('APPROVED', 'DECLINED', 'ERROR', 'VOIDED') THEN
        RAISE_APPLICATION_ERROR(-20005, 'Estado Wompi invalido');
    END IF;

    SELECT TOTAL
    INTO v_monto
    FROM VENTA
    WHERE ID_VENTA = p_id_venta
    FOR UPDATE;

    IF NVL(v_monto, 0) <= 0 THEN
        RAISE_APPLICATION_ERROR(-20003, 'La venta no tiene un total valido');
    END IF;

    BEGIN
        SELECT ID_PAGO, ESTADO, ID_TRANSACCION_WOMPI
        INTO v_id_pago, v_estado_pago_actual, v_tx_pago_actual
        FROM PAGO
        WHERE ID_VENTA = p_id_venta
          AND ROWNUM = 1
        FOR UPDATE;
    EXCEPTION
        WHEN NO_DATA_FOUND THEN
            v_id_pago := NULL;
            v_estado_pago_actual := NULL;
            v_tx_pago_actual := NULL;
    END;

    IF v_id_pago IS NULL THEN
        INSERT INTO PAGO (
            ID_PAGO,
            ID_VENTA,
            ID_METODO,
            MONTO,
            FECHA,
            ESTADO,
            ID_TRANSACCION_WOMPI,
            REFERENCIA_WOMPI,
            METODO_REAL,
            JSON_RESPUESTA
        )
        VALUES (
            SEQ_PAGO.NEXTVAL,
            p_id_venta,
            p_metodo,
            v_monto,
            SYSTIMESTAMP,
            v_estado,
            p_transaccion_wompi,
            p_referencia_wompi,
            p_metodo_real,
            p_json_respuesta
        );
    ELSE
        UPDATE PAGO
        SET ID_METODO = p_metodo,
            MONTO = v_monto,
            ESTADO = v_estado,
            ID_TRANSACCION_WOMPI = p_transaccion_wompi,
            REFERENCIA_WOMPI = p_referencia_wompi,
            METODO_REAL = p_metodo_real,
            JSON_RESPUESTA = p_json_respuesta
        WHERE ID_PAGO = v_id_pago
          AND (
              -- Nunca sobreescribir APPROVED real.
              -- PAGADO/COMPLETADO sin TX_ID son placeholders del SP de pedido:
              -- si el webhook llega con un TX_ID real se deben actualizar.
              UPPER(TRIM(ESTADO)) NOT IN ('APPROVED', 'PAGADO', 'COMPLETADO')
              OR (
                  UPPER(TRIM(ESTADO)) IN ('PAGADO', 'COMPLETADO')
                  AND (ID_TRANSACCION_WOMPI IS NULL
                       OR TRIM(TO_CHAR(ID_TRANSACCION_WOMPI)) = '')
              )
          );
    END IF;

    IF v_estado = 'APPROVED'
       AND NOT (
           UPPER(TRIM(NVL(v_estado_pago_actual, 'PENDING'))) = 'APPROVED'
           OR (
               UPPER(TRIM(NVL(v_estado_pago_actual, 'PENDING'))) IN ('PAGADO', 'COMPLETADO')
               AND v_tx_pago_actual IS NOT NULL
               AND TRIM(TO_CHAR(v_tx_pago_actual)) <> ''
           )
       ) THEN
        SELECT COUNT(*)
        INTO v_stock_proc
        FROM USER_PROCEDURES
        WHERE OBJECT_NAME = 'SP_DESCONTAR_STOCK'
          AND PROCEDURE_NAME IS NULL;

        IF v_stock_proc > 0 THEN
            EXECUTE IMMEDIATE 'BEGIN SP_DESCONTAR_STOCK(:id_venta); END;' USING p_id_venta;
        END IF;

        UPDATE PEDIDO
        SET ID_ESTADO = 2
        WHERE ID_VENTA = p_id_venta
          AND ID_ESTADO IN (1, 5);
    END IF;
    -- DECLINED / ERROR / VOIDED no cancelan el pedido inmediatamente.
    -- El pedido queda en ID_ESTADO = 1 (Pendiente) para permitir reintentos.
    -- SP_EXPIRAR_PEDIDOS lo cancela automaticamente despues de 5 minutos.
END;
/

CREATE OR REPLACE TRIGGER TRG_PAGO_AI_ESTADO_PEDIDO
AFTER INSERT OR UPDATE OF ESTADO, ID_TRANSACCION_WOMPI ON PAGO
FOR EACH ROW
BEGIN
    IF UPPER(TRIM(:NEW.ESTADO)) = 'APPROVED'
       OR (
           UPPER(TRIM(:NEW.ESTADO)) IN ('PAGADO', 'COMPLETADO')
           AND :NEW.ID_TRANSACCION_WOMPI IS NOT NULL
           AND TRIM(TO_CHAR(:NEW.ID_TRANSACCION_WOMPI)) <> ''
       ) THEN
        UPDATE PEDIDO
        SET ID_ESTADO = 2
        WHERE ID_VENTA = :NEW.ID_VENTA
          AND ID_ESTADO IN (1, 5);
    END IF;
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

CREATE OR REPLACE PROCEDURE SP_EXPIRAR_PEDIDOS
AS
BEGIN
    UPDATE PEDIDO p
    SET p.ID_ESTADO = 5
    WHERE p.ID_ESTADO = 1
      AND p.CREATED_AT IS NOT NULL
      AND CAST(p.CREATED_AT AS TIMESTAMP) < SYSTIMESTAMP - INTERVAL '5' MINUTE
      AND NOT EXISTS (
          SELECT 1
          FROM PAGO pg
          WHERE pg.ID_VENTA = p.ID_VENTA
            AND (
                UPPER(TRIM(pg.ESTADO)) = 'APPROVED'
                OR (
                    UPPER(TRIM(pg.ESTADO)) IN ('PAGADO', 'COMPLETADO')
                    AND pg.ID_TRANSACCION_WOMPI IS NOT NULL
                    AND TRIM(TO_CHAR(pg.ID_TRANSACCION_WOMPI)) <> ''
                )
            )
      );
END;
/

CREATE OR REPLACE VIEW V_PEDIDOS_USUARIO AS
SELECT
    p.ID_PEDIDO,
    p.ID_ESTADO,
    v.FECHA,
    v.TOTAL,
    CASE 
        WHEN p.ID_ESTADO = 1 AND EXISTS (
            SELECT 1 FROM PAGO pg 
            WHERE pg.ID_VENTA = p.ID_VENTA 
            AND UPPER(TRIM(pg.ESTADO)) IN ('APPROVED', 'PAGADO', 'COMPLETADO')
        ) THEN 'Procesado'
        ELSE e.NOMBRE 
    END AS ESTADO,
    v.ID_USUARIO
FROM PEDIDO p
JOIN VENTA v ON p.ID_VENTA = v.ID_VENTA
JOIN ESTADO_PEDIDO e ON p.ID_ESTADO = e.ID_ESTADO;
/
