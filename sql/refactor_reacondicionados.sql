-- REFACTORIZACION FINAL SISTEMA DE REACONDICIONADOS
-- Versión corregida según nombres de columnas reales y aliases solicitados.

--------------------------------------------------------------------------------
-- 1. PROCEDIMIENTO: SP_PRODUCTO_RECIBIDO
--------------------------------------------------------------------------------
CREATE OR REPLACE PROCEDURE SP_PRODUCTO_RECIBIDO (
    p_id_devolucion_detalle IN NUMBER
) AS
    v_id_producto       NUMBER;
    v_id_referencia     NUMBER;
    v_precio_original   NUMBER;
    v_id_item_existente NUMBER;
    v_num_devoluciones  NUMBER;
BEGIN
    -- A. Marcar el detalle de devolucion como recibido (campo real: PRODUCTO_RECIBIDO)
    UPDATE DEVOLUCION_DETALLE
       SET PRODUCTO_RECIBIDO = 1
     WHERE ID_DEVOLUCION_DETALLE = p_id_devolucion_detalle;

    -- B. Obtener ID_PRODUCTO, ID_REFERENCIA y PRECIO_UNITARIO original
    SELECT dd.ID_PRODUCTO, dd.ID_REFERENCIA, dv.PRECIO_UNITARIO
      INTO v_id_producto, v_id_referencia, v_precio_original
      FROM DEVOLUCION_DETALLE dd
      JOIN DETALLE_VENTA     dv ON dv.ID_DETALLE = dd.ID_DETALLE
     WHERE dd.ID_DEVOLUCION_DETALLE = p_id_devolucion_detalle;

    -- C. Buscar si ya existe este producto/referencia en INVENTARIO_REACONDICIONADO
    BEGIN
        SELECT ID_ITEM_REACONDICIONADO, NUMERO_DEVOLUCIONES
          INTO v_id_item_existente, v_num_devoluciones
          FROM INVENTARIO_REACONDICIONADO
         WHERE ID_PRODUCTO = v_id_producto
           AND ( (v_id_referencia IS NULL AND ID_REFERENCIA IS NULL) OR (ID_REFERENCIA = v_id_referencia) )
           AND ROWNUM = 1;
        
        -- CASO EXISTE: Incrementar contador y actualizar estado
        v_num_devoluciones := v_num_devoluciones + 1;
        
        IF v_num_devoluciones >= 3 THEN
            -- Mover automaticamente a STOCK_MUERTO
            UPDATE INVENTARIO_REACONDICIONADO
               SET NUMERO_DEVOLUCIONES   = v_num_devoluciones,
                   ID_DEVOLUCION_DETALLE = p_id_devolucion_detalle,
                   ESTADO                = 'STOCK_MUERTO',
                   DISPONIBLE            = 0,
                   FECHA_INGRESO         = SYSDATE
             WHERE ID_ITEM_REACONDICIONADO = v_id_item_existente;
        ELSE
            -- Regresar a PENDIENTE de decision
            UPDATE INVENTARIO_REACONDICIONADO
               SET NUMERO_DEVOLUCIONES   = v_num_devoluciones,
                   ID_DEVOLUCION_DETALLE = p_id_devolucion_detalle,
                   ESTADO                = 'PENDIENTE',
                   DISPONIBLE            = 1,
                   DESCUENTO             = 0,
                   PRECIO_FINAL          = v_precio_original,
                   FECHA_INGRESO         = SYSDATE
             WHERE ID_ITEM_REACONDICIONADO = v_id_item_existente;
        END IF;

    EXCEPTION
        WHEN NO_DATA_FOUND THEN
            -- CASO NUEVO: Insertar primer registro
            INSERT INTO INVENTARIO_REACONDICIONADO (
                ID_ITEM_REACONDICIONADO, ID_PRODUCTO, ID_REFERENCIA, ID_DEVOLUCION_DETALLE,
                NUMERO_DEVOLUCIONES, DESCUENTO, PRECIO_ORIGINAL, PRECIO_FINAL,
                ESTADO, DISPONIBLE, FECHA_INGRESO
            ) VALUES (
                SEQ_REACONDICIONADO.NEXTVAL, v_id_producto, v_id_referencia, p_id_devolucion_detalle,
                1, 0, v_precio_original, v_precio_original,
                'PENDIENTE', 1, SYSDATE
            );
    END;

EXCEPTION
    WHEN OTHERS THEN
        RAISE_APPLICATION_ERROR(-20010, 'Error en SP_PRODUCTO_RECIBIDO: ' || SQLERRM);
END;
/

--------------------------------------------------------------------------------
-- 2. VISTAS ACTUALIZADAS (Manteniendo aliases solicitados)
--------------------------------------------------------------------------------

CREATE OR REPLACE VIEW V_OFERTAS_REACONDICIONADOS AS
SELECT ir.*, 
       p.NOMBRE AS PRODUCTO,
       rp.NUMERO_REFERENCIA AS NOMBRE_REFERENCIA,
       (SELECT pi.URL FROM PRODUCTO_IMAGEN pi 
         WHERE pi.ID_PRODUCTO = ir.ID_PRODUCTO 
         ORDER BY pi.ORDEN FETCH FIRST 1 ROWS ONLY) AS IMAGEN
  FROM INVENTARIO_REACONDICIONADO ir
 INNER JOIN PRODUCTO p ON p.ID_PRODUCTO = ir.ID_PRODUCTO
  LEFT JOIN REFERENCIA_PRODUCTO rp ON rp.ID_REFERENCIA = ir.ID_REFERENCIA
 WHERE ir.DISPONIBLE = 1 
   AND ir.ESTADO IN ('OFERTA_10', 'OFERTA_15');
/

CREATE OR REPLACE VIEW V_STOCK_MUERTO AS
SELECT ir.*, 
       p.NOMBRE AS PRODUCTO,
       rp.NUMERO_REFERENCIA AS NOMBRE_REFERENCIA,
       (SELECT pi.URL FROM PRODUCTO_IMAGEN pi 
         WHERE pi.ID_PRODUCTO = ir.ID_PRODUCTO 
         ORDER BY pi.ORDEN FETCH FIRST 1 ROWS ONLY) AS IMAGEN
  FROM INVENTARIO_REACONDICIONADO ir
 INNER JOIN PRODUCTO p ON p.ID_PRODUCTO = ir.ID_PRODUCTO
  LEFT JOIN REFERENCIA_PRODUCTO rp ON rp.ID_REFERENCIA = ir.ID_REFERENCIA
 WHERE ir.ESTADO = 'STOCK_MUERTO';
/
