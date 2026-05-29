<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('medico');

$current_page = 'agenda';
$page_title   = 'Agenda';

$pdo = getDB();

// ── OBTENER ID Y ESPECIALIDAD DEL MÉDICO ──
$stmt = $pdo->prepare("SELECT m.id, e.nombre AS especialidad FROM medicos m JOIN especialidades e ON e.id = m.especialidad_id WHERE m.usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$medico = $stmt->fetch();
$medico_id           = $medico['id'] ?? null;
$medico_especialidad = $medico['especialidad'] ?? 'Médico';

// ── CAMBIAR ESTATUS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cita_id'], $_POST['nuevo_estatus'])) {
    $cita_id       = (int)$_POST['cita_id'];
    $nuevo_estatus = $_POST['nuevo_estatus'];
    $permitidos    = ['confirmada', 'completada', 'cancelada'];
    if (in_array($nuevo_estatus, $permitidos)) {
        $pdo->prepare("UPDATE citas SET estatus = ? WHERE id = ? AND medico_id = ?")
            ->execute([$nuevo_estatus, $cita_id, $medico_id]);
    }
    $params = http_build_query(array_filter([
        'vista' => $_POST['vista'] ?? 'diaria',
        'fecha' => $_POST['fecha'] ?? date('Y-m-d'),
        'q'     => $_POST['q']    ?? '',
        'filtro'=> $_POST['filtro'] ?? 'todos',
    ]));
    header("Location: agenda.php?{$params}");
    exit;
}

// ── PARÁMETROS ──
$fecha_base = $_GET['fecha']  ?? date('Y-m-d');
$vista      = $_GET['vista']  ?? 'diaria';
$buscar     = trim($_GET['q'] ?? '');
$filtro     = $_GET['filtro'] ?? 'todos';
$pagina     = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 10;
$offset     = ($pagina - 1) * $por_pagina;

// ── VISTA DIARIA ──
$fecha_dt   = new DateTime($fecha_base);
$fecha_prev = (clone $fecha_dt)->modify('-1 day')->format('Y-m-d');
$fecha_next = (clone $fecha_dt)->modify('+1 day')->format('Y-m-d');
$fecha_label= $fecha_dt->format('d') . ' de ' .
    ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'][$fecha_dt->format('n') - 1] .
    ' de ' . $fecha_dt->format('Y');

// ── VISTA SEMANAL ──
$dia_semana  = (int)$fecha_dt->format('N');
$inicio_sem  = (clone $fecha_dt)->modify('-' . ($dia_semana - 1) . ' days');
$fin_sem     = (clone $inicio_sem)->modify('+6 days');
$sem_prev    = (clone $inicio_sem)->modify('-7 days')->format('Y-m-d');
$sem_next    = (clone $inicio_sem)->modify('+7 days')->format('Y-m-d');
$sem_label   = $inicio_sem->format('d M') . ' — ' . $fin_sem->format('d M Y');

$dias_nombres = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
$hoy = date('Y-m-d');

