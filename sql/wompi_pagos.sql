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
    add_column_if_missing('PAGO', 'ID_TRANSACCION_WOMPI', 'VARCHAR2(120)');
    add_column_if_missing('PAGO', 'REFERENCIA_WOMPI', 'VARCHAR2(120)');
    add_column_if_missing('PAGO', 'METODO_REAL', 'VARCHAR2(80)');
    add_column_if_missing('PAGO', 'JSON_RESPUESTA', 'CLOB');
END;
/

DECLARE
    v_count NUMBER;
BEGIN
    SELECT COUNT(*) INTO v_count
    FROM USER_INDEXES
    WHERE INDEX_NAME = 'IDX_PAGO_WOMPI_TX';

    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'CREATE INDEX IDX_PAGO_WOMPI_TX ON PAGO(ID_TRANSACCION_WOMPI)';
    END IF;

    SELECT COUNT(*) INTO v_count
    FROM USER_INDEXES
    WHERE INDEX_NAME = 'IDX_PAGO_WOMPI_REF';

    IF v_count = 0 THEN
        EXECUTE IMMEDIATE 'CREATE INDEX IDX_PAGO_WOMPI_REF ON PAGO(REFERENCIA_WOMPI)';
    END IF;
END;
/
