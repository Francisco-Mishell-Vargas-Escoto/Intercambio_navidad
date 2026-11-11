<?php
/**
 * Archivo: app/views/intercambio/ver.php
 * Página principal de un intercambio: datos, cuenta regresiva, participantes y compartir.
 * Variables: $i, $esAdmin, $participante, $participantes, $enlace
 */
[$estadoTexto, $estadoColor] = estado_intercambio($i);
$codigoBonito = CodigoService::formatear($i['codigo_acceso']);
$textoCompartir = '¡Únete a mi intercambio navideño "' . $i['nombre'] . '"! Código: ' . $codigoBonito . ' · ' . $enlace;
?>
<!-- ===== Encabezado ===== -->
<section class="hero hero-intercambio text-center">
    <div class="container">
        <div class="d-flex justify-content-center flex-wrap gap-2 mb-2">
            <span class="badge text-bg-<?= $estadoColor ?>"><?= e($estadoTexto) ?></span>
            <?php if ($esAdmin): ?><span class="badge bg-dorado"><i class="bi bi-star-fill"></i> Eres el organizador</span><?php endif; ?>
        </div>
        <h1 class="titulo-festivo mb-2"><?= e($i['nombre']) ?></h1>
        <p class="lead mb-0"><?= nl2br(e($i['descripcion'] ?: '¡Bienvenidos a nuestro intercambio navideño!')) ?></p>
        <p class="small text-muted mt-2 mb-0">Organiza: <?= e($i['admin_nombre']) ?></p>
        <?php if ($esAdmin): ?>
            <a href="<?= url('intercambios/' . (int)$i['id'] . '/admin') ?>" class="btn btn-navidad mt-3">
                <i class="bi bi-speedometer2"></i> Panel de administración
            </a>
        <?php endif; ?>
    </div>
</section>

