<?php
/**
 * Archivo: app/views/admin/panel.php
 * Dashboard del organizador con pestañas: Resumen, Participantes, Sorteo, Invitaciones y Configuración.
 * Variables: $i, $stats, $participantes, $invitaciones, $enlace, $bloqueado,
 *            $restricciones, $asignaciones (número), $vistas (número)
 */
list($estadoTexto, $estadoColor) = estado_intercambio($i);
$abierto = Intercambio::registroAbierto($i);
$total = max(1, $stats['participantes']);   // evita dividir entre cero
$porc = function ($n) use ($total) { return (int)round($n * 100 / $total); };
$diasEvento = max(0, (int)ceil((strtotime($i['fecha_evento']) - time()) / 86400));
$diasCierre = max(0, (int)ceil((strtotime($i['fecha_limite']) - time()) / 86400));
$esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base = $esquema . '://' . $_SERVER['HTTP_HOST'];
?>
<section class="container my-5">
    <a href="<?= url('intercambios/' . (int)$i['id']) ?>" class="small text-decoration-none text-pino">
        <i class="bi bi-arrow-left"></i> Ver la página del intercambio
    </a>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-2 mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="bi bi-speedometer2 text-navidad"></i> Panel de administración</h1>
            <p class="text-muted mb-0"><?= e($i['nombre']) ?> · <span class="badge text-bg-<?= $estadoColor ?>"><?= e($estadoTexto) ?></span></p>
        </div>
    </div>

    <!-- ===== Pestañas ===== -->
    <ul class="nav nav-pills pestanas-admin mb-4" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#resumen" type="button" role="tab">
            <i class="bi bi-bar-chart"></i> Resumen</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#participantes" type="button" role="tab">
            <i class="bi bi-people"></i> Participantes <span class="badge rounded-pill bg-dorado"><?= $stats['participantes'] ?></span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#sorteo" type="button" role="tab">
            <i class="bi bi-shuffle"></i> Sorteo</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#invitaciones" type="button" role="tab">
            <i class="bi bi-envelope-heart"></i> Invitaciones</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#configuracion" type="button" role="tab">
            <i class="bi bi-gear"></i> Configuración</button></li>
    </ul>

    <div class="tab-content">
        <!-- ================= RESUMEN ================= -->
        <div class="tab-pane fade show active" id="resumen" role="tabpanel">
            <div class="row g-3 mb-4">
                <?php
                $tarjetas = [
                    ['bi-people-fill', 'Participantes', $stats['participantes'], null],
                    ['bi-check2-circle', 'Confirmados', $stats['confirmados'], $porc($stats['confirmados'])],
                    ['bi-gift', 'Con lista de deseos', $stats['con_deseos'], $porc($stats['con_deseos'])],
                    ['bi-person-vcard', 'Con perfil completo', $stats['con_perfil'], $porc($stats['con_perfil'])],
                ];
                foreach ($tarjetas as list($icono, $etiqueta, $valor, $p)): ?>
                    <div class="col-6 col-lg-3">
                        <div class="card tarjeta-stat h-100">
                            <div class="card-body">
                                <i class="bi <?= $icono ?>"></i>
                                <div class="stat-valor"><?= (int)$valor ?></div>
                                <div class="small text-muted"><?= e($etiqueta) ?></div>
                                <?php if ($p !== null): ?>
                                    <div class="progress mt-2" role="progressbar" aria-label="<?= e($etiqueta) ?>"
                                         aria-valuenow="<?= $p ?>" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar bg-pino" style="width: <?= $p ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?= $p ?>%</small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card tarjeta-intercambio h-100">
                        <div class="card-body p-4">
                            <h2 class="h5"><i class="bi bi-door-open text-pino"></i> Estado del registro</h2>
                            <ul class="list-unstyled lista-iconos small mb-3">
                                <li><i class="bi bi-hourglass-split"></i> Cierra el <?= e(fecha_hora($i['fecha_limite'])) ?>
                                    <?= $abierto ? '(faltan ' . $diasCierre . ' día' . ($diasCierre === 1 ? '' : 's') . ')' : '' ?></li>
                                <li><i class="bi bi-calendar-event"></i> Intercambio en <?= $diasEvento ?> día<?= $diasEvento === 1 ? '' : 's' ?></li>
                                <li><i class="bi bi-envelope-check"></i> Invitaciones aceptadas: <?= $stats['invitaciones_aceptadas'] ?> de <?= $stats['invitaciones'] ?></li>
                                <li><i class="bi bi-list-stars"></i> Ideas de regalo registradas: <?= $stats['total_deseos'] ?></li>
                            </ul>
                            <?php if ($i['estado'] === 'abierto'): ?>
                                <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/admin/registro') ?>"
                                      data-confirmar="¿Cerrar el registro? Nadie más podrá unirse con el código.">
                                    <?= Csrf::campo() ?><input type="hidden" name="accion" value="cerrar">
                                    <button class="btn btn-outline-secondary rounded-pill"><i class="bi bi-lock"></i> Cerrar registro</button>
                                </form>
                            <?php elseif ($i['estado'] === 'cerrado'): ?>
                                <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/admin/registro') ?>">
                                    <?= Csrf::campo() ?><input type="hidden" name="accion" value="abrir">
                                    <button class="btn btn-pino"><i class="bi bi-unlock"></i> Reabrir registro</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card tarjeta-intercambio h-100">
                        <div class="card-body p-4">
                            <h2 class="h5"><i class="bi bi-shuffle text-navidad"></i> Sorteo</h2>
                            <?php if ($i['estado'] === 'revelado'): ?>
                                <p class="small mb-2"><i class="bi bi-check-circle-fill text-pino"></i> Resultados publicados el <?= e(fecha_hora($i['revelado_en'])) ?>.</p>
                                <p class="small text-muted mb-3"><?= $vistas ?> de <?= $asignaciones ?> participantes ya descubrieron a su amigo secreto.</p>
                            <?php elseif ($i['estado'] === 'sorteado'): ?>
                                <p class="small mb-3"><i class="bi bi-hourglass text-navidad"></i> Sorteo realizado. Falta publicar los resultados.</p>
                            <?php else: ?>
                                <p class="text-muted small mb-3">Se necesitan al menos 3 participantes. Tienes <?= $stats['participantes'] ?>.
                                    <?= count($restricciones) ?> restricción(es) configurada(s).</p>
                            <?php endif; ?>
                            <a href="#sorteo" class="btn btn-pino"><i class="bi bi-arrow-right"></i> Ir al sorteo</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= PARTICIPANTES ================= -->
        <div class="tab-pane fade" id="participantes" role="tabpanel">
            <div class="card tarjeta-intercambio tarjeta-form mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3"><i class="bi bi-person-plus text-pino"></i> Agregar participante</h2>
                    <?php if ($bloqueado): ?>
                        <p class="text-muted mb-0">El sorteo ya se realizó; no se pueden agregar participantes.</p>
                    <?php else: ?>
                        <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/admin/participantes') ?>"
                              class="needs-validation row g-2" novalidate>
                            <?= Csrf::campo() ?>
                            <div class="col-md-8">
                                <input type="email" name="email" class="form-control<?= clase_error('email') ?>" required maxlength="150"
                                       placeholder="correo@ejemplo.com" value="<?= old('email') ?>" aria-label="Correo del participante">
                                <div class="invalid-feedback"><?= e(error_campo('email')) ?: 'Escribe un correo válido.' ?></div>
                            </div>
                            <div class="col-md-4 d-grid">
                                <button class="btn btn-pino"><i class="bi bi-plus-circle"></i> Agregar</button>
                            </div>
                            <div class="form-text">Si ya tiene cuenta se agrega al momento. Si no, se crea una invitación personal para ese correo.</div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card tarjeta-intercambio">
                <div class="card-body p-0">
                    <?php if (!$participantes): ?>
                        <p class="text-muted p-4 mb-0">Todavía no hay participantes. Comparte el código desde la pestaña Invitaciones.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 tabla-admin">
                                <thead>
                                    <tr><th>Participante</th><th class="d-none d-md-table-cell">Se unió</th>
                                        <th class="text-center">Confirmó</th><th class="text-center">Deseos</th><th class="text-end">Acciones</th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($participantes as $p): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($p['foto']): ?>
                                                    <img src="<?= url('uploads/' . e($p['foto'])) ?>" alt="" class="avatar">
                                                <?php else: ?>
                                                    <span class="avatar"><?= e(iniciales($p['nombre'])) ?></span>
                                                <?php endif; ?>
                                                <div class="min-w-0">
                                                    <div class="fw-medium"><?= e($p['nombre']) ?>
                                                        <?php if ((int)$p['usuario_id'] === (int)$i['admin_id']): ?><span class="badge bg-dorado">Tú</span><?php endif; ?></div>
                                                    <div class="small text-muted text-truncate"><?= e($p['email']) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="d-none d-md-table-cell small text-muted"><?= e(fecha_corta($p['creado_en'])) ?></td>
                                        <td class="text-center">
                                            <?= $p['confirmado'] ? '<i class="bi bi-check-circle-fill text-pino" title="Sí"></i>'
                                                                 : '<i class="bi bi-clock text-muted" title="Pendiente"></i>' ?>
                                        </td>
                                        <td class="text-center"><?= (int)$p['total_deseos'] ?></td>
                                        <td class="text-end text-nowrap">
                                            <a href="<?= url('intercambios/' . (int)$i['id'] . '/admin/participantes/' . (int)$p['id']) ?>"
                                               class="btn btn-sm btn-outline-secondary" aria-label="Editar"><i class="bi bi-pencil"></i></a>
                                            <?php if (!$bloqueado): ?>
                                                <form method="post" class="d-inline"
                                                      action="<?= url('intercambios/' . (int)$i['id'] . '/admin/participantes/' . (int)$p['id'] . '/eliminar') ?>"
                                                      data-confirmar="¿Eliminar a <?= e($p['nombre']) ?>? También se borrará su lista de deseos.">
                                                    <?= Csrf::campo() ?>
                                                    <button class="btn btn-sm btn-outline-danger" aria-label="Eliminar"><i class="bi bi-trash"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ================= SORTEO ================= -->
        <div class="tab-pane fade" id="sorteo" role="tabpanel">
            <?php
            $yaSorteado = in_array($i['estado'], ['sorteado', 'revelado'], true);
            $pendientes = $stats['participantes'] - $stats['confirmados'];
            ?>
            <div class="row g-4">
                <!-- Paso 1: restricciones -->
                <div class="col-lg-6">
                    <div class="card tarjeta-intercambio tarjeta-form h-100">
                        <div class="card-body p-4">
                            <h2 class="h5 seccion-form"><span>1</span> Restricciones (opcional)</h2>
                            <p class="small text-muted">Evita combinaciones, por ejemplo que una pareja o hermanos se regalen entre sí.</p>

                            <?php if (!$yaSorteado && count($participantes) >= 2): ?>
                                <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/admin/restricciones') ?>" class="mb-3">
                                    <?= Csrf::campo() ?>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-sm-5">
                                            <select name="dador_id" class="form-select" required aria-label="Quién regala">
                                                <option value="">Persona…</option>
                                                <?php foreach ($participantes as $p): ?>
                                                    <option value="<?= (int)$p['id'] ?>"><?= e($p['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-sm-2 text-center small text-muted">no le regala a</div>
                                        <div class="col-sm-5">
                                            <select name="receptor_id" class="form-select" required aria-label="A quién no le regala">
                                                <option value="">Persona…</option>
                                                <?php foreach ($participantes as $p): ?>
                                                    <option value="<?= (int)$p['id'] ?>"><?= e($p['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="bidireccional" value="1" id="bidireccional" checked>
                                            <label class="form-check-label small" for="bidireccional">En ambos sentidos</label>
                                        </div>
                                        <button class="btn btn-sm btn-pino"><i class="bi bi-plus-circle"></i> Agregar restricción</button>
                                    </div>
                                </form>
                            <?php elseif (!$yaSorteado): ?>
                                <p class="small text-muted">Necesitas al menos 2 participantes para crear restricciones.</p>
                            <?php endif; ?>

                            <?php if (!$restricciones): ?>
                                <p class="small text-muted mb-0"><i class="bi bi-info-circle"></i> Sin restricciones: cualquiera puede regalarle a cualquiera (excepto a sí mismo).</p>
                            <?php else: ?>
                                <ul class="list-unstyled mb-0 d-grid gap-2">
                                    <?php foreach ($restricciones as $r): ?>
                                        <li class="invitacion">
                                            <span class="flex-grow-1 small">
                                                <strong><?= e($r['dador_nombre']) ?></strong>
                                                <i class="bi <?= $r['bidireccional'] ? 'bi-arrow-left-right' : 'bi-arrow-right' ?> text-navidad mx-1"
                                                   title="<?= $r['bidireccional'] ? 'En ambos sentidos' : 'Solo en un sentido' ?>"></i>
                                                <strong><?= e($r['receptor_nombre']) ?></strong>
                                                <span class="text-muted">· no se regalan</span>
                                            </span>
                                            <?php if (!$yaSorteado): ?>
                                                <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/admin/restricciones/' . (int)$r['id'] . '/eliminar') ?>">
                                                    <?= Csrf::campo() ?>
                                                    <button class="btn btn-sm btn-outline-danger" aria-label="Eliminar restricción"><i class="bi bi-x-lg"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Paso 2 y 3: sortear y publicar -->
                <div class="col-lg-6">
                    <div class="card tarjeta-intercambio tarjeta-form h-100">
                        <div class="card-body p-4">
                            <h2 class="h5 seccion-form"><span>2</span> Sorteo</h2>

                            <?php if (!$yaSorteado): ?>
                                <p class="small text-muted">Se asignará al azar a quién le regala cada uno de los <strong><?= $stats['participantes'] ?></strong>
                                    participantes. Nadie, ni siquiera tú, podrá ver la lista completa.</p>
                                <?php if ($pendientes > 0): ?>
                                    <div class="alert alert-warning small py-2"><i class="bi bi-exclamation-triangle"></i>
                                        <?= $pendientes ?> participante(s) aún no confirman. Entrarán al sorteo de todas formas;
                                        si alguien no va a participar, elimínalo antes.</div>
                                <?php endif; ?>
                                <?php if ($i['estado'] === 'abierto' && Intercambio::registroAbierto($i)): ?>
                                    <p class="small"><i class="bi bi-info-circle text-pino"></i> Al sortear, el registro se cierra automáticamente.</p>
                                <?php endif; ?>
                                <?php if ($stats['participantes'] < 3): ?>
                                    <p class="small text-navidad mb-0">Necesitas al menos 3 participantes (tienes <?= $stats['participantes'] ?>).</p>
                                <?php else: ?>
                                    <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/admin/sorteo') ?>"
                                          data-confirmar="¿Realizar el sorteo con <?= $stats['participantes'] ?> participantes? Podrás reiniciarlo mientras no publiques los resultados.">
                                        <?= Csrf::campo() ?>
                                        <button class="btn btn-navidad btn-lg w-100" data-una-vez><i class="bi bi-shuffle"></i> Realizar sorteo</button>
                                    </form>
                                <?php endif; ?>

                            <?php elseif ($i['estado'] === 'sorteado'): ?>
                                <div class="text-center mb-3">
                                    <div class="icono-form mb-2"><i class="bi bi-lock"></i></div>
                                    <p class="mb-1"><strong><?= $asignaciones ?> asignaciones</strong> guardadas en secreto</p>
                                    <p class="small text-muted mb-0">Sorteo realizado el <?= e(fecha_hora($i['sorteado_en'])) ?></p>
                                </div>
                                <h3 class="h6 seccion-form mt-4"><span>3</span> Publicar resultados</h3>
                                <p class="small text-muted">Al publicar, cada participante podrá descubrir a quién le regala.
                                    <strong>Después ya no podrás reiniciar el sorteo.</strong></p>
                                <div class="d-grid gap-2">
                                    <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/admin/sorteo/publicar') ?>" class="d-grid"
                                          data-confirmar="¿Publicar los resultados? Es definitivo: el sorteo ya no se podrá reiniciar.">
                                        <?= Csrf::campo() ?>
                                        <button class="btn btn-navidad btn-lg" data-una-vez><i class="bi bi-megaphone"></i> Publicar resultados</button>
                                    </form>
                                    <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/admin/sorteo/reiniciar') ?>" class="d-grid"
                                          data-confirmar="¿Reiniciar el sorteo? Se borrarán todas las asignaciones.">
                                        <?= Csrf::campo() ?>
                                        <button class="btn btn-outline-secondary rounded-pill" data-una-vez><i class="bi bi-arrow-counterclockwise"></i> Reiniciar sorteo</button>
                                    </form>
                                </div>

                            <?php else: ?>
                                <div class="text-center">
                                    <div class="icono-form mb-2"><i class="bi bi-stars"></i></div>
                                    <p class="mb-1"><strong>¡Resultados publicados!</strong></p>
                                    <p class="small text-muted">Publicados el <?= e(fecha_hora($i['revelado_en'])) ?>.</p>
                                    <div class="progress mb-1" role="progressbar" aria-label="Participantes que ya vieron su resultado"
                                         aria-valuenow="<?= $vistas ?>" aria-valuemin="0" aria-valuemax="<?= max(1, $asignaciones) ?>">
                                        <div class="progress-bar bg-pino" style="width: <?= (int)round($vistas * 100 / max(1, $asignaciones)) ?>%"></div>
                                    </div>
                                    <p class="small text-muted mb-0"><?= $vistas ?> de <?= $asignaciones ?> ya descubrieron a su amigo secreto.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= INVITACIONES ================= -->
        <div class="tab-pane fade" id="invitaciones" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card tarjeta-intercambio mb-4">
                        <div class="card-body p-4 text-center">
                            <h2 class="h6 text-muted">Código general del intercambio</h2>
                            <div class="codigo-acceso mb-3"><?= e(CodigoService::formatear($i['codigo_acceso'])) ?></div>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-pino" data-copiar="<?= e($enlace) ?>"><i class="bi bi-link-45deg"></i> Copiar enlace</button>
                                <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/admin/codigo') ?>"
                                      data-confirmar="¿Generar un código nuevo? El código y el enlace actuales dejarán de funcionar." class="d-grid">
                                    <?= Csrf::campo() ?>
                                    <button class="btn btn-outline-secondary rounded-pill"><i class="bi bi-arrow-repeat"></i> Generar código nuevo</button>
                                </form>
                            </div>
                            <p class="small text-muted mt-3 mb-0">Funciona para cualquiera hasta la fecha límite de registro.</p>
                        </div>
                    </div>

                    <div class="card tarjeta-intercambio tarjeta-form">
                        <div class="card-body p-4">
                            <h2 class="h6"><i class="bi bi-envelope-plus text-navidad"></i> Nueva invitación personal</h2>
                            <p class="small text-muted">Un enlace único para una persona. Funciona aunque ya haya cerrado el registro (mientras no se haga el sorteo).</p>
                            <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/admin/invitaciones') ?>" class="needs-validation" novalidate>
                                <?= Csrf::campo() ?>
                                <input type="email" name="email" class="form-control mb-2" maxlength="150"
                                       placeholder="correo@ejemplo.com (opcional)" aria-label="Correo del invitado">
                                <div class="invalid-feedback">Escribe un correo válido o déjalo vacío.</div>
                                <button class="btn btn-navidad w-100"><i class="bi bi-plus-circle"></i> Crear invitación</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card tarjeta-intercambio">
                        <div class="card-body p-4">
                            <h2 class="h6 mb-3">Invitaciones personales (<?= count($invitaciones) ?>)</h2>
                            <?php if (!$invitaciones): ?>
                                <p class="small text-muted mb-0">Aún no creas invitaciones personales.</p>
                            <?php else: ?>
                                <div class="d-grid gap-2">
                                <?php foreach ($invitaciones as $inv):
                                    $link = $base . url('invitacion/' . $inv['token']);
                                    $texto = '¡Hola! Te invito a mi intercambio navideño "' . $i['nombre'] . '". Únete aquí: ' . $link;
                                    $mailto = 'mailto:' . rawurlencode((string)$inv['email'])
                                            . '?subject=' . rawurlencode('Invitación: ' . $i['nombre'])
                                            . '&body=' . rawurlencode($texto);
                                ?>
                                    <div class="invitacion">
                                        <div class="min-w-0 flex-grow-1">
                                            <div class="fw-medium text-truncate"><?= e($inv['email'] ?: 'Sin correo (enlace abierto)') ?></div>
                                            <div class="small text-muted">
                                                Creada el <?= e(fecha_corta($inv['creado_en'])) ?> ·
                                                <?= $inv['aceptada_en']
                                                    ? '<span class="text-pino"><i class="bi bi-check-circle-fill"></i> Aceptada</span>'
                                                    : '<span class="text-navidad"><i class="bi bi-clock"></i> Pendiente</span>' ?>
                                            </div>
                                        </div>
                                        <?php if (!$inv['aceptada_en']): ?>
                                            <div class="d-flex gap-1 flex-shrink-0">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-copiar="<?= e($link) ?>"
                                                        aria-label="Copiar enlace"><i class="bi bi-link-45deg"></i></button>
                                                <a class="btn btn-sm btn-outline-secondary" href="<?= e($mailto) ?>" aria-label="Enviar por correo">
                                                    <i class="bi bi-envelope"></i></a>
                                                <a class="btn btn-sm btn-outline-success" target="_blank" rel="noopener"
                                                   href="https://wa.me/?text=<?= rawurlencode($texto) ?>" aria-label="Enviar por WhatsApp">
                                                    <i class="bi bi-whatsapp"></i></a>
                                                <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/admin/invitaciones/' . (int)$inv['id'] . '/eliminar') ?>"
                                                      data-confirmar="¿Cancelar esta invitación? Su enlace dejará de funcionar.">
                                                    <?= Csrf::campo() ?>
                                                    <button class="btn btn-sm btn-outline-danger" aria-label="Cancelar invitación"><i class="bi bi-x-lg"></i></button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= CONFIGURACIÓN ================= -->
        <div class="tab-pane fade" id="configuracion" role="tabpanel">
            <form method="post" action="<?= url('intercambios/' . (int)$i['id'] . '/admin/configuracion') ?>" class="needs-validation" novalidate>
                <?= Csrf::campo() ?>
                <?php require RUTA_VISTAS . '/intercambio/_campos.php'; ?>
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-navidad btn-lg"><i class="bi bi-save"></i> Guardar configuración</button>
                </div>
            </form>
        </div>
    </div>
</section>
