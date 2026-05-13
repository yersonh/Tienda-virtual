-- =====================================================================
-- MIGRATION: fix_produccion_webhook.sql
-- Problema: pedidos se cancelan automaticamente (ID_ESTADO = 5) antes
-- de que llegue el webhook APPROVED, y el pago queda sin reflejarse.
--
-- Solucion:
-- 1. Columna CANCELADO_AUTOMATICO en PEDIDO para distinguir cancelacion
--    por timeout vs cancelacion manual por usuario/admin.
-- 2. SP_EXPIRAR_PEDIDOS marca CANCELADO_AUTOMATICO = 1.
-- 3. SP_PROCESAR_PAGO reactiva SOLO pedidos auto-cancelados.
-- 4. Trigger TRG_PAGO_AI_ESTADO_PEDIDO con la misma logica.
-- 5. Ventana de expiracion ampliada de 5 a 20 minutos para produccion
--    (PSE/Nequi pueden tardar mas de 5 minutos).
--
-- EJECUTAR UNA SOLA VEZ en el schema ADMIN (Oracle Autonomous DB).
-- =====================================================================

-- ── 1. Columna CANCELADO_AUTOMATICO ──────────────────────────────────
DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count
    FROM USER_TAB_COLUMNS
    WHERE TABLE_NAME  = 'PEDIDO'
      AND COLUMN_NAME = 'CANCELADO_AUTOMATICO';

    IF v_count = 0 THEN
        -- DEFAULT 0 = cancelacion manual o aun no cancelado
        -- 1 = cancelado automaticamente por SP_EXPIRAR_PEDIDOS
        EXECUTE IMMEDIATE
            'ALTER TABLE PEDIDO ADD CANCELADO_AUTOMATICO NUMBER(1) DEFAULT 0 NOT NULL';
        DBMS_OUTPUT.PUT_LINE('Columna CANCELADO_AUTOMATICO agregada a PEDIDO.');
    ELSE
        DBMS_OUTPUT.PUT_LINE('Columna CANCELADO_AUTOMATICO ya existe en PEDIDO.');
    END IF;
END;
/

-- ── 2. Indice para mejorar performance de las queries de expiracion ──
DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count
    FROM USER_INDEXES
    WHERE INDEX_NAME = 'IDX_PEDIDO_AUTO_CANCEL';

    IF v_count = 0 THEN
        EXECUTE IMMEDIATE
            'CREATE INDEX IDX_PEDIDO_AUTO_CANCEL ON PEDIDO (ID_ESTADO, CANCELADO_AUTOMATICO, CREATED_AT)';
        DBMS_OUTPUT.PUT_LINE('Indice IDX_PEDIDO_AUTO_CANCEL creado.');
    ELSE
        DBMS_OUTPUT.PUT_LINE('Indice IDX_PEDIDO_AUTO_CANCEL ya existe.');
    END IF;
END;
/

