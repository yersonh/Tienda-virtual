-- =============================================================
-- NOTIFICACION: tabla de notificaciones del sistema
-- Ejecutar una sola vez contra la base Oracle Autonomous.
-- =============================================================

-- Secuencia
CREATE SEQUENCE SEQ_NOTIFICACION
    START WITH 1
    INCREMENT BY 1
    NOCACHE
    NOCYCLE;

-- Tabla principal
CREATE TABLE NOTIFICACION (
    ID_NOTIFICACION  NUMBER          NOT NULL,
    ID_USUARIO       NUMBER          NOT NULL,
    TITULO           VARCHAR2(200)   NOT NULL,
    MENSAJE          VARCHAR2(1000),
    TIPO             VARCHAR2(50)    DEFAULT 'info'      NOT NULL,
    LEIDA            NUMBER(1)       DEFAULT 0           NOT NULL,
    URL              VARCHAR2(500),
    FECHA_CREACION   DATE            DEFAULT SYSDATE     NOT NULL,
    CONSTRAINT pk_notificacion      PRIMARY KEY (ID_NOTIFICACION),
    CONSTRAINT fk_notif_usuario     FOREIGN KEY (ID_USUARIO)
        REFERENCES USUARIO(ID_USUARIO) ON DELETE CASCADE,
    CONSTRAINT chk_notif_leida      CHECK (LEIDA IN (0, 1)),
    CONSTRAINT chk_notif_tipo       CHECK (TIPO IN ('info','success','warning','error'))
);

-- Índice para acelerar consultas por usuario
CREATE INDEX idx_notif_usuario_leida
    ON NOTIFICACION (ID_USUARIO, LEIDA, FECHA_CREACION DESC);
