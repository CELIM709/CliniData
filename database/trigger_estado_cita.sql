-- 1. Crear o reemplazar la función
CREATE OR REPLACE FUNCTION fn_completar_cita_tras_consulta()
RETURNS TRIGGER AS $$
DECLARE
    v_estado_actual VARCHAR(20);
BEGIN
    IF NEW.id_cita IS NOT NULL THEN
        -- Obtener estado actual de la cita
        SELECT estado INTO v_estado_actual 
        FROM cita 
        WHERE id_cita = NEW.id_cita;

        -- Validar estado CONFIRMADA
        IF v_estado_actual IS NULL OR v_estado_actual != 'CONFIRMADA' THEN
            RAISE EXCEPTION 'La consulta solo puede asociarse a una cita en estado CONFIRMADA (Estado actual: %).', 
                COALESCE(v_estado_actual, 'NO EXISTE');
        END IF;

        -- Actualizar estado de la cita
        UPDATE cita
        SET estado = 'COMPLETADA'
        WHERE id_cita = NEW.id_cita;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- 2. Eliminar el trigger si ya existía para evitar duplicados
DROP TRIGGER IF EXISTS trg_completar_cita_tras_consulta ON consulta;

-- 3. Crear el trigger vinculado a la tabla consulta
CREATE TRIGGER trg_completar_cita_tras_consulta
AFTER INSERT ON consulta
FOR EACH ROW
EXECUTE FUNCTION fn_completar_cita_tras_consulta();



-- en caso que se quieran eliminar el trigger

-- 1. Eliminar el trigger de la tabla consulta
-- DROP TRIGGER IF EXISTS trg_completar_cita_tras_consulta ON consulta;

-- 2. Eliminar la función que ejecutaba la lógica
-- DROP FUNCTION IF EXISTS fn_completar_cita_tras_consulta();