<section class="container mt-n5 position-relative">
    <div class="row g-4">
        <!-- ===== Columna principal ===== -->
        <div class="col-lg-8">
            <div class="card tarjeta-intercambio mb-4">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-sm-6 col-xl-4">
                            <div class="dato"><i class="bi bi-calendar-event"></i>
                                <small>Fecha del intercambio</small><strong><?= e(fecha_hora($i['fecha_evento'])) ?></strong></div>
                        </div>
                        <div class="col-sm-6 col-xl-4">
                            <div class="dato"><i class="bi bi-cash-coin"></i>
                                <small>Presupuesto</small><strong><?= e(presupuesto($i)) ?></strong></div>
                        </div>
                        <div class="col-sm-6 col-xl-4">
                            <div class="dato"><i class="bi bi-people"></i>
                                <small>Participantes</small><strong><?= (int)$i['total_participantes'] ?></strong></div>
                        </div>
                        <div class="col-sm-6 col-xl-6">
                            <div class="dato"><i class="bi bi-geo-alt"></i>
                                <small>Lugar</small><strong><?= e($i['lugar'] ?: 'Por definir') ?></strong></div>
                        </div>
                        <div class="col-sm-12 col-xl-6">
                            <div class="dato"><i class="bi bi-hourglass-split"></i>
                                <small>Registro hasta</small><strong><?= e(fecha_hora($i['fecha_limite'])) ?></strong></div>
                        </div>
                    </div>

                    <div class="cuenta-regresiva mt-4" data-fecha="<?= fecha_js($i['fecha_evento']) ?>">
                        <div><span data-unidad="dias">0</span><small>días</small></div>
                        <div><span data-unidad="horas">0</span><small>horas</small></div>
                        <div><span data-unidad="minutos">0</span><small>min</small></div>
                        <div><span data-unidad="segundos">0</span><small>seg</small></div>
                    </div>
                </div>
            </div>

            <!-- Mi participación: confirmación + acceso a perfil y lista de deseos -->
            <?php if ($participante): ?>
                <div class="card tarjeta-intercambio mb-4 <?= $participante['confirmado'] ? 'tarjeta-confirmada' : '' ?>">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <h2 class="h5 mb-1">Mi participación</h2>
                                <p class="small text-muted mb-0">
                                    <?php if ($participante['confirmado']): ?>
                                        <i class="bi bi-check-circle-fill text-pino"></i> Confirmaste tu asistencia
                                    <?php else: ?>
                                        <i class="bi bi-exclamation-circle text-navidad"></i> Aún no confirmas tu asistencia
                                    <?php endif; ?>
                                    · <?= (int)$misDeseos ?> idea(s) en tu lista de deseos
                                </p>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php if (!$participante['confirmado'] && !in_array($i['estado'], ['sorteado', 'revelado'], true)): ?>
                                    <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/confirmar') ?>">
                                        <?= Csrf::campo() ?>
                                        <input type="hidden" name="confirmado" value="1">
                                        <button class="btn btn-navidad"><i class="bi bi-check2"></i> Confirmar</button>
                                    </form>
                                <?php endif; ?>
                                <a href="<?= url('intercambios/' . (int)$i['id'] . '/perfil') ?>" class="btn btn-pino">
                                    <i class="bi bi-gift"></i> Mi perfil y deseos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Amigo secreto (se activa en los módulos 6 y 7) -->
            <?php if ($participante): ?>
                <div class="card tarjeta-intercambio tarjeta-secreto mb-4">
                    <div class="card-body p-4 d-flex align-items-center gap-3">
                        <div class="icono-form flex-shrink-0"><i class="bi bi-incognito"></i></div>
                        <div>
                            <h2 class="h5 mb-1">Tu amigo secreto</h2>
                            <?php if ($i['estado'] === 'sorteado'): ?>
                                <p class="text-muted mb-0">¡El sorteo ya se realizó! Espera a que el organizador publique los resultados
                                    para descubrir a quién le regalas.</p>
                            <?php elseif ($i['estado'] === 'revelado'): ?>
                                <p class="text-muted mb-3">¡Los resultados ya están publicados! Descubre a quién le regalas y mira su lista de deseos.</p>
                                <a href="<?= url('intercambios/' . (int)$i['id'] . '/mi-amigo-secreto') ?>" class="btn btn-navidad btn-descubrir">
                                    <i class="bi bi-gift"></i> Descubrir a mi amigo secreto</a>
                            <?php else: ?>
                                <p class="text-muted mb-0">El sorteo aún no se realiza. Cuando el organizador publique los resultados,
                                    aquí podrás descubrir a quién le regalas.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($i['reglas']): ?>
                <div class="card tarjeta-intercambio mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5"><i class="bi bi-journal-text text-pino"></i> Reglas y condiciones</h2>
                        <p class="mb-0 texto-reglas"><?= nl2br(e($i['reglas'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- ===== Columna lateral ===== -->
        <div class="col-lg-4">
            <!-- Compartir / invitar -->
            <div class="card tarjeta-intercambio mb-4">
                <div class="card-body p-4 text-center">
                    <h2 class="h6 text-muted mb-2">Código de invitación</h2>
                    <div class="codigo-acceso mb-3"><?= e($codigoBonito) ?></div>

                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-pino" data-copiar="<?= e($enlace) ?>">
                            <i class="bi bi-link-45deg"></i> Copiar enlace
                        </button>
                        <a class="btn btn-outline-success rounded-pill" target="_blank" rel="noopener"
                           href="https://wa.me/?text=<?= rawurlencode($textoCompartir) ?>">
                            <i class="bi bi-whatsapp"></i> Compartir por WhatsApp
                        </a>
                        <button type="button" class="btn btn-outline-secondary rounded-pill d-none" data-compartir
                                data-titulo="<?= e($i['nombre']) ?>" data-texto="<?= e($textoCompartir) ?>">
                            <i class="bi bi-share"></i> Más opciones
                        </button>
                    </div>
                    <?php if (!Intercambio::registroAbierto($i)): ?>
                        <p class="small text-muted mt-3 mb-0">El registro ya cerró: el enlace ya no permite unirse.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Participantes -->
            <div class="card tarjeta-intercambio">
                <div class="card-body p-4">
                    <h2 class="h6 mb-3"><i class="bi bi-people-fill text-navidad"></i> Participantes (<?= count($participantes) ?>)</h2>
                    <?php if (!$participantes): ?>
                        <p class="small text-muted mb-0">Aún no hay participantes. ¡Comparte el código!</p>
                    <?php else: ?>
                        <ul class="list-unstyled lista-participantes mb-0">
                            <?php foreach ($participantes as $p): ?>
                                <li>
                                    <?php if ($p['foto']): ?>
                                        <img src="<?= url('uploads/' . e($p['foto'])) ?>" alt="" class="avatar">
                                    <?php else: ?>
                                        <span class="avatar"><?= e(iniciales($p['nombre'])) ?></span>
                                    <?php endif; ?>
                                    <span class="flex-grow-1"><?= e($p['nombre']) ?></span>
                                    <?php if ($p['confirmado']): ?>
                                        <i class="bi bi-check-circle-fill text-pino" title="Confirmó su participación"></i>
                                    <?php else: ?>
                                        <i class="bi bi-clock text-muted" title="Pendiente de confirmar"></i>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