-- ── 3. SP_EXPIRAR_PEDIDOS: ventana 20 min + marca CANCELADO_AUTOMATICO ─
CREATE OR REPLACE PROCEDURE SP_EXPIRAR_PEDIDOS AS
BEGIN
    -- Cancela pedidos pendientes sin pago aprobado despues de 20 minutos.
    -- Marca CANCELADO_AUTOMATICO = 1 para que SP_PROCESAR_PAGO pueda
    -- reactivarlos si el webhook llega tarde pero el pago fue real.
    UPDATE PEDIDO p
    SET    p.ID_ESTADO           = 5,
           p.CANCELADO_AUTOMATICO = 1
    WHERE  p.ID_ESTADO  = 1
      AND  p.CREATED_AT IS NOT NULL
      AND  CAST(p.CREATED_AT AS TIMESTAMP) < SYSTIMESTAMP - INTERVAL '20' MINUTE
      AND  NOT EXISTS (
               SELECT 1
               FROM   PAGO pg
               WHERE  pg.ID_VENTA = p.ID_VENTA
                 AND  (
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

-- ── 4. SP_PROCESAR_PAGO: lógica de reactivacion con CANCELADO_AUTOMATICO ─
CREATE OR REPLACE PROCEDURE SP_PROCESAR_PAGO (
    p_id_venta           IN NUMBER,
    p_metodo             IN NUMBER,
    p_estado             IN VARCHAR2,
    p_transaccion_wompi  IN VARCHAR2,
    p_referencia_wompi   IN VARCHAR2,
    p_metodo_real        IN VARCHAR2,
    p_json_respuesta     IN CLOB
)
AS
    v_monto              NUMBER(10,2);
    v_id_pago            PAGO.ID_PAGO%TYPE;
    v_estado_pago_actual PAGO.ESTADO%TYPE;
    v_tx_pago_actual     PAGO.ID_TRANSACCION_WOMPI%TYPE;
    v_estado             VARCHAR2(30);
    v_stock_proc         NUMBER;
    v_pedido_id_estado   PEDIDO.ID_ESTADO%TYPE;
    v_cancelado_auto     NUMBER(1);
    v_rows_actualizados  NUMBER;
BEGIN
    -- Validaciones basicas
    IF p_id_venta IS NULL OR p_id_venta <= 0 THEN
        RAISE_APPLICATION_ERROR(-20001, 'SP_PROCESAR_PAGO: Venta invalida: ' || NVL(TO_CHAR(p_id_venta), 'NULL'));
    END IF;

    v_estado := UPPER(TRIM(p_estado));

    IF v_estado NOT IN ('APPROVED', 'DECLINED', 'ERROR', 'VOIDED') THEN
        RAISE_APPLICATION_ERROR(-20005, 'SP_PROCESAR_PAGO: Estado Wompi invalido: [' || v_estado || ']');
    END IF;

    -- Leer total de la venta (con bloqueo para evitar race condition)
    SELECT TOTAL
    INTO   v_monto
    FROM   VENTA
    WHERE  ID_VENTA = p_id_venta
    FOR UPDATE;

    IF NVL(v_monto, 0) <= 0 THEN
        RAISE_APPLICATION_ERROR(-20003,
            'SP_PROCESAR_PAGO: Venta ' || p_id_venta || ' no tiene un total valido: ' || NVL(TO_CHAR(v_monto), 'NULL'));
    END IF;

    -- Buscar pago existente (con bloqueo)
    BEGIN
        SELECT ID_PAGO, ESTADO, ID_TRANSACCION_WOMPI
        INTO   v_id_pago, v_estado_pago_actual, v_tx_pago_actual
        FROM   PAGO
        WHERE  ID_VENTA = p_id_venta
          AND  ROWNUM   = 1
        FOR UPDATE;
    EXCEPTION
        WHEN NO_DATA_FOUND THEN
            v_id_pago            := NULL;
            v_estado_pago_actual := NULL;
            v_tx_pago_actual     := NULL;
    END;

    -- Insertar o actualizar PAGO
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
        ) VALUES (
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
        -- Actualizar solo si NO esta en un estado terminal real
        -- APPROVED es siempre terminal.
        -- PAGADO/COMPLETADO es terminal solo cuando ya tiene un TX_ID real
        -- (sin TX_ID son placeholders del SP de creacion de pedido).
        UPDATE PAGO
        SET    ID_METODO            = p_metodo,
               MONTO                = v_monto,
               ESTADO               = v_estado,
               ID_TRANSACCION_WOMPI = p_transaccion_wompi,
               REFERENCIA_WOMPI     = p_referencia_wompi,
               METODO_REAL          = p_metodo_real,
               JSON_RESPUESTA       = p_json_respuesta
        WHERE  ID_PAGO = v_id_pago
          AND  (
                   UPPER(TRIM(ESTADO)) NOT IN ('APPROVED', 'PAGADO', 'COMPLETADO')
                   OR (
                       UPPER(TRIM(ESTADO)) IN ('PAGADO', 'COMPLETADO')
                       AND (
                           ID_TRANSACCION_WOMPI IS NULL
                           OR TRIM(TO_CHAR(ID_TRANSACCION_WOMPI)) = ''
                       )
                   )
               );
    END IF;

    -- Actualizar PEDIDO solo si el estado es APPROVED y no estaba ya procesado
    IF v_estado = 'APPROVED'
       AND NOT (
            UPPER(TRIM(NVL(v_estado_pago_actual, 'PENDING'))) = 'APPROVED'
            OR (
                UPPER(TRIM(NVL(v_estado_pago_actual, 'PENDING'))) IN ('PAGADO', 'COMPLETADO')
                AND v_tx_pago_actual IS NOT NULL
                AND TRIM(TO_CHAR(v_tx_pago_actual)) <> ''
            )
       ) THEN

        -- Descontar stock si el SP existe
        SELECT COUNT(*) INTO v_stock_proc
        FROM   USER_PROCEDURES
        WHERE  OBJECT_NAME    = 'SP_DESCONTAR_STOCK'
          AND  PROCEDURE_NAME IS NULL;

        IF v_stock_proc > 0 THEN
            EXECUTE IMMEDIATE 'BEGIN SP_DESCONTAR_STOCK(:id_venta); END;' USING p_id_venta;
        END IF;

        -- Leer estado actual del pedido para logging interno
        BEGIN
            SELECT ID_ESTADO, NVL(CANCELADO_AUTOMATICO, 0)
            INTO   v_pedido_id_estado, v_cancelado_auto
            FROM   PEDIDO
            WHERE  ID_VENTA = p_id_venta
              AND  ROWNUM = 1;
        EXCEPTION
            WHEN NO_DATA_FOUND THEN
                v_pedido_id_estado := NULL;
                v_cancelado_auto   := 0;
        END;

        -- LOGICA CLAVE:
        -- Reactivar pedido si:
        --   a) Esta pendiente de pago (ID_ESTADO = 1), O
        --   b) Fue cancelado AUTOMATICAMENTE por timeout (ID_ESTADO = 5 Y CANCELADO_AUTOMATICO = 1)
        --
        -- NO reactivar si fue cancelado manualmente por usuario o admin
        --   (ID_ESTADO = 5 Y CANCELADO_AUTOMATICO = 0)
        UPDATE PEDIDO
        SET    ID_ESTADO            = 2,
               CANCELADO_AUTOMATICO = 0
        WHERE  ID_VENTA = p_id_venta
          AND  (
                   ID_ESTADO = 1
                   OR (ID_ESTADO = 5 AND NVL(CANCELADO_AUTOMATICO, 0) = 1)
               );

        v_rows_actualizados := SQL%ROWCOUNT;

        -- Si no se actualizo ningun pedido y el estado era 5 con CANCELADO_AUTOMATICO=0,
        -- es una cancelacion manual: el pago se registro pero el pedido no se reactiva.
        -- Esto es correcto por diseno.

    END IF;
