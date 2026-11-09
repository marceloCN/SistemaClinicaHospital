-- =============================================================================
-- SISTEMA WEB DE GESTIÓN DE CITAS MÉDICAS
-- Clínica Vida Salud - Examen Parcial II - Sistemas 1
-- Base de datos optimizada para PostgreSQL 12+
-- =============================================================================
-- NOTA IMPORTANTE:
-- El framework subyacente crea sus propias tablas base (settings, visits, users,
-- system_settings) desde config/setup.php. Este script modela el dominio del
-- examen (personas, médicos, citas, etc.). Si deseas integrar ambos mundos,
-- vincula la tabla users del framework con cuenta_persona o persona.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 0. LIMPIEZA SEGURA (descomentar solo si se ejecuta en una base de datos de
--    desarrollo vacía; en producción eliminar esta sección).
-- -----------------------------------------------------------------------------
/*
DROP TABLE IF EXISTS bitacora_acciones CASCADE;
DROP TABLE IF EXISTS bitacora_accesos CASCADE;
DROP TABLE IF EXISTS configuracion_sistema CASCADE;
DROP TABLE IF EXISTS medicamento_paciente CASCADE;
DROP TABLE IF EXISTS antecedente_medico CASCADE;
DROP TABLE IF EXISTS alergia CASCADE;
DROP TABLE IF EXISTS perfil_medico CASCADE;
DROP TABLE IF EXISTS expediente_clinico CASCADE;
DROP TABLE IF EXISTS estudio_solicitado CASCADE;
DROP TABLE IF EXISTS tipo_estudio CASCADE;
DROP TABLE IF EXISTS diagnostico CASCADE;
DROP TABLE IF EXISTS consulta_medica CASCADE;
DROP TABLE IF EXISTS evaluacion_previa CASCADE;
DROP TABLE IF EXISTS reprogramacion_cita CASCADE;
DROP TABLE IF EXISTS recepcion_cita CASCADE;
DROP TABLE IF EXISTS cita CASCADE;
DROP TABLE IF EXISTS ficha_agenda CASCADE;
DROP TABLE IF EXISTS bloqueo_agenda CASCADE;
DROP TABLE IF EXISTS horario_atencion CASCADE;
DROP TABLE IF EXISTS servicio_medico CASCADE;
DROP TABLE IF EXISTS medico_especialidad CASCADE;
DROP TABLE IF EXISTS especialidad CASCADE;
DROP TABLE IF EXISTS medico CASCADE;
DROP TABLE IF EXISTS paciente CASCADE;
DROP TABLE IF EXISTS contacto_emergencia CASCADE;
DROP TABLE IF EXISTS cuenta_persona CASCADE;
DROP TABLE IF EXISTS persona CASCADE;
DROP TABLE IF EXISTS rol_permiso CASCADE;
DROP TABLE IF EXISTS permiso CASCADE;
DROP TABLE IF EXISTS rol CASCADE;
*/

-- =============================================================================
-- MÓDULO 1: SEGURIDAD, ROLES Y USUARIOS (Capa de Acceso)
-- =============================================================================

