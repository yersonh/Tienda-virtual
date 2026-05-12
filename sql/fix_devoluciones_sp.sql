-- REFIX DE PROCEDIMIENTOS DE DEVOLUCION PARA ASEGURAR CAMBIO DE ESTADO
-- Ejecutar en Oracle para solucionar el problema de estados que no cambian.

--------------------------------------------------------------------------------
-- 1. SP_APROBAR_DEVOLUCION
--------------------------------------------------------------------------------
CREATE OR REPLACE PROCEDURE SP_APROBAR_DEVOLUCION (
    p_id_devolucion IN NUMBER,
    p_observacion   IN VARCHAR2
) AS
BEGIN
    -- Actualizar cabecera de devolucion: Estado 2 (Aprobado)
    UPDATE DEVOLUCION
       SET ID_ESTADO_DEVOLUCION = 2,
           OBSERVACION_ADMIN    = p_observacion,
           FECHA_APROBACION     = SYSDATE
     WHERE ID_DEVOLUCION = p_id_devolucion;

    -- Actualizar detalles que no hayan sido rechazados previamente a APROBADO
    UPDATE DEVOLUCION_DETALLE
       SET ESTADO = 'APROBADO'
     WHERE ID_DEVOLUCION = p_id_devolucion
       AND ESTADO = 'PENDIENTE';
       
    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20011, 'Error en SP_APROBAR_DEVOLUCION: ' || SQLERRM);
END;
/

--------------------------------------------------------------------------------
-- 2. SP_RECHAZAR_DEVOLUCION
--------------------------------------------------------------------------------
CREATE OR REPLACE PROCEDURE SP_RECHAZAR_DEVOLUCION (
    p_id_devolucion IN NUMBER,
    p_motivo        IN VARCHAR2
) AS
BEGIN
    -- Actualizar cabecera de devolucion: Estado 3 (Rechazado)
    UPDATE DEVOLUCION
       SET ID_ESTADO_DEVOLUCION = 3,
           OBSERVACION_ADMIN    = p_motivo
     WHERE ID_DEVOLUCION = p_id_devolucion;

    -- Actualizar todos los detalles a RECHAZADO
    UPDATE DEVOLUCION_DETALLE
       SET ESTADO = 'RECHAZADO'
     WHERE ID_DEVOLUCION = p_id_devolucion;

    COMMIT;
EXCEPTION
    WHEN OTHERS THEN
        ROLLBACK;
        RAISE_APPLICATION_ERROR(-20012, 'Error en SP_RECHAZAR_DEVOLUCION: ' || SQLERRM);
END;
/
