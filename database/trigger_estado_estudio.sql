-- 1. Crear o reemplazar la función
CREATE OR REPLACE FUNCTION fn_marcar_estudio_realizado()
RETURNS TRIGGER AS $$
DECLARE
    v_estado_actual VARCHAR(20);
BEGIN
    -- Obtener el estado actual del estudio con bloqueo de fila
    SELECT estado INTO v_estado_actual
    FROM estudio
    WHERE id_estudio = NEW.id_estudio;

    -- Validar existencia y estado
    IF v_estado_actual IS NULL THEN
        RAISE EXCEPTION 'El estudio con ID % no existe.', NEW.id_estudio;
    END IF;

    -- Cambiar el estado del estudio
    UPDATE estudio
    SET estado = 'REALIZADO'
    WHERE id_estudio = NEW.id_estudio;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- 2. Eliminar el trigger si ya existía para evitar duplicados
DROP TRIGGER IF EXISTS trg_marcar_estudio_realizado ON resultado;

-- 3. Crear el trigger en la tabla resultado
CREATE TRIGGER trg_marcar_estudio_realizado
AFTER INSERT ON resultado
FOR EACH ROW
EXECUTE FUNCTION fn_marcar_estudio_realizado();


-- en caso que se quieran eliminar el trigger
-- 1. Eliminar el trigger de la tabla resultado
-- DROP TRIGGER IF EXISTS trg_marcar_estudio_realizado ON resultado;

-- 2. Eliminar la función asociada
-- DROP FUNCTION IF EXISTS fn_marcar_estudio_realizado();