// ── CITAS DIARIAS ──
$stmt = $pdo->prepare("
    SELECT c.id, c.hora, c.estatus, c.motivo,
           CONCAT(u.nombre, ' ', u.apellido) AS paciente,
           u.nombre AS pac_nombre, u.apellido AS pac_apellido,
           u.correo AS pac_correo, u.telefono AS pac_telefono
    FROM citas c
    JOIN usuarios u ON c.paciente_id = u.id
    WHERE c.medico_id = ? AND c.fecha = ?
    ORDER BY c.hora ASC
");
$stmt->execute([$medico_id, $fecha_base]);
$citas_dia = $stmt->fetchAll();

// ── CITAS SEMANALES ──
$stmt = $pdo->prepare("
    SELECT c.id, c.fecha, c.hora, c.estatus, c.motivo,
           CONCAT(u.nombre, ' ', u.apellido) AS paciente,
           u.nombre AS pac_nombre, u.apellido AS pac_apellido
    FROM citas c
    JOIN usuarios u ON c.paciente_id = u.id
    WHERE c.medico_id = ? AND c.fecha BETWEEN ? AND ?
    ORDER BY c.fecha ASC, c.hora ASC
");
$stmt->execute([$medico_id, $inicio_sem->format('Y-m-d'), $fin_sem->format('Y-m-d')]);
$citas_semana_raw = $stmt->fetchAll();

$citas_semana = [];
foreach ($citas_semana_raw as $c) {
    $citas_semana[$c['fecha']][] = $c;
}

$dias_semana = [];
for ($i = 0; $i < 7; $i++) {
    $d = (clone $inicio_sem)->modify("+{$i} days");
    $dias_semana[] = $d->format('Y-m-d');
}

// ── VISTA LISTA ──
$where_lista  = "WHERE c.medico_id = ?";
$params_lista = [$medico_id];

if ($buscar) {
    $where_lista   .= " AND (u.nombre LIKE ? OR u.apellido LIKE ?)";
    $like           = "%{$buscar}%";
    $params_lista[] = $like;
    $params_lista[] = $like;
}

if ($filtro !== 'todos') {
    $where_lista   .= " AND c.estatus = ?";
    $params_lista[] = $filtro;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM citas c JOIN usuarios u ON c.paciente_id = u.id {$where_lista}");
$stmt->execute($params_lista);
$total_filtrado = $stmt->fetchColumn();
$total_paginas  = max(1, ceil($total_filtrado / $por_pagina));
$pagina         = min($pagina, $total_paginas);

$stmt = $pdo->prepare("
    SELECT c.id, c.fecha, c.hora, c.estatus, c.motivo,
           CONCAT(u.nombre, ' ', u.apellido) AS paciente,
           u.nombre AS pac_nombre, u.apellido AS pac_apellido
    FROM citas c
    JOIN usuarios u ON c.paciente_id = u.id
    {$where_lista}
    ORDER BY c.fecha DESC, c.hora DESC
    LIMIT {$por_pagina} OFFSET {$offset}
");
$stmt->execute($params_lista);
$citas_lista = $stmt->fetchAll();

$total_pendientes  = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE medico_id = ? AND estatus = 'pendiente'");
$total_pendientes->execute([$medico_id]);
$cnt_pendientes = $total_pendientes->fetchColumn();

$total_confirmadas = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE medico_id = ? AND estatus = 'confirmada'");
$total_confirmadas->execute([$medico_id]);
$cnt_confirmadas = $total_confirmadas->fetchColumn();

$total_completadas = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE medico_id = ? AND estatus = 'completada'");
$total_completadas->execute([$medico_id]);
$cnt_completadas = $total_completadas->fetchColumn();

$total_canceladas = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE medico_id = ? AND estatus = 'cancelada'");
$total_canceladas->execute([$medico_id]);
$cnt_canceladas = $total_canceladas->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/medico/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/medico/agenda.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/medico/layout.php'; ?>

  <div class="content">

    <div class="page-header">
      <div>
        <h1 class="page-title-text">Agenda</h1>
        <p class="page-sub">Consulta y gestiona tus citas programadas.</p>
      </div>
      <div class="vista-toggle">
        <a href="?vista=diaria&fecha=<?= $fecha_base ?>"
           class="vista-btn <?= $vista === 'diaria' ? 'active' : '' ?>">
          <i class="ti ti-calendar-day"></i> Diaria
        </a>
        <a href="?vista=semanal&fecha=<?= $fecha_base ?>"
           class="vista-btn <?= $vista === 'semanal' ? 'active' : '' ?>">
          <i class="ti ti-calendar-week"></i> Semanal
        </a>
        <a href="?vista=lista&fecha=<?= $fecha_base ?>"
           class="vista-btn <?= $vista === 'lista' ? 'active' : '' ?>">
          <i class="ti ti-list"></i> Lista
        </a>
      </div>
    </div>

    <?php if ($vista === 'diaria'): ?>
    <!-- ── VISTA DIARIA ── -->
    <div class="agenda-card">
      <div class="agenda-nav">
        <a href="?vista=diaria&fecha=<?= $fecha_prev ?>" class="nav-arrow"><i class="ti ti-chevron-left"></i></a>
        <div class="agenda-fecha-wrap">
          <span class="agenda-fecha"><?= $fecha_label ?></span>
          <?php if ($fecha_base === $hoy): ?>
            <span class="hoy-badge">Hoy</span>
          <?php endif; ?>
        </div>
        <a href="?vista=diaria&fecha=<?= $fecha_next ?>" class="nav-arrow"><i class="ti ti-chevron-right"></i></a>
        <a href="?vista=diaria&fecha=<?= $hoy ?>" class="btn-hoy">Hoy</a>
      </div>

      <?php if (empty($citas_dia)): ?>
        <div class="empty-state">
          <i class="ti ti-calendar-off"></i>
          <p>No tienes citas programadas para este día.</p>
        </div>
      <?php else: ?>
        <div class="citas-diaria">
          <?php foreach ($citas_dia as $c): ?>
            <div class="cita-row">
              <div class="cita-time">
                <span class="cita-hora"><?= substr($c['hora'], 0, 5) ?></span>
              </div>
              <div class="cita-content">
                <div class="cita-header-row">
                  <div class="patient-cell">
                    <div class="mini-avatar">
                      <?= strtoupper(substr($c['pac_nombre'], 0, 1) . substr($c['pac_apellido'], 0, 1)) ?>
                    </div>
                    <div>
                      <div class="cita-paciente"><?= htmlspecialchars($c['paciente']) ?></div>
                      <div class="cita-contacto">
                        <?= $c['pac_correo'] ? htmlspecialchars($c['pac_correo']) : '' ?>
                        <?= $c['pac_telefono'] ? ' · ' . htmlspecialchars($c['pac_telefono']) : '' ?>
                      </div>
                    </div>
                  </div>
                  <div class="cita-acciones">
                    <span class="badge <?= $c['estatus'] ?>"><?= ucfirst($c['estatus']) ?></span>
                    <?php if ($c['estatus'] === 'pendiente'): ?>
                      <form method="POST" style="display:inline">
                        <input type="hidden" name="cita_id"       value="<?= $c['id'] ?>">
                        <input type="hidden" name="nuevo_estatus" value="confirmada">
                        <input type="hidden" name="vista"         value="diaria">
                        <input type="hidden" name="fecha"         value="<?= $fecha_base ?>">
                        <button type="submit" class="btn-estatus confirmar" title="Confirmar">
                          <i class="ti ti-check"></i> Confirmar
                        </button>
                      </form>
                      <form method="POST" style="display:inline">
                        <input type="hidden" name="cita_id"       value="<?= $c['id'] ?>">
                        <input type="hidden" name="nuevo_estatus" value="cancelada">
                        <input type="hidden" name="vista"         value="diaria">
                        <input type="hidden" name="fecha"         value="<?= $fecha_base ?>">
                        <button type="submit" class="btn-estatus cancelar" title="Cancelar">
                          <i class="ti ti-x"></i> Cancelar
                        </button>
                      </form>
                    <?php elseif ($c['estatus'] === 'confirmada'): ?>
                      <form method="POST" style="display:inline">
                        <input type="hidden" name="cita_id"       value="<?= $c['id'] ?>">
                        <input type="hidden" name="nuevo_estatus" value="completada">
                        <input type="hidden" name="vista"         value="diaria">
                        <input type="hidden" name="fecha"         value="<?= $fecha_base ?>">
                        <button type="submit" class="btn-estatus completar" title="Completar">
                          <i class="ti ti-circle-check"></i> Completar
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
                <?php if ($c['motivo']): ?>
                  <div class="cita-motivo"><i class="ti ti-notes"></i> <?= htmlspecialchars($c['motivo']) ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php elseif ($vista === 'semanal'): ?>
    <!-- ── VISTA SEMANAL ── -->
    <div class="agenda-card">
      <div class="agenda-nav">
        <a href="?vista=semanal&fecha=<?= $sem_prev ?>" class="nav-arrow"><i class="ti ti-chevron-left"></i></a>
        <span class="agenda-fecha"><?= $sem_label ?></span>
        <a href="?vista=semanal&fecha=<?= $sem_next ?>" class="nav-arrow"><i class="ti ti-chevron-right"></i></a>
        <a href="?vista=semanal&fecha=<?= $hoy ?>" class="btn-hoy">Esta semana</a>
      </div>
      <div class="semana-grid">
        <?php foreach ($dias_semana as $i => $dia): ?>
          <div class="dia-col <?= $dia === $hoy ? 'hoy' : '' ?>">
            <div class="dia-header">
              <span class="dia-nombre"><?= $dias_nombres[$i] ?></span>
              <span class="dia-num <?= $dia === $hoy ? 'hoy-num' : '' ?>">
                <?= (new DateTime($dia))->format('d') ?>
              </span>
            </div>
            <div class="dia-citas">
              <?php if (!empty($citas_semana[$dia])): ?>
                <?php foreach ($citas_semana[$dia] as $c): ?>
                  <div class="cita-chip <?= $c['estatus'] ?>">
                    <span class="chip-hora"><?= substr($c['hora'], 0, 5) ?></span>
                    <span class="chip-nombre"><?= htmlspecialchars($c['pac_nombre'] . ' ' . substr($c['pac_apellido'], 0, 1) . '.') ?></span>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="dia-vacio">Sin citas</div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <?php else: ?>
    <!-- ── VISTA LISTA ── -->
    <div class="table-card">
      <div class="table-toolbar">
        <div class="filter-tabs">
          <?php
          $filtros = [
            'todos'      => ['label' => 'Todos',      'count' => $cnt_pendientes + $cnt_confirmadas + $cnt_completadas + $cnt_canceladas],
            'pendiente'  => ['label' => 'Pendientes', 'count' => $cnt_pendientes],
            'confirmada' => ['label' => 'Confirmadas','count' => $cnt_confirmadas],
            'completada' => ['label' => 'Completadas','count' => $cnt_completadas],
            'cancelada'  => ['label' => 'Canceladas', 'count' => $cnt_canceladas],
          ];
          foreach ($filtros as $val => $f): ?>
            <a href="?vista=lista&filtro=<?= $val ?>&q=<?= urlencode($buscar) ?>"
               class="filter-tab <?= $filtro === $val ? 'active' : '' ?>">
              <?= $f['label'] ?> <span class="filter-count"><?= $f['count'] ?></span>
            </a>
          <?php endforeach; ?>
        </div>
        <form method="GET" class="search-form">
          <input type="hidden" name="vista"  value="lista">
          <input type="hidden" name="filtro" value="<?= htmlspecialchars($filtro) ?>">
          <div class="search-wrap">
            <i class="ti ti-search search-icon"></i>
            <input type="text" name="q" class="search-input"
                   placeholder="Buscar paciente..."
                   value="<?= htmlspecialchars($buscar) ?>">
            <?php if ($buscar): ?>
              <a href="?vista=lista&filtro=<?= $filtro ?>" class="search-clear"><i class="ti ti-x"></i></a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Paciente</th>
              <th>Fecha</th>
              <th>Hora</th>
              <th>Motivo</th>
              <th>Estatus</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($citas_lista)): ?>
              <tr>
                <td colspan="6">
                  <div class="empty-state">
                    <i class="ti ti-calendar-off"></i>
                    <p>No hay citas que coincidan.</p>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($citas_lista as $c): ?>
                <tr>
                  <td>
                    <div class="patient-cell">
                      <div class="mini-avatar">
                        <?= strtoupper(substr($c['pac_nombre'], 0, 1) . substr($c['pac_apellido'], 0, 1)) ?>
                      </div>
                      <span class="patient-name"><?= htmlspecialchars($c['paciente']) ?></span>
                    </div>
                  </td>
                  <td class="text-muted"><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                  <td class="text-muted"><?= substr($c['hora'], 0, 5) ?></td>
                  <td class="text-muted"><?= htmlspecialchars($c['motivo'] ?? '—') ?></td>
                  <td><span class="badge <?= $c['estatus'] ?>"><?= ucfirst($c['estatus']) ?></span></td>
                  <td>
                    <div class="actions-cell">
                      <?php if ($c['estatus'] === 'pendiente'): ?>
                        <form method="POST" style="display:inline">
                          <input type="hidden" name="cita_id"       value="<?= $c['id'] ?>">
                          <input type="hidden" name="nuevo_estatus" value="confirmada">
                          <input type="hidden" name="vista"         value="lista">
                          <input type="hidden" name="filtro"        value="<?= $filtro ?>">
                          <input type="hidden" name="q"             value="<?= htmlspecialchars($buscar) ?>">
                          <button type="submit" class="action-icon success" title="Confirmar">
                            <i class="ti ti-check"></i>
                          </button>
                        </form>
                        <form method="POST" style="display:inline">
                          <input type="hidden" name="cita_id"       value="<?= $c['id'] ?>">
                          <input type="hidden" name="nuevo_estatus" value="cancelada">
                          <input type="hidden" name="vista"         value="lista">
                          <input type="hidden" name="filtro"        value="<?= $filtro ?>">
                          <input type="hidden" name="q"             value="<?= htmlspecialchars($buscar) ?>">
                          <button type="submit" class="action-icon danger" title="Cancelar">
                            <i class="ti ti-x"></i>
                          </button>
                        </form>
                      <?php elseif ($c['estatus'] === 'confirmada'): ?>
                        <form method="POST" style="display:inline">
                          <input type="hidden" name="cita_id"       value="<?= $c['id'] ?>">
                          <input type="hidden" name="nuevo_estatus" value="completada">
                          <input type="hidden" name="vista"         value="lista">
                          <input type="hidden" name="filtro"        value="<?= $filtro ?>">
                          <input type="hidden" name="q"             value="<?= htmlspecialchars($buscar) ?>">
                          <button type="submit" class="action-icon teal" title="Completar">
                            <i class="ti ti-circle-check"></i>
                          </button>
                        </form>
                      <?php else: ?>
                        <span class="text-muted">—</span>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($total_paginas > 1): ?>
      <div class="pagination">
        <span class="pagination-info">
          Mostrando <?= min($offset + 1, $total_filtrado) ?>–<?= min($offset + $por_pagina, $total_filtrado) ?> de <?= $total_filtrado ?> citas
        </span>
        <div class="pagination-btns">
          <?php if ($pagina > 1): ?>
            <a href="?vista=lista&filtro=<?= $filtro ?>&q=<?= urlencode($buscar) ?>&pagina=<?= $pagina - 1 ?>" class="pag-btn"><i class="ti ti-chevron-left"></i></a>
          <?php endif; ?>
          <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?vista=lista&filtro=<?= $filtro ?>&q=<?= urlencode($buscar) ?>&pagina=<?= $i ?>"
               class="pag-btn <?= $i === $pagina ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($pagina < $total_paginas): ?>
            <a href="?vista=lista&filtro=<?= $filtro ?>&q=<?= urlencode($buscar) ?>&pagina=<?= $pagina + 1 ?>" class="pag-btn"><i class="ti ti-chevron-right"></i></a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div>
</div>

</div><!-- .layout -->

<script src="/CitaAgil1/assets/js/medico/layout.js"></script>
<script src="/CitaAgil1/assets/js/medico/agenda.js"></script>
</body>
</html>