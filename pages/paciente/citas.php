<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('paciente');

$current_page = 'citas';
$page_title   = 'Mis citas';

$pdo = getDB();
$paciente_id = $_SESSION['user_id'];

// ── CANCELAR CITA ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancelar') {
    $cita_id = (int)($_POST['cita_id'] ?? 0);
    $pdo->prepare("UPDATE citas SET estatus = 'cancelada' WHERE id = ? AND paciente_id = ? AND estatus IN ('pendiente','confirmada')")
        ->execute([$cita_id, $paciente_id]);
    header("Location: citas.php?cancelada=1");
    exit;
}

// ── REPROGRAMAR CITA ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reprogramar') {
    $cita_id   = (int)($_POST['cita_id']   ?? 0);
    $medico_id = (int)($_POST['medico_id'] ?? 0);
    $fecha     = $_POST['fecha'] ?? '';
    $hora      = $_POST['hora']  ?? '';

    if ($cita_id && $medico_id && $fecha && $hora) {
        $ocupado = $pdo->prepare("SELECT id FROM citas WHERE medico_id = ? AND fecha = ? AND hora = ? AND estatus IN ('pendiente','confirmada') AND id != ?");
        $ocupado->execute([$medico_id, $fecha, $hora, $cita_id]);
        if ($ocupado->fetch()) {
            $error = 'Ese horario ya está ocupado. Selecciona otro.';
        } else {
            $pdo->prepare("UPDATE citas SET fecha = ?, hora = ?, estatus = 'pendiente' WHERE id = ? AND paciente_id = ?")
                ->execute([$fecha, $hora, $cita_id, $paciente_id]);
            header("Location: citas.php?reprogramada=1");
            exit;
        }
    }
}

// ── PARÁMETROS ──
$filtro    = $_GET['filtro']  ?? 'todos';
$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina= 10;
$offset    = ($pagina - 1) * $por_pagina;

// ── TOTALES ──
$cnt = [];
foreach (['pendiente','confirmada','completada','cancelada'] as $e) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE paciente_id = ? AND estatus = ?");
    $s->execute([$paciente_id, $e]);
    $cnt[$e] = $s->fetchColumn();
}
$cnt['todos'] = array_sum($cnt);

// ── WHERE ──
$where  = "WHERE c.paciente_id = ?";
$params = [$paciente_id];
if ($filtro !== 'todos') { $where .= " AND c.estatus = ?"; $params[] = $filtro; }

// ── TOTAL FILTRADO ──
$stmt = $pdo->prepare("SELECT COUNT(*) FROM citas c {$where}");
$stmt->execute($params);
$total_filtrado = $stmt->fetchColumn();
$total_paginas  = max(1, ceil($total_filtrado / $por_pagina));
$pagina         = min($pagina, $total_paginas);