CREATE TABLE IF NOT EXISTS rol (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    clave VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS permiso (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    modulo VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS rol_permiso (
    id_rol INTEGER NOT NULL,
    id_permiso INTEGER NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (id_rol, id_permiso),
    FOREIGN KEY (id_rol) REFERENCES rol(id) ON DELETE CASCADE,
    FOREIGN KEY (id_permiso) REFERENCES permiso(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS persona (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    ci VARCHAR(15) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    telefono VARCHAR(15),
    direccion VARCHAR(255),
    correo VARCHAR(150),
    fecha_nacimiento DATE,
    sexo VARCHAR(10) CHECK (sexo IN ('MASCULINO', 'FEMENINO', 'OTRO')),
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_registro TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_rol INTEGER NOT NULL,
    FOREIGN KEY (id_rol) REFERENCES rol(id)
);

CREATE TABLE IF NOT EXISTS cuenta_persona (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_persona INTEGER NOT NULL UNIQUE,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    intentos_fallidos INTEGER NOT NULL DEFAULT 0,
    bloqueado BOOLEAN NOT NULL DEFAULT FALSE,
    bloqueado_hasta TIMESTAMPTZ NULL,
    fecha_creacion TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMPTZ NULL,
    remember_token VARCHAR(255),
    FOREIGN KEY (id_persona) REFERENCES persona(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS contacto_emergencia (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_paciente INTEGER NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    parentesco VARCHAR(50),
    telefono VARCHAR(15) NOT NULL,
    correo VARCHAR(150),
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_registro TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_paciente) REFERENCES persona(id) ON DELETE CASCADE
);

-- =============================================================================
-- MÓDULO 2: INFRAESTRUCTURA MÉDICA (Especialidades y Médicos)
-- =============================================================================

CREATE TABLE IF NOT EXISTS medico (
    id_persona INTEGER PRIMARY KEY REFERENCES persona(id) ON DELETE CASCADE,
    nro_colegiado VARCHAR(50) NOT NULL UNIQUE,
    fecha_ingreso DATE NOT NULL DEFAULT CURRENT_DATE,
    biografia TEXT,
    estado BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS especialidad (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    estado BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS medico_especialidad (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_medico INTEGER NOT NULL REFERENCES medico(id_persona) ON DELETE CASCADE,
    id_especialidad INTEGER NOT NULL REFERENCES especialidad(id) ON DELETE CASCADE,
    institucion_formadora VARCHAR(150),
    nro_certificado VARCHAR(80),
    fecha_obtencion DATE,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    UNIQUE (id_medico, id_especialidad)
);

-- Acreditación de un médico para ejercer una especialidad en la clínica.
CREATE TABLE IF NOT EXISTS servicio_medico (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_medico_especialidad INTEGER NOT NULL REFERENCES medico_especialidad(id) ON DELETE CASCADE,
    fecha_inicio DATE NOT NULL DEFAULT CURRENT_DATE,
    fecha_fin DATE,
    estado VARCHAR(15) NOT NULL DEFAULT 'ACTIVO' CHECK (estado IN ('ACTIVO', 'SUSPENDIDO', 'FINALIZADO')),
    observaciones TEXT,
    CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)
);

-- Impide tener dos acreditaciones activas para la misma especialidad de un médico.
CREATE UNIQUE INDEX IF NOT EXISTS uq_servicio_medico_activo
    ON servicio_medico(id_medico_especialidad)
    WHERE estado = 'ACTIVO';

CREATE TABLE IF NOT EXISTS horario_atencion (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_servicio_medico INTEGER NOT NULL REFERENCES servicio_medico(id) ON DELETE CASCADE,
    dia_semana SMALLINT NOT NULL CHECK (dia_semana BETWEEN 1 AND 7),
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    duracion_ficha SMALLINT NOT NULL CHECK (duracion_ficha > 0),
    fecha_desde DATE NOT NULL DEFAULT CURRENT_DATE,
    fecha_hasta DATE,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    CHECK (hora_inicio < hora_fin),
    CHECK (fecha_hasta IS NULL OR fecha_hasta >= fecha_desde)
);

CREATE TABLE IF NOT EXISTS bloqueo_agenda (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_servicio_medico INTEGER NOT NULL REFERENCES servicio_medico(id) ON DELETE CASCADE,
    fecha_hora_inicio TIMESTAMPTZ NOT NULL,
    fecha_hora_fin TIMESTAMPTZ NOT NULL,
    motivo VARCHAR(200),
    estado VARCHAR(15) NOT NULL DEFAULT 'ACTIVO' CHECK (estado IN ('ACTIVO', 'ANULADO')),
    fecha_registro TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario_registro INTEGER NOT NULL REFERENCES persona(id),
    CHECK (fecha_hora_fin > fecha_hora_inicio)
);

-- =============================================================================
-- MÓDULO 3: AGENDA Y CITAS MÉDICAS
-- =============================================================================

CREATE TABLE IF NOT EXISTS paciente (
    id_persona INTEGER PRIMARY KEY REFERENCES persona(id) ON DELETE CASCADE,
    fecha_alta DATE NOT NULL DEFAULT CURRENT_DATE,
    observaciones TEXT
);

-- Fichas (slots) generados a partir de los horarios de atención.
CREATE TABLE IF NOT EXISTS ficha_agenda (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_horario INTEGER NOT NULL REFERENCES horario_atencion(id) ON DELETE CASCADE,
    fecha DATE NOT NULL,
    fecha_hora_inicio TIMESTAMPTZ NOT NULL,
    fecha_hora_fin TIMESTAMPTZ NOT NULL,
    capacidad SMALLINT NOT NULL DEFAULT 1 CHECK (capacidad > 0),
    estado VARCHAR(15) NOT NULL DEFAULT 'ABIERTA' CHECK (estado IN ('ABIERTA', 'CERRADA', 'BLOQUEADA')),
    UNIQUE (id_horario, fecha, fecha_hora_inicio),
    CHECK (fecha_hora_fin > fecha_hora_inicio)
);

CREATE INDEX IF NOT EXISTS idx_ficha_agenda_fecha ON ficha_agenda(fecha);
CREATE INDEX IF NOT EXISTS idx_ficha_agenda_estado ON ficha_agenda(estado);

-- Estados de cita según el examen: Programada, Confirmada, Atendida, Cancelada, Inasistencia.
-- Se agregan estados intermedios usados por la clínica.
CREATE TABLE IF NOT EXISTS cita (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE,
    id_paciente INTEGER NOT NULL REFERENCES paciente(id_persona),
    id_ficha_agenda INTEGER NOT NULL REFERENCES ficha_agenda(id),
    estado VARCHAR(20) NOT NULL DEFAULT 'PROGRAMADA'
        CHECK (estado IN (
            'PROGRAMADA', 'CONFIRMADA', 'REPROGRAMADA', 'EN_RECEPCION',
            'EN_ATENCION', 'ATENDIDA', 'CANCELADA', 'INASISTENCIA'
        )),
    motivo_consulta TEXT,
    id_usuario_registro INTEGER NOT NULL REFERENCES persona(id),
    fecha_registro TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observaciones TEXT
);

CREATE INDEX IF NOT EXISTS idx_cita_paciente ON cita(id_paciente);
CREATE INDEX IF NOT EXISTS idx_cita_ficha ON cita(id_ficha_agenda);
CREATE INDEX IF NOT EXISTS idx_cita_estado ON cita(estado);
CREATE INDEX IF NOT EXISTS idx_cita_codigo ON cita(codigo);

CREATE TABLE IF NOT EXISTS recepcion_cita (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_cita INTEGER NOT NULL UNIQUE REFERENCES cita(id) ON DELETE CASCADE,
    fecha_hora_llegada TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario_recibe INTEGER NOT NULL REFERENCES persona(id),
    estado VARCHAR(25) NOT NULL DEFAULT 'EN_ESPERA'
        CHECK (estado IN (
            'EN_ESPERA', 'EN_EVALUACION', 'LISTO_PARA_CONSULTA', 'FINALIZADO'
        )),
    observaciones TEXT
);

CREATE TABLE IF NOT EXISTS reprogramacion_cita (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_cita INTEGER NOT NULL REFERENCES cita(id),
    id_ficha_anterior INTEGER NOT NULL REFERENCES ficha_agenda(id),
    id_ficha_nueva INTEGER NOT NULL REFERENCES ficha_agenda(id),
    id_usuario_reprograma INTEGER NOT NULL REFERENCES persona(id),
    fecha_reprogramacion TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motivo VARCHAR(250) NOT NULL,
    CHECK (id_ficha_anterior <> id_ficha_nueva)
);

-- =============================================================================
-- MÓDULO 4: HISTORIAL CLÍNICO (adicional al alcance mínimo del examen)
-- =============================================================================

CREATE TABLE IF NOT EXISTS evaluacion_previa (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_cita INTEGER NOT NULL UNIQUE REFERENCES cita(id) ON DELETE CASCADE,
    id_personal_registro INTEGER NOT NULL REFERENCES persona(id),
    fecha_hora TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motivo_referido TEXT,
    anamnesis_preliminar TEXT,
    presion_sistolica SMALLINT CHECK (presion_sistolica IS NULL OR presion_sistolica > 0),
    presion_diastolica SMALLINT CHECK (presion_diastolica IS NULL OR presion_diastolica > 0),
    frecuencia_cardiaca SMALLINT CHECK (frecuencia_cardiaca IS NULL OR frecuencia_cardiaca > 0),
    frecuencia_respiratoria SMALLINT CHECK (frecuencia_respiratoria IS NULL OR frecuencia_respiratoria > 0),
    temperatura NUMERIC(4,1) CHECK (temperatura IS NULL OR temperatura > 0),
    saturacion_oxigeno NUMERIC(5,2) CHECK (saturacion_oxigeno IS NULL OR saturacion_oxigeno BETWEEN 0 AND 100),
    peso_kg NUMERIC(6,2) CHECK (peso_kg IS NULL OR peso_kg > 0),
    talla_cm NUMERIC(6,2) CHECK (talla_cm IS NULL OR talla_cm > 0),
    observaciones TEXT,
    estado VARCHAR(20) NOT NULL DEFAULT 'COMPLETADA'
        CHECK (estado IN ('EN_CURSO', 'COMPLETADA', 'ANULADA'))
);

CREATE TABLE IF NOT EXISTS consulta_medica (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_cita INTEGER NOT NULL UNIQUE REFERENCES cita(id) ON DELETE CASCADE,
    fecha_inicio TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_fin TIMESTAMPTZ,
    anamnesis TEXT,
    examen_fisico TEXT,
    evolucion TEXT,
    plan_tratamiento TEXT,
    observaciones TEXT,
    estado VARCHAR(20) NOT NULL DEFAULT 'EN_CURSO'
        CHECK (estado IN ('EN_CURSO', 'FINALIZADA')),
    CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)
);

CREATE TABLE IF NOT EXISTS diagnostico (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_consulta INTEGER NOT NULL REFERENCES consulta_medica(id) ON DELETE CASCADE,
    descripcion TEXT NOT NULL,
    tipo VARCHAR(20) NOT NULL DEFAULT 'PRINCIPAL'
        CHECK (tipo IN ('PRINCIPAL', 'SECUNDARIO')),
    observaciones TEXT
);

CREATE TABLE IF NOT EXISTS tipo_estudio (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL UNIQUE,
    descripcion TEXT,
    estado BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS estudio_solicitado (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_consulta INTEGER NOT NULL REFERENCES consulta_medica(id) ON DELETE CASCADE,
    id_diagnostico INTEGER REFERENCES diagnostico(id) ON DELETE SET NULL,
    id_tipo_estudio INTEGER NOT NULL REFERENCES tipo_estudio(id),
    fecha_solicitud TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    indicacion TEXT,
    estado VARCHAR(20) NOT NULL DEFAULT 'SOLICITADO'
        CHECK (estado IN (
            'SOLICITADO', 'PROGRAMADO', 'EN_PROCESO', 'REALIZADO',
            'INFORMADO', 'CANCELADO'
        )),
    fecha_realizacion TIMESTAMPTZ,
    resultado TEXT,
    fecha_resultado TIMESTAMPTZ,
    observaciones TEXT,
    CHECK (fecha_realizacion IS NULL OR fecha_realizacion >= fecha_solicitud),
    CHECK (fecha_resultado IS NULL OR fecha_resultado >= fecha_solicitud)
);

CREATE TABLE IF NOT EXISTS expediente_clinico (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_paciente INTEGER NOT NULL UNIQUE REFERENCES paciente(id_persona) ON DELETE CASCADE,
    fecha_apertura TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observaciones_generales TEXT,
    estado BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS perfil_medico (
    id_expediente INTEGER PRIMARY KEY REFERENCES expediente_clinico(id) ON DELETE CASCADE,
    grupo_sanguineo VARCHAR(2) CHECK (grupo_sanguineo IN ('A', 'B', 'AB', 'O')),
    factor_rh CHAR(1) CHECK (factor_rh IN ('+', '-')),
    observaciones TEXT,
    fecha_actualizacion TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS alergia (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_expediente INTEGER NOT NULL REFERENCES expediente_clinico(id) ON DELETE CASCADE,
    sustancia VARCHAR(150) NOT NULL,
    tipo VARCHAR(30) NOT NULL
        CHECK (tipo IN ('MEDICAMENTO', 'ALIMENTO', 'AMBIENTAL', 'LATEX', 'OTRO')),
    reaccion TEXT,
    gravedad VARCHAR(20) CHECK (gravedad IN ('LEVE', 'MODERADA', 'GRAVE', 'DESCONOCIDA')),
    fecha_registro TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    observaciones TEXT,
    estado BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS antecedente_medico (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_expediente INTEGER NOT NULL REFERENCES expediente_clinico(id) ON DELETE CASCADE,
    tipo VARCHAR(30) NOT NULL
        CHECK (tipo IN (
            'ALERGIA', 'PERSONAL', 'FAMILIAR', 'QUIRURGICO',
            'HOSPITALIZACION', 'ENFERMEDAD_CRONICA', 'OTRO'
        )),
    descripcion TEXT NOT NULL,
    fecha_registro TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS medicamento_paciente (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_expediente INTEGER NOT NULL REFERENCES expediente_clinico(id) ON DELETE CASCADE,
    nombre_medicamento VARCHAR(150) NOT NULL,
    dosis VARCHAR(100),
    frecuencia VARCHAR(150),
    fecha_inicio DATE,
    fecha_fin DATE,
    prescrito_por VARCHAR(150),
    observaciones TEXT,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    CHECK (
        fecha_fin IS NULL
        OR fecha_inicio IS NULL
        OR fecha_fin >= fecha_inicio
    )
);

-- =============================================================================
-- MÓDULO 5: BITÁCORAS Y CONFIGURACIÓN DEL SISTEMA
-- =============================================================================

CREATE TABLE IF NOT EXISTS bitacora_accesos (
    id BIGSERIAL PRIMARY KEY,
    id_persona INTEGER REFERENCES persona(id) ON DELETE SET NULL,
    usuario_intentado VARCHAR(50),
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_hora TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(20) NOT NULL CHECK (estado IN ('EXITOSO', 'FALLIDO', 'CERRADO', 'BLOQUEADO')),
    detalle TEXT
);

CREATE INDEX IF NOT EXISTS idx_bitacora_accesos_persona ON bitacora_accesos(id_persona);
CREATE INDEX IF NOT EXISTS idx_bitacora_accesos_fecha ON bitacora_accesos(fecha_hora);
CREATE INDEX IF NOT EXISTS idx_bitacora_accesos_estado ON bitacora_accesos(estado);

CREATE TABLE IF NOT EXISTS bitacora_acciones (
    id BIGSERIAL PRIMARY KEY,
    id_persona INTEGER REFERENCES persona(id) ON DELETE SET NULL,
    tabla_afectada VARCHAR(50) NOT NULL,
    accion VARCHAR(50) NOT NULL,
    registro_id INTEGER,
    datos_anteriores JSONB,
    datos_nuevos JSONB,
    ip_address VARCHAR(45),
    user_agent TEXT,
    fecha_hora TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    detalle TEXT
);

CREATE INDEX IF NOT EXISTS idx_bitacora_acciones_persona ON bitacora_acciones(id_persona);
CREATE INDEX IF NOT EXISTS idx_bitacora_acciones_tabla ON bitacora_acciones(tabla_afectada);
CREATE INDEX IF NOT EXISTS idx_bitacora_acciones_fecha ON bitacora_acciones(fecha_hora);
CREATE INDEX IF NOT EXISTS idx_bitacora_acciones_registro ON bitacora_acciones(tabla_afectada, registro_id);

CREATE TABLE IF NOT EXISTS configuracion_sistema (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    tipo VARCHAR(20) NOT NULL DEFAULT 'string'
        CHECK (tipo IN ('string', 'integer', 'boolean', 'json', 'text')),
    descripcion TEXT,
    categoria VARCHAR(50) DEFAULT 'general',
    editable BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_actualizacion TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- =============================================================================
-- FUNCIONES Y PROCEDIMIENTOS ALMACENADOS
-- =============================================================================

-- Genera automáticamente el código único de cita (CM-000001, CM-000002, ...)
CREATE OR REPLACE FUNCTION generar_codigo_cita()
RETURNS TRIGGER AS $$
DECLARE
    v_numero INTEGER;
    v_codigo VARCHAR(20);
BEGIN
    IF NEW.codigo IS NOT NULL THEN
        RETURN NEW;
    END IF;

    SELECT COALESCE(MAX(CAST(SUBSTRING(codigo FROM 4) AS INTEGER)), 0) + 1
    INTO v_numero
    FROM cita
    WHERE codigo ~ '^CM-[0-9]+$';

    v_codigo := 'CM-' || LPAD(v_numero::TEXT, 6, '0');
    NEW.codigo := v_codigo;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_generar_codigo_cita ON cita;
CREATE TRIGGER trg_generar_codigo_cita
BEFORE INSERT ON cita
FOR EACH ROW
EXECUTE FUNCTION generar_codigo_cita();

-- Verifica si una ficha de agenda está disponible para asignar una cita.
CREATE OR REPLACE FUNCTION validar_ficha_disponible(p_id_ficha INTEGER)
RETURNS BOOLEAN AS $$
DECLARE
    v_estado VARCHAR(15);
    v_capacidad SMALLINT;
    v_ocupadas INTEGER;
    v_fecha DATE;
BEGIN
    SELECT estado, capacidad, fecha
    INTO v_estado, v_capacidad, v_fecha
    FROM ficha_agenda
    WHERE id = p_id_ficha;

    IF v_estado IS NULL THEN
        RETURN FALSE;
    END IF;

    IF v_estado != 'ABIERTA' OR v_fecha < CURRENT_DATE THEN
        RETURN FALSE;
    END IF;

    SELECT COUNT(*) INTO v_ocupadas
    FROM cita
    WHERE id_ficha_agenda = p_id_ficha
      AND estado NOT IN ('CANCELADA', 'INASISTENCIA');

    RETURN v_ocupadas < v_capacidad;
END;
$$ LANGUAGE plpgsql;

-- Registra una nueva cita verificando disponibilidad y bloqueos.
CREATE OR REPLACE PROCEDURE registrar_cita(
    p_id_paciente INTEGER,
    p_id_ficha_agenda INTEGER,
    p_motivo_consulta TEXT,
    p_id_usuario_registro INTEGER,
    p_observaciones TEXT,
    INOUT p_id_cita INTEGER DEFAULT NULL,
    INOUT p_codigo VARCHAR(20) DEFAULT NULL
)
LANGUAGE plpgsql AS $$
DECLARE
    v_id_servicio INTEGER;
    v_id_medico INTEGER;
    v_id_especialidad INTEGER;
    v_ficha_inicio TIMESTAMPTZ;
    v_ficha_fin TIMESTAMPTZ;
    v_bloqueos INTEGER;
BEGIN
    -- Verificar que la ficha exista y esté disponible
    IF NOT validar_ficha_disponible(p_id_ficha_agenda) THEN
        RAISE EXCEPTION 'La ficha de agenda no está disponible o ya expiró';
    END IF;

    -- Verificar que el paciente esté activo
    IF NOT EXISTS (
        SELECT 1
        FROM persona p
        JOIN paciente pa ON pa.id_persona = p.id
        WHERE pa.id_persona = p_id_paciente AND p.estado = TRUE
    ) THEN
        RAISE EXCEPTION 'El paciente no existe o está inactivo';
    END IF;

    -- Recuperar datos de la ficha para validar bloqueos
    SELECT fa.fecha_hora_inicio, fa.fecha_hora_fin, sm.id, me.id_medico, me.id_especialidad
    INTO v_ficha_inicio, v_ficha_fin, v_id_servicio, v_id_medico, v_id_especialidad
    FROM ficha_agenda fa
    JOIN horario_atencion ha ON ha.id = fa.id_horario
    JOIN servicio_medico sm ON sm.id = ha.id_servicio_medico
    JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
    WHERE fa.id = p_id_ficha_agenda;

    -- Verificar bloqueos de agenda activos
    SELECT COUNT(*) INTO v_bloqueos
    FROM bloqueo_agenda
    WHERE id_servicio_medico = v_id_servicio
      AND estado = 'ACTIVO'
      AND (
          (fecha_hora_inicio, fecha_hora_fin) OVERLAPS (v_ficha_inicio, v_ficha_fin)
      );

    IF v_bloqueos > 0 THEN
        RAISE EXCEPTION 'El horario se encuentra bloqueado para el servicio médico';
    END IF;

    -- Insertar la cita (el trigger genera el código)
    INSERT INTO cita (
        id_paciente, id_ficha_agenda, estado, motivo_consulta,
        id_usuario_registro, observaciones
    ) VALUES (
        p_id_paciente, p_id_ficha_agenda, 'PROGRAMADA', p_motivo_consulta,
        p_id_usuario_registro, p_observaciones
    )
    RETURNING id, codigo INTO p_id_cita, p_codigo;

    -- Marcar la ficha como cerrada si alcanza su capacidad
    UPDATE ficha_agenda
    SET estado = 'CERRADA'
    WHERE id = p_id_ficha_agenda
      AND (
          SELECT COUNT(*) FROM cita
          WHERE id_ficha_agenda = p_id_ficha_agenda
            AND estado NOT IN ('CANCELADA', 'INASISTENCIA')
      ) >= capacidad;

    -- Registrar en bitácora
    INSERT INTO bitacora_acciones (
        id_persona, tabla_afectada, accion, registro_id,
        datos_nuevos, detalle
    ) VALUES (
        p_id_usuario_registro, 'cita', 'INSERTAR', p_id_cita,
        jsonb_build_object(
            'id_paciente', p_id_paciente,
            'id_ficha_agenda', p_id_ficha_agenda,
            'estado', 'PROGRAMADA',
            'codigo', p_codigo
        ),
        'Registro de nueva cita médica'
    );
END;
$$;

-- Reprograma una cita a una nueva ficha de agenda.
CREATE OR REPLACE PROCEDURE reprogramar_cita(
    p_id_cita INTEGER,
    p_id_ficha_nueva INTEGER,
    p_motivo VARCHAR(250),
    p_id_usuario INTEGER
)
LANGUAGE plpgsql AS $$
DECLARE
    v_estado VARCHAR(20);
    v_id_ficha_anterior INTEGER;
    v_codigo VARCHAR(20);
BEGIN
    SELECT estado, id_ficha_agenda, codigo
    INTO v_estado, v_id_ficha_anterior, v_codigo
    FROM cita
    WHERE id = p_id_cita;

    IF v_estado IS NULL THEN
        RAISE EXCEPTION 'La cita no existe';
    END IF;

    IF v_estado IN ('ATENDIDA', 'CANCELADA', 'INASISTENCIA') THEN
        RAISE EXCEPTION 'No se puede reprogramar una cita en estado %', v_estado;
    END IF;

    IF v_id_ficha_anterior = p_id_ficha_nueva THEN
        RAISE EXCEPTION 'Debe seleccionar una ficha diferente a la actual';
    END IF;

    IF NOT validar_ficha_disponible(p_id_ficha_nueva) THEN
        RAISE EXCEPTION 'La nueva ficha de agenda no está disponible';
    END IF;

    -- Liberar ficha anterior
    UPDATE ficha_agenda SET estado = 'ABIERTA' WHERE id = v_id_ficha_anterior;

    -- Actualizar cita
    UPDATE cita
    SET id_ficha_agenda = p_id_ficha_nueva,
        estado = 'REPROGRAMADA',
        observaciones = COALESCE(observaciones || ' | Reprogramada: ' || p_motivo, 'Reprogramada: ' || p_motivo)
    WHERE id = p_id_cita;

    -- Cerrar nueva ficha si aplica
    UPDATE ficha_agenda
    SET estado = 'CERRADA'
    WHERE id = p_id_ficha_nueva
      AND (
          SELECT COUNT(*) FROM cita
          WHERE id_ficha_agenda = p_id_ficha_nueva
            AND estado NOT IN ('CANCELADA', 'INASISTENCIA')
      ) >= capacidad;

    -- Registrar histórico de reprogramación
    INSERT INTO reprogramacion_cita (
        id_cita, id_ficha_anterior, id_ficha_nueva,
        id_usuario_reprograma, motivo
    ) VALUES (
        p_id_cita, v_id_ficha_anterior, p_id_ficha_nueva,
        p_id_usuario, p_motivo
    );

    INSERT INTO bitacora_acciones (
        id_persona, tabla_afectada, accion, registro_id,
        datos_nuevos, detalle
    ) VALUES (
        p_id_usuario, 'cita', 'REPROGRAMAR', p_id_cita,
        jsonb_build_object(
            'id_ficha_anterior', v_id_ficha_anterior,
            'id_ficha_nueva', p_id_ficha_nueva,
            'estado', 'REPROGRAMADA',
            'motivo', p_motivo
        ),
        'Reprogramación de cita ' || v_codigo
    );
END;
$$;

-- Cancela una cita programada y libera su ficha.
CREATE OR REPLACE PROCEDURE cancelar_cita(
    p_id_cita INTEGER,
    p_motivo TEXT,
    p_id_usuario INTEGER
)
LANGUAGE plpgsql AS $$
DECLARE
    v_estado VARCHAR(20);
    v_id_ficha INTEGER;
    v_codigo VARCHAR(20);
BEGIN
    SELECT estado, id_ficha_agenda, codigo
    INTO v_estado, v_id_ficha, v_codigo
    FROM cita
    WHERE id = p_id_cita;

    IF v_estado IS NULL THEN
        RAISE EXCEPTION 'La cita no existe';
    END IF;

    IF v_estado IN ('ATENDIDA', 'CANCELADA') THEN
        RAISE EXCEPTION 'No se puede cancelar una cita en estado %', v_estado;
    END IF;

    UPDATE cita
    SET estado = 'CANCELADA',
        observaciones = COALESCE(
            observaciones || ' | Motivo cancelación: ' || p_motivo,
            'Motivo cancelación: ' || p_motivo
        )
    WHERE id = p_id_cita;

    UPDATE ficha_agenda SET estado = 'ABIERTA' WHERE id = v_id_ficha;

    INSERT INTO bitacora_acciones (
        id_persona, tabla_afectada, accion, registro_id,
        datos_nuevos, detalle
    ) VALUES (
        p_id_usuario, 'cita', 'CANCELAR', p_id_cita,
        jsonb_build_object('estado', 'CANCELADA', 'motivo', p_motivo),
        'Cancelación de cita ' || v_codigo
    );
END;
$$;

-- Cambia el estado de una cita respetando la máquina de estados del negocio.
CREATE OR REPLACE PROCEDURE cambiar_estado_cita(
    p_id_cita INTEGER,
    p_nuevo_estado VARCHAR(20),
    p_id_usuario INTEGER
)
LANGUAGE plpgsql AS $$
DECLARE
    v_estado_actual VARCHAR(20);
    v_codigo VARCHAR(20);
BEGIN
    SELECT estado, codigo INTO v_estado_actual, v_codigo
    FROM cita WHERE id = p_id_cita;

    IF v_estado_actual IS NULL THEN
        RAISE EXCEPTION 'Cita no encontrada';
    END IF;

    -- Validar transiciones válidas
    IF v_estado_actual = 'CANCELADA' AND p_nuevo_estado != 'CANCELADA' THEN
        RAISE EXCEPTION 'No se puede cambiar el estado de una cita cancelada';
    END IF;

    IF v_estado_actual = 'ATENDIDA' AND p_nuevo_estado != 'ATENDIDA' THEN
        RAISE EXCEPTION 'No se puede cambiar el estado de una cita atendida';
    END IF;

    UPDATE cita SET estado = p_nuevo_estado WHERE id = p_id_cita;

    INSERT INTO bitacora_acciones (
        id_persona, tabla_afectada, accion, registro_id,
        datos_anteriores, datos_nuevos, detalle
    ) VALUES (
        p_id_usuario, 'cita', 'CAMBIAR_ESTADO', p_id_cita,
        jsonb_build_object('estado', v_estado_actual),
        jsonb_build_object('estado', p_nuevo_estado),
        'Cambio de estado de cita ' || v_codigo
    );
END;
$$;

-- Registra un intento de acceso al sistema.
CREATE OR REPLACE PROCEDURE registrar_acceso(
    p_id_persona INTEGER,
    p_usuario_intentado VARCHAR(50),
    p_ip_address VARCHAR(45),
    p_user_agent TEXT,
    p_estado VARCHAR(20),
    p_detalle TEXT
)
LANGUAGE plpgsql AS $$
BEGIN
    INSERT INTO bitacora_accesos (
        id_persona, usuario_intentado, ip_address, user_agent,
        estado, detalle
    ) VALUES (
        p_id_persona, p_usuario_intentado, p_ip_address, p_user_agent,
        p_estado, p_detalle
    );

    IF p_estado = 'EXITOSO' AND p_id_persona IS NOT NULL THEN
        UPDATE cuenta_persona
        SET intentos_fallidos = 0,
            bloqueado = FALSE,
            bloqueado_hasta = NULL,
            ultimo_acceso = CURRENT_TIMESTAMP
        WHERE id_persona = p_id_persona;
    ELSIF p_estado = 'FALLIDO' AND p_usuario_intentado IS NOT NULL THEN
        UPDATE cuenta_persona
        SET intentos_fallidos = intentos_fallidos + 1,
            bloqueado = CASE WHEN intentos_fallidos + 1 >= 5 THEN TRUE ELSE bloqueado END,
            bloqueado_hasta = CASE WHEN intentos_fallidos + 1 >= 5 THEN CURRENT_TIMESTAMP + INTERVAL '30 minutes' ELSE bloqueado_hasta END
        WHERE usuario = p_usuario_intentado;
    END IF;
END;
$$;

-- Registra manualmente una acción en la bitácora de acciones.
CREATE OR REPLACE PROCEDURE registrar_bitacora_accion(
    p_id_persona INTEGER,
    p_tabla_afectada VARCHAR(50),
    p_accion VARCHAR(50),
    p_registro_id INTEGER,
    p_datos_anteriores JSONB,
    p_datos_nuevos JSONB,
    p_ip_address VARCHAR(45),
    p_user_agent TEXT,
    p_detalle TEXT
)
LANGUAGE plpgsql AS $$
BEGIN
    INSERT INTO bitacora_acciones (
        id_persona, tabla_afectada, accion, registro_id,
        datos_anteriores, datos_nuevos, ip_address, user_agent, detalle
    ) VALUES (
        p_id_persona, p_tabla_afectada, p_accion, p_registro_id,
        p_datos_anteriores, p_datos_nuevos, p_ip_address, p_user_agent, p_detalle
    );
END;
$$;

-- Retorna la agenda de un médico para una fecha específica.
CREATE OR REPLACE FUNCTION obtener_agenda_medico(p_id_medico INTEGER, p_fecha DATE)
RETURNS TABLE (
    hora_inicio TIMESTAMPTZ,
    hora_fin TIMESTAMPTZ,
    codigo_cita VARCHAR(20),
    paciente_nombre VARCHAR,
    paciente_ci VARCHAR,
    especialidad VARCHAR,
    estado_cita VARCHAR(20),
    motivo_consulta TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        fa.fecha_hora_inicio,
        fa.fecha_hora_fin,
        c.codigo,
        (p.nombre || ' ' || p.apellido)::VARCHAR,
        p.ci,
        e.nombre,
        c.estado,
        c.motivo_consulta
    FROM cita c
    JOIN ficha_agenda fa ON fa.id = c.id_ficha_agenda
    JOIN horario_atencion ha ON ha.id = fa.id_horario
    JOIN servicio_medico sm ON sm.id = ha.id_servicio_medico
    JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
    JOIN especialidad e ON e.id = me.id_especialidad
    JOIN persona p ON p.id = c.id_paciente
    WHERE me.id_medico = p_id_medico
      AND DATE(fa.fecha_hora_inicio) = p_fecha
    ORDER BY fa.fecha_hora_inicio;
END;
$$ LANGUAGE plpgsql;

-- Retorna el historial completo de citas de un paciente.
CREATE OR REPLACE FUNCTION obtener_historial_paciente(p_id_paciente INTEGER)
RETURNS TABLE (
    fecha_hora_inicio TIMESTAMPTZ,
    fecha_hora_fin TIMESTAMPTZ,
    codigo_cita VARCHAR(20),
    estado_cita VARCHAR(20),
    medico_nombre VARCHAR,
    especialidad VARCHAR,
    motivo_consulta TEXT,
    fecha_registro TIMESTAMPTZ
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        fa.fecha_hora_inicio,
        fa.fecha_hora_fin,
        c.codigo,
        c.estado,
        (pm.nombre || ' ' || pm.apellido)::VARCHAR,
        e.nombre,
        c.motivo_consulta,
        c.fecha_registro
    FROM cita c
    JOIN ficha_agenda fa ON fa.id = c.id_ficha_agenda
    JOIN horario_atencion ha ON ha.id = fa.id_horario
    JOIN servicio_medico sm ON sm.id = ha.id_servicio_medico
    JOIN medico_especialidad me ON me.id = sm.id_medico_especialidad
    JOIN persona pm ON pm.id = me.id_medico
    JOIN especialidad e ON e.id = me.id_especialidad
    WHERE c.id_paciente = p_id_paciente
    ORDER BY fa.fecha_hora_inicio DESC;
END;
$$ LANGUAGE plpgsql;

-- Genera automáticamente las fichas (slots) de un horario de atención para una
-- fecha determinada. Útil para ser llamado desde PHP o un job programado.
CREATE OR REPLACE PROCEDURE generar_fichas_horario(
    p_id_horario INTEGER,
    p_fecha DATE
)
LANGUAGE plpgsql AS $$
DECLARE
    v_hora_inicio TIME;
    v_hora_fin TIME;
    v_duracion SMALLINT;
    v_id_servicio INTEGER;
    v_dia_semana SMALLINT;
    v_slot_inicio TIMESTAMPTZ;
    v_slot_fin TIMESTAMPTZ;
BEGIN
    SELECT ha.hora_inicio, ha.hora_fin, ha.duracion_ficha, ha.id_servicio_medico, ha.dia_semana
    INTO v_hora_inicio, v_hora_fin, v_duracion, v_id_servicio, v_dia_semana
    FROM horario_atencion ha
    WHERE ha.id = p_id_horario;

    IF v_hora_inicio IS NULL THEN
        RAISE EXCEPTION 'El horario de atención no existe';
    END IF;

    IF EXTRACT(DOW FROM p_fecha) + 1 != v_dia_semana THEN
        RAISE EXCEPTION 'La fecha no corresponde al día de la semana del horario';
    END IF;

    v_slot_inicio := p_fecha + v_hora_inicio;

    WHILE (v_slot_inicio::TIME) < v_hora_fin LOOP
        v_slot_fin := v_slot_inicio + (v_duracion || ' minutes')::INTERVAL;

        IF v_slot_fin::TIME <= v_hora_fin THEN
            INSERT INTO ficha_agenda (
                id_horario, fecha, fecha_hora_inicio, fecha_hora_fin, capacidad, estado
            ) VALUES (
                p_id_horario, p_fecha, v_slot_inicio, v_slot_fin, 1, 'ABIERTA'
            )
            ON CONFLICT (id_horario, fecha, fecha_hora_inicio) DO NOTHING;
        END IF;

        v_slot_inicio := v_slot_fin;
    END LOOP;
END;
$$;

-- =============================================================================
-- DATOS INICIALES
-- =============================================================================

-- Roles del sistema
INSERT INTO rol (clave, nombre, descripcion) VALUES
('ADMIN', 'Administrador', 'Configura y supervisa el sistema'),
('RECEP', 'Recepcionista', 'Gestiona citas y pacientes'),
('MEDICO', 'Médico', 'Atiende consultas y revisa agendas'),
('PAC', 'Paciente', 'Usuario externo del sistema de citas')
ON CONFLICT (clave) DO NOTHING;

-- Permisos básicos
INSERT INTO permiso (clave, nombre, modulo) VALUES
('pacientes.ver', 'Ver pacientes', 'pacientes'),
('pacientes.crear', 'Crear pacientes', 'pacientes'),
('pacientes.editar', 'Editar pacientes', 'pacientes'),
('medicos.ver', 'Ver médicos', 'medicos'),
('medicos.crear', 'Crear médicos', 'medicos'),
('medicos.editar', 'Editar médicos', 'medicos'),
('especialidades.ver', 'Ver especialidades', 'especialidades'),
('especialidades.crear', 'Crear especialidades', 'especialidades'),
('citas.ver', 'Ver citas', 'citas'),
('citas.crear', 'Crear citas', 'citas'),
('citas.reprogramar', 'Reprogramar citas', 'citas'),
('citas.cancelar', 'Cancelar citas', 'citas'),
('reportes.ver', 'Ver reportes', 'reportes'),
('usuarios.ver', 'Ver usuarios', 'usuarios'),
('usuarios.administrar', 'Administrar usuarios', 'usuarios'),
('configuracion.ver', 'Ver configuración', 'configuracion')
ON CONFLICT (clave) DO NOTHING;

-- Asignación de permisos a roles
INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT r.id, p.id
FROM rol r
CROSS JOIN permiso p
WHERE r.clave = 'ADMIN'
ON CONFLICT DO NOTHING;

INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT r.id, p.id
FROM rol r
JOIN permiso p ON p.clave IN (
    'pacientes.ver', 'pacientes.crear', 'pacientes.editar',
    'medicos.ver', 'citas.ver', 'citas.crear', 'citas.reprogramar',
    'citas.cancelar'
)
WHERE r.clave = 'RECEP'
ON CONFLICT DO NOTHING;

INSERT INTO rol_permiso (id_rol, id_permiso)
SELECT r.id, p.id
FROM rol r
JOIN permiso p ON p.clave IN ('citas.ver')
WHERE r.clave = 'MEDICO'
ON CONFLICT DO NOTHING;

-- Especialidades de ejemplo
INSERT INTO especialidad (nombre, descripcion) VALUES
('Medicina general', 'Atención médica general'),
('Pediatría', 'Atención médica infantil'),
('Ginecología', 'Salud reproductiva femenina'),
('Odontología', 'Salud dental y bucal'),
('Cardiología', 'Estudio y tratamiento del corazón')
ON CONFLICT (nombre) DO NOTHING;

-- Configuración de la página inicial y del sistema
INSERT INTO configuracion_sistema (clave, valor, tipo, descripcion, categoria, editable) VALUES
('clinica_nombre', 'Clínica Vida Salud', 'string', 'Nombre de la clínica mostrado en el sistema', 'general', TRUE),
('clinica_eslogan', 'Tu salud, nuestra prioridad', 'string', 'Eslogan de la clínica', 'general', TRUE),
('clinica_direccion', 'Av. Principal #123, Ciudad', 'string', 'Dirección física de la clínica', 'general', TRUE),
('clinica_telefono', '2-1234567', 'string', 'Teléfono de contacto', 'general', TRUE),
('clinica_correo', 'contacto@vidasalud.com', 'string', 'Correo de contacto', 'general', TRUE),
('pagina_inicial_titulo', 'Bienvenido a la Clínica Vida Salud', 'string', 'Título principal de la página de inicio pública', 'pagina_inicial', TRUE),
('pagina_inicial_subtitulo', 'Gestiona tus citas médicas de forma rápida y segura', 'string', 'Subtítulo de la página de inicio pública', 'pagina_inicial', TRUE),
('pagina_inicial_descripcion', 'Ofrecemos atención médica especializada en múltiples áreas. Agenda tu cita con nuestros profesionales.', 'text', 'Descripción extendida de la página de inicio pública', 'pagina_inicial', TRUE),
('pagina_inicial_imagen_fondo', 'assets/img/hero-clinica.jpg', 'string', 'Imagen de fondo del hero de la página inicial', 'pagina_inicial', TRUE),
('tema_color_primario', '#008040', 'string', 'Color primario del tema de la página pública', 'apariencia', TRUE),
('tema_color_secundario', '#0a3d2e', 'string', 'Color secundario usado en degradados y fondos', 'apariencia', TRUE),
('tema_preset', 'verde-esmeralda', 'string', 'Preset de colores seleccionado en la configuración', 'apariencia', TRUE),
('inicio_servicios', '[{"icon":"fa-stethoscope","title":"Consulta médica especializada","desc":"Evaluación integral con especialistas certificados y seguimiento personalizado."},{"icon":"fa-heartbeat","title":"Cardiología y chequeos","desc":"Estudios preventivos y control cardiovascular con tecnología de precisión."},{"icon":"fa-child","title":"Pediatría y control infantil","desc":"Acompañamiento cercano para el crecimiento y bienestar de los más pequeños."},{"icon":"fa-flask","title":"Laboratorio clínico","desc":"Resultados confiables y oportunos para respaldar cada diagnóstico."},{"icon":"fa-x-ray","title":"Diagnóstico por imagen","desc":"Equipos modernos y lectura especializada para una evaluación precisa."},{"icon":"fa-briefcase-medical","title":"Salud ocupacional","desc":"Programas de prevención y bienestar para empresas y colaboradores."}]', 'json', 'Servicios mostrados en la página de inicio', 'pagina_inicial', TRUE),
('inicio_especialidades', '[]', 'json', 'Especialidades de servicio_medico visibles en el inicio (vacío = todas)', 'pagina_inicial', TRUE),
('inicio_medicos_destacados', '[]', 'json', 'Médicos con servicio activo destacados en el inicio', 'pagina_inicial', TRUE),
('inicio_medicos_cantidad', '3', 'integer', 'Cantidad de médicos a mostrar en la página de inicio', 'pagina_inicial', TRUE),
('inicio_mostrar_especialidades', 'true', 'boolean', 'Mostrar la sección de especialidades en el inicio', 'pagina_inicial', TRUE),
('inicio_mostrar_medicos', 'true', 'boolean', 'Mostrar la sección de médicos en el inicio', 'pagina_inicial', TRUE),
('inicio_anios_experiencia', '25', 'integer', 'Años de experiencia mostrados en las estadísticas del inicio', 'pagina_inicial', TRUE),
('clinica_emergencias', '24/7', 'string', 'Texto o número de atención de emergencias', 'general', TRUE),
('clinica_horario', 'Lunes a viernes · 08:00 a 20:00', 'string', 'Horario de atención de lunes a viernes', 'general', TRUE),
('clinica_horario_sabado', 'Sábados · 08:00 a 13:00', 'string', 'Horario de atención de los sábados', 'general', TRUE),
('duracion_cita_minutos_default', '30', 'integer', 'Duración por defecto de cada cita en minutos', 'citas', TRUE),
('hora_inicio_atencion_default', '08:00', 'string', 'Hora de inicio de atención por defecto', 'citas', TRUE),
('hora_fin_atencion_default', '18:00', 'string', 'Hora de fin de atención por defecto', 'citas', TRUE)
ON CONFLICT (clave) DO NOTHING;

-- Servicios médicos iniciales a partir de las acreditaciones activas.
-- Si un médico ya tiene un servicio activo, no se duplica.
INSERT INTO servicio_medico (id_medico_especialidad, fecha_inicio, estado)
SELECT me.id, CURRENT_DATE, 'ACTIVO'
FROM medico_especialidad me
WHERE me.estado = TRUE
  AND NOT EXISTS (
      SELECT 1
      FROM servicio_medico sm
      WHERE sm.id_medico_especialidad = me.id
        AND sm.estado = 'ACTIVO'
  );

-- Tabla para tokens de recuperación de contraseña
CREATE TABLE IF NOT EXISTS recuperacion_password (
    id INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_cuenta_persona INTEGER NOT NULL REFERENCES cuenta_persona(id) ON DELETE CASCADE,
    token VARCHAR(255) NOT NULL UNIQUE,
    estado VARCHAR(20) NOT NULL DEFAULT 'ACTIVO' CHECK (estado IN ('ACTIVO', 'USADO', 'EXPIRADO')),
    fecha_expiracion TIMESTAMPTZ NOT NULL,
    fecha_uso TIMESTAMPTZ NULL,
    fecha_registro TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_recuperacion_token ON recuperacion_password(token);
CREATE INDEX IF NOT EXISTS idx_recuperacion_estado ON recuperacion_password(estado);

-- Usuario administrador inicial
INSERT INTO persona (ci, nombre, apellido, telefono, correo, direccion, sexo, id_rol)
VALUES ('0000000', 'Administrador', 'del Sistema', '0000000', 'admin@vidasalud.com', 'Clínica Vida Salud', 'OTRO',
    (SELECT id FROM rol WHERE clave = 'ADMIN'))
ON CONFLICT (ci) DO NOTHING;

-- usuario: admin    password: admin123
INSERT INTO cuenta_persona (id_persona, usuario, correo, password_hash)
SELECT p.id, 'admin', 'admin@vidasalud.com', '$2y$10$xnFhr75YSCoL/GEQghlNg.Yfpj9Y0y4/r448nuUGpVoRpFNzyxYdy'
FROM persona p
WHERE p.ci = '0000000'
ON CONFLICT (usuario) DO NOTHING;

-- =============================================================================
-- PROCEDIMIENTOS ALMACENADOS PARA BITÁCORAS
-- =============================================================================

-- ---------------------------------------------------------------------------
-- SP: bitacora_accesos
-- ---------------------------------------------------------------------------
DROP FUNCTION IF EXISTS sp_bitacora_acceso_insertar(INT, VARCHAR, VARCHAR, TEXT, VARCHAR, TEXT);

CREATE OR REPLACE FUNCTION sp_bitacora_acceso_insertar(
    p_id_persona INT,
    p_usuario_intentado VARCHAR(50),
    p_ip_address VARCHAR(45),
    p_user_agent TEXT,
    p_estado VARCHAR(20),
    p_detalle TEXT
)
RETURNS VOID
LANGUAGE plpgsql
AS $$
BEGIN
    INSERT INTO bitacora_accesos (
        id_persona, usuario_intentado, ip_address, user_agent, estado, detalle, fecha_hora
    ) VALUES (
        p_id_persona,
        p_usuario_intentado,
        p_ip_address,
        p_user_agent,
        UPPER(TRIM(p_estado)),
        p_detalle,
        CURRENT_TIMESTAMP
    );
END;
$$;

COMMENT ON FUNCTION sp_bitacora_acceso_insertar IS
    'Inserta un registro en la tabla bitacora_accesos.';

-- ---------------------------------------------------------------------------
-- SP: bitacora_acciones
-- ---------------------------------------------------------------------------
DROP FUNCTION IF EXISTS sp_bitacora_accion_insertar(INT, VARCHAR, VARCHAR, INT, JSONB, JSONB, VARCHAR, TEXT, TEXT);

CREATE OR REPLACE FUNCTION sp_bitacora_accion_insertar(
    p_id_persona INT,
    p_tabla_afectada VARCHAR(50),
    p_accion VARCHAR(50),
    p_registro_id INT,
    p_datos_anteriores JSONB,
    p_datos_nuevos JSONB,
    p_ip_address VARCHAR(45),
    p_user_agent TEXT,
    p_detalle TEXT
)
RETURNS VOID
LANGUAGE plpgsql
AS $$
BEGIN
    INSERT INTO bitacora_acciones (
        id_persona, tabla_afectada, accion, registro_id,
        datos_anteriores, datos_nuevos, ip_address, user_agent, detalle, fecha_hora
    ) VALUES (
        p_id_persona,
        p_tabla_afectada,
        UPPER(TRIM(p_accion)),
        p_registro_id,
        p_datos_anteriores,
        p_datos_nuevos,
        p_ip_address,
        p_user_agent,
        p_detalle,
        CURRENT_TIMESTAMP
    );
END;
$$;

COMMENT ON FUNCTION sp_bitacora_accion_insertar IS
    'Inserta un registro en la tabla bitacora_acciones.';

-- ---------------------------------------------------------------------------
-- SP: portal - gestión de intentos + bitácora de accesos
-- ---------------------------------------------------------------------------
DROP FUNCTION IF EXISTS sp_portal_registrar_acceso(INT, INT, VARCHAR, VARCHAR, TEXT, VARCHAR, TEXT, INT, INT);

CREATE OR REPLACE FUNCTION sp_portal_registrar_acceso(
    p_id_cuenta_persona INT,
    p_id_persona INT,
    p_usuario_intentado VARCHAR(50),
    p_ip_address VARCHAR(45),
    p_user_agent TEXT,
    p_estado VARCHAR(20),
    p_detalle TEXT,
    p_intentos_maximos INT DEFAULT 3,
    p_minutos_bloqueo INT DEFAULT 30
)
RETURNS INT
LANGUAGE plpgsql
AS $$
DECLARE
    v_estado VARCHAR(20);
    v_intentos_actuales INT := 0;
BEGIN
    v_estado := UPPER(TRIM(p_estado));

    PERFORM sp_bitacora_acceso_insertar(
        p_id_persona,
        p_usuario_intentado,
        p_ip_address,
        p_user_agent,
        v_estado,
        p_detalle
    );

    IF p_id_cuenta_persona IS NULL THEN
        RETURN 0;
    END IF;

    IF v_estado = 'FALLIDO' THEN
        UPDATE cuenta_persona
        SET intentos_fallidos = intentos_fallidos + 1
        WHERE id = p_id_cuenta_persona
        RETURNING intentos_fallidos INTO v_intentos_actuales;

        IF v_intentos_actuales >= p_intentos_maximos THEN
            UPDATE cuenta_persona
            SET bloqueado = TRUE,
                bloqueado_hasta = CURRENT_TIMESTAMP + (p_minutos_bloqueo || ' minutes')::INTERVAL
            WHERE id = p_id_cuenta_persona;
        END IF;

    ELSIF v_estado = 'EXITOSO' THEN
        UPDATE cuenta_persona
        SET intentos_fallidos = 0,
            bloqueado = FALSE,
            bloqueado_hasta = NULL,
            ultimo_acceso = CURRENT_TIMESTAMP
        WHERE id = p_id_cuenta_persona;

        v_intentos_actuales := 0;
    ELSE
        SELECT intentos_fallidos INTO v_intentos_actuales
        FROM cuenta_persona
        WHERE id = p_id_cuenta_persona;
    END IF;

    RETURN COALESCE(v_intentos_actuales, 0);
END;
$$;

COMMENT ON FUNCTION sp_portal_registrar_acceso IS
    'Gestiona intentos_fallidos/bloqueo de cuenta_persona y registra la bitácora de accesos.';
