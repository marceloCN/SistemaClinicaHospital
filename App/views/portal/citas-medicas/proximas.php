<?php
/**
 * Próximas citas del paciente (pantalla de inicio).
 */
$proximas = $proximas ?? [];

$formatoFecha = static function (string $valor): string {
    if ($valor === '') {
        return '—';
    }

    return substr($valor, 8, 2) . '/' . substr($valor, 5, 2) . '/' . substr($valor, 0, 4)
        . ' ' . substr($valor, 11, 5);
};

$claseEstado = static function (string $estado): string {
    return match ($estado) {
        'PROGRAMADA' => 'badge-info',
        'CONFIRMADA' => 'badge-primary',
        'REPROGRAMADA' => 'badge-warning',
        'EN_RECEPCION', 'EN_ATENCION' => 'badge-secondary',
        'ATENDIDA' => 'badge-success',
        'CANCELADA' => 'badge-danger',
        'INASISTENCIA' => 'badge-dark',
        default => 'badge-light',
    };
};
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="m-0">Próximas citas</h5>
    <small class="text-muted">Tus turnos activos más cercanos</small>
</div>

<div class="cm-tabla mb-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th scope="col">Fecha y hora</th>
                    <th scope="col">Médico</th>
                    <th scope="col">Especialidad</th>
                    <th scope="col">Estado</th>
                    <th scope="col" class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($proximas === []) { ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            <i class="fas fa-inbox"></i> No tienes citas próximas.
                            <button type="button" class="btn btn-sm btn-link js-nueva-cita">Generar una nueva cita</button>
                        </td>
                    </tr>
                <?php } ?>
                <?php foreach ($proximas as $cita) { ?>
                    <tr>
                        <td class="cm-hora"><?= e($formatoFecha((string) $cita['fecha_hora_inicio'])) ?></td>
                        <td>
                            <div class="cm-nombre">
                                <?= e((string) $cita['medico']) ?>
                                <small>Colegiado <?= e((string) $cita['nro_colegiado']) ?></small>
                            </div>
                        </td>
                        <td><?= e((string) $cita['especialidad']) ?></td>
                        <td>
                            <span class="badge <?= e($claseEstado((string) $cita['estado'])) ?>">
                                <?= e((string) $cita['estado']) ?>
                            </span>
                        </td>
                        <td class="text-right text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary js-detalle-cita"
                                data-id="<?= (int) $cita['id'] ?>">
                                <i class="fas fa-eye"></i> Detalle
                            </button>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
