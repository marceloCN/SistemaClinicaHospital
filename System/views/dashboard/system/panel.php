<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-id-badge mr-1"></i> Bienvenido al panel</h3>
            </div>
            <div class="card-body">
                <h5 class="mb-1">Hola, <?= e($nombre) ?></h5>
                <p class="text-muted mb-3">Has iniciado sesión en el sistema.</p>

                <a href="<?= \lib\Route::route('system') ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-tachometer-alt mr-1"></i> Ir al resumen del sistema
                </a>
            </div>
        </div>
    </div>
</div>