END;
/

-- ── 5. Trigger TRG_PAGO_AI_ESTADO_PEDIDO con la misma logica ──────────
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
        -- Reactivar solo pedidos pendientes o auto-cancelados por timeout.
        -- Pedidos cancelados manualmente (CANCELADO_AUTOMATICO = 0 y ID_ESTADO = 5)
        -- NO se reactivan.
        UPDATE PEDIDO
        SET    ID_ESTADO            = 2,
               CANCELADO_AUTOMATICO = 0
        WHERE  ID_VENTA = :NEW.ID_VENTA
          AND  (
                   ID_ESTADO = 1
                   OR (ID_ESTADO = 5 AND NVL(CANCELADO_AUTOMATICO, 0) = 1)
               );
    END IF;
END;
/

-- ── 6. Vista V_PEDIDOS_USUARIO actualizada ────────────────────────────
CREATE OR REPLACE VIEW V_PEDIDOS_USUARIO AS
SELECT
    p.ID_PEDIDO,
    p.ID_ESTADO,
    v.FECHA,
    v.TOTAL,
    CASE
        WHEN p.ID_ESTADO = 1 AND EXISTS (
            SELECT 1 FROM PAGO pg
            WHERE  pg.ID_VENTA = p.ID_VENTA
              AND  UPPER(TRIM(pg.ESTADO)) IN ('APPROVED', 'PAGADO', 'COMPLETADO')
        ) THEN 'Procesado'
        ELSE e.NOMBRE
    END AS ESTADO,
    v.ID_USUARIO
FROM       PEDIDO       p
JOIN       VENTA        v ON p.ID_VENTA  = v.ID_VENTA
JOIN       ESTADO_PEDIDO e ON p.ID_ESTADO = e.ID_ESTADO;
/

-- ── 7. Verificacion final ─────────────────────────────────────────────
SELECT 'CANCELADO_AUTOMATICO existe: ' ||
       CASE WHEN COUNT(*) > 0 THEN 'SI' ELSE 'NO - REVISAR' END AS CHECK_COLUMNA
FROM   USER_TAB_COLUMNS
WHERE  TABLE_NAME  = 'PEDIDO'
  AND  COLUMN_NAME = 'CANCELADO_AUTOMATICO';

SELECT 'SP_EXPIRAR_PEDIDOS valida: ' ||
       CASE WHEN COUNT(*) > 0 THEN 'SI' ELSE 'NO - REVISAR' END AS CHECK_SP_EXPIRAR
FROM   USER_OBJECTS
WHERE  OBJECT_NAME = 'SP_EXPIRAR_PEDIDOS'
  AND  STATUS      = 'VALID';

SELECT 'SP_PROCESAR_PAGO valida: ' ||
       CASE WHEN COUNT(*) > 0 THEN 'SI' ELSE 'NO - REVISAR' END AS CHECK_SP_PROCESAR
FROM   USER_OBJECTS
WHERE  OBJECT_NAME = 'SP_PROCESAR_PAGO'
  AND  STATUS      = 'VALID';

SELECT 'TRG_PAGO_AI_ESTADO_PEDIDO valida: ' ||
       CASE WHEN COUNT(*) > 0 THEN 'SI' ELSE 'NO - REVISAR' END AS CHECK_TRIGGER
FROM   USER_OBJECTS
WHERE  OBJECT_NAME = 'TRG_PAGO_AI_ESTADO_PEDIDO'
  AND  STATUS      = 'VALID';