// ── CITAS ──
$stmt = $pdo->prepare("
    SELECT c.id, c.fecha, c.hora, c.estatus, c.motivo,
           CONCAT(u.nombre, ' ', u.apellido) AS medico,
           u.nombre AS med_nombre, u.apellido AS med_apellido,
           e.nombre AS especialidad,
           m.id AS medico_id
    FROM citas c
    JOIN medicos m ON c.medico_id = m.id
    JOIN usuarios u ON m.usuario_id = u.id
    JOIN especialidades e ON m.especialidad_id = e.id
    {$where}
    ORDER BY c.fecha DESC, c.hora DESC
    LIMIT {$por_pagina} OFFSET {$offset}
");
$stmt->execute($params);
$citas = $stmt->fetchAll();

// ── SLOTS PARA REPROGRAMAR ──
$slots_reprogramar = [];
if (isset($_GET['reprogramar'])) {
    $cita_rep_id = (int)$_GET['reprogramar'];
    $stmt = $pdo->prepare("SELECT medico_id, fecha FROM citas WHERE id = ? AND paciente_id = ?");
    $stmt->execute([$cita_rep_id, $paciente_id]);
    $cita_rep = $stmt->fetch();

    if ($cita_rep) {
        $med_id   = $cita_rep['medico_id'];
        $fecha_rep= $_GET['fecha_rep'] ?? date('Y-m-d');

        $stmt = $pdo->prepare("SELECT * FROM disponibilidad_medico WHERE medico_id = ? AND activo = 1");
        $stmt->execute([$med_id]);
        $disp_raw = $stmt->fetchAll();
        $dias_disp = [];
        foreach ($disp_raw as $d) $dias_disp[$d['dia_semana']] = $d;

        $dt      = new DateTime($fecha_rep);
        $dia_num = (int)$dt->format('N');
        $duracion= (int)($pdo->query("SELECT valor FROM configuracion WHERE clave = 'duracion_cita'")->fetchColumn() ?? 30);

        if (isset($dias_disp[$dia_num])) {
            $disp   = $dias_disp[$dia_num];
            $inicio = new DateTime($fecha_rep . ' ' . $disp['hora_inicio']);
            $fin    = new DateTime($fecha_rep . ' ' . $disp['hora_fin']);

            $stmt = $pdo->prepare("SELECT hora FROM citas WHERE medico_id = ? AND fecha = ? AND estatus IN ('pendiente','confirmada') AND id != ?");
            $stmt->execute([$med_id, $fecha_rep, $cita_rep_id]);
            $ocupadas = array_column($stmt->fetchAll(), 'hora');

            $current = clone $inicio;
            while ($current < $fin) {
                $hora_str = $current->format('H:i:s');
                $slots_reprogramar[] = [
                    'hora'    => $hora_str,
                    'display' => $current->format('H:i'),
                    'ocupado' => in_array($hora_str, $ocupadas),
                ];
                $current->modify("+{$duracion} minutes");
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $page_title ?> — CitaÁgil</title>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/paciente/layout.css"/>
  <link rel="stylesheet" href="/CitaAgil1/assets/css/paciente/citas.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/paciente/layout.php'; ?>

  <div class="content">

    <?php if (isset($_GET['agendada'])): ?>
      <div class="toast"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> ¡Cita agendada exitosamente!</div>
    <?php elseif (isset($_GET['cancelada'])): ?>
      <div class="toast"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Cita cancelada.</div>
    <?php elseif (isset($_GET['reprogramada'])): ?>
      <div class="toast"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Cita reprogramada exitosamente.</div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="toast toast-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <div class="page-header">
      <div>
        <h1 class="page-title-text">Mis citas</h1>
        <p class="page-sub">Gestiona todas tus citas médicas.</p>
      </div>
      <a href="/CitaAgil1/pages/paciente/buscar.php" class="btn-nueva">
        <i class="ti ti-plus"></i> Nueva cita
      </a>
    </div>

    <div class="table-card">
      <div class="table-toolbar">
        <div class="filter-tabs">
          <?php
          $filtros = [
            'todos'      => 'Todos',
            'pendiente'  => 'Pendientes',
            'confirmada' => 'Confirmadas',
            'completada' => 'Completadas',
            'cancelada'  => 'Canceladas',
          ];
          foreach ($filtros as $val => $label): ?>
            <a href="?filtro=<?= $val ?>"
               class="filter-tab <?= $filtro === $val ? 'active' : '' ?>">
              <?= $label ?> <span class="filter-count"><?= $cnt[$val] ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Médico</th>
              <th>Especialidad</th>
              <th>Fecha</th>
              <th>Hora</th>
              <th>Estatus</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($citas)): ?>
              <tr>
                <td colspan="6">
                  <div class="empty-state">
                    <i class="ti ti-calendar-off"></i>
                    <p>No tienes citas en esta categoría.</p>
                    <a href="/CitaAgil1/pages/paciente/buscar.php" class="btn-agendar-link">Agendar una cita</a>
                  </div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($citas as $c): ?>
                <tr>
                  <td>
                    <div class="medico-cell">
                      <div class="mini-avatar">
                        <?= strtoupper(substr($c['med_nombre'], 0, 1) . substr($c['med_apellido'], 0, 1)) ?>
                      </div>
                      <span class="medico-nombre">Dr. <?= htmlspecialchars($c['medico']) ?></span>
                    </div>
                  </td>
                  <td><span class="esp-badge"><?= htmlspecialchars($c['especialidad']) ?></span></td>
                  <td class="text-muted"><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
                  <td class="text-muted"><?= substr($c['hora'], 0, 5) ?></td>
                  <td><span class="badge <?= $c['estatus'] ?>"><?= ucfirst($c['estatus']) ?></span></td>
                  <td>
                    <div class="actions-cell">
                      <button class="action-icon" title="Ver detalle"
                              onclick="verDetalle(<?= htmlspecialchars(json_encode($c)) ?>)">
                        <i class="ti ti-eye"></i>
                      </button>
                      <?php if (in_array($c['estatus'], ['pendiente', 'confirmada'])): ?>
                        <a href="?filtro=<?= $filtro ?>&reprogramar=<?= $c['id'] ?>"
                           class="action-icon" title="Reprogramar">
                          <i class="ti ti-calendar-repeat"></i>
                        </a>
                        <button class="action-icon danger" title="Cancelar"
                                onclick="confirmarCancelar(<?= $c['id'] ?>)">
                          <i class="ti ti-x"></i>
                        </button>
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
            <a href="?filtro=<?= $filtro ?>&pagina=<?= $pagina - 1 ?>" class="pag-btn"><i class="ti ti-chevron-left"></i></a>
          <?php endif; ?>
          <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?filtro=<?= $filtro ?>&pagina=<?= $i ?>"
               class="pag-btn <?= $i === $pagina ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($pagina < $total_paginas): ?>
            <a href="?filtro=<?= $filtro ?>&pagina=<?= $pagina + 1 ?>" class="pag-btn"><i class="ti ti-chevron-right"></i></a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Modal reprogramar -->
    <?php if (isset($_GET['reprogramar']) && isset($cita_rep)): ?>
    <div class="modal-overlay open" id="reprogramarModal">
      <div class="modal modal-reprogramar">
        <div class="modal-rep-header">
          <div class="modal-rep-icon"><i class="ti ti-calendar-repeat"></i></div>
          <div>
            <div class="modal-rep-titulo">Reprogramar cita</div>
            <div class="modal-rep-sub">Selecciona nueva fecha y hora.</div>
          </div>
          <a href="?filtro=<?= $filtro ?>" class="modal-close-link"><i class="ti ti-x"></i></a>
        </div>
        <div class="modal-rep-body">
          <!-- Selector de fecha -->
          <form method="GET" class="fecha-rep-form">
            <input type="hidden" name="filtro"       value="<?= $filtro ?>">
            <input type="hidden" name="reprogramar"  value="<?= (int)$_GET['reprogramar'] ?>">
            <div class="fecha-rep-field">
              <label>Selecciona fecha</label>
              <input type="date" name="fecha_rep"
                     min="<?= date('Y-m-d') ?>"
                     value="<?= htmlspecialchars($_GET['fecha_rep'] ?? date('Y-m-d')) ?>"
                     onchange="this.form.submit()">
            </div>
          </form>

          <?php if (!empty($slots_reprogramar)): ?>
            <div class="slots-label">Horarios disponibles</div>
            <form method="POST" class="slots-rep-form">
              <input type="hidden" name="action"    value="reprogramar">
              <input type="hidden" name="cita_id"   value="<?= (int)$_GET['reprogramar'] ?>">
              <input type="hidden" name="medico_id" value="<?= $med_id ?>">
              <input type="hidden" name="fecha"     value="<?= htmlspecialchars($_GET['fecha_rep'] ?? '') ?>">
              <div class="slots-grid-rep">
                <?php foreach ($slots_reprogramar as $s): ?>
                  <?php if (!$s['ocupado']): ?>
                    <button type="submit" name="hora" value="<?= $s['hora'] ?>" class="slot-rep-btn">
                      <?= $s['display'] ?>
                    </button>
                  <?php else: ?>
                    <span class="slot-rep-btn ocupado"><?= $s['display'] ?></span>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </form>
          <?php elseif (isset($_GET['fecha_rep'])): ?>
            <div class="empty-state" style="padding:20px">
              <i class="ti ti-clock-off"></i>
              <p>No hay horarios disponibles para esta fecha.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<!-- Modal detalle -->
<div class="modal-overlay" id="detalleModal">
  <div class="modal modal-detalle">
    <div class="modal-det-header">
      <div class="modal-det-icon"><i class="ti ti-calendar-event"></i></div>
      <div>
        <div class="modal-det-titulo">Detalle de cita</div>
        <div id="detEstado"></div>
      </div>
      <button class="modal-close-btn" id="detalleClose"><i class="ti ti-x"></i></button>
    </div>
    <div class="modal-det-body">
      <div class="det-row"><span class="det-label"><i class="ti ti-stethoscope"></i> Médico</span><span class="det-value" id="detMedico"></span></div>
      <div class="det-row"><span class="det-label"><i class="ti ti-clipboard-list"></i> Especialidad</span><span class="det-value" id="detEspecialidad"></span></div>
      <div class="det-row"><span class="det-label"><i class="ti ti-calendar"></i> Fecha</span><span class="det-value" id="detFecha"></span></div>
      <div class="det-row"><span class="det-label"><i class="ti ti-clock"></i> Hora</span><span class="det-value" id="detHora"></span></div>
      <div class="det-row"><span class="det-label"><i class="ti ti-notes"></i> Motivo</span><span class="det-value" id="detMotivo"></span></div>
    </div>
  </div>
</div>

<!-- Modal cancelar -->
<div class="modal-overlay" id="cancelarModal">
  <div class="modal modal-confirm">
    <div class="modal-icon"><i class="ti ti-calendar-x"></i></div>
    <h3 class="modal-title">¿Cancelar cita?</h3>
    <p class="modal-desc">Esta acción no se puede deshacer. ¿Estás seguro de cancelar tu cita?</p>
    <form method="POST" class="modal-actions">
      <input type="hidden" name="action"  value="cancelar">
      <input type="hidden" name="cita_id" id="cancelarCitaId">
      <button type="button" class="modal-btn modal-cancel" id="cancelarCancel">No, mantener</button>
      <button type="submit" class="modal-btn modal-confirm-btn">Sí, cancelar</button>
    </form>
  </div>
</div>

</div><!-- .layout -->

<script src="/CitaAgil1/assets/js/paciente/layout.js"></script>
<script src="/CitaAgil1/assets/js/paciente/citas.js"></script>
</body>
</html>