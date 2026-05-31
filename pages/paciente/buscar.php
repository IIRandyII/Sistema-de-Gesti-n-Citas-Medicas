<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole('paciente');

$current_page = 'buscar';
$page_title   = 'Buscar médico';

$pdo = getDB();

$paciente_id = $_SESSION['user_id'];

// ── AGENDAR CITA ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'agendar') {
    $medico_id = (int)($_POST['medico_id'] ?? 0);
    $fecha     = $_POST['fecha']  ?? '';
    $hora      = $_POST['hora']   ?? '';
    $motivo    = trim($_POST['motivo'] ?? '');

    $error_agendar = '';

    if (!$medico_id || !$fecha || !$hora) {
        $error_agendar = 'Datos incompletos.';
    } else {
        // Verificar que el slot sigue disponible
        $ocupado = $pdo->prepare("SELECT id FROM citas WHERE medico_id = ? AND fecha = ? AND hora = ? AND estatus IN ('pendiente','confirmada')");
        $ocupado->execute([$medico_id, $fecha, $hora]);

        if ($ocupado->fetch()) {
            $error_agendar = 'Ese horario ya fue tomado. Por favor selecciona otro.';
        } else {
            $pdo->prepare("INSERT INTO citas (paciente_id, medico_id, fecha, hora, motivo, estatus) VALUES (?, ?, ?, ?, ?, 'pendiente')")
                ->execute([$paciente_id, $medico_id, $fecha, $hora, $motivo ?: null]);
            header("Location: citas.php?agendada=1");
            exit;
        }
    }
}

// ── PARÁMETROS ──
$esp_id    = (int)($_GET['especialidad'] ?? 0);
$medico_id = (int)($_GET['medico']      ?? 0);
$fecha_sel = $_GET['fecha'] ?? '';
$paso      = (int)($_GET['paso']        ?? 1);

// ── ESPECIALIDADES ──
$especialidades = $pdo->query("SELECT * FROM especialidades ORDER BY nombre")->fetchAll();

// ── MÉDICOS POR ESPECIALIDAD ──
$medicos = [];
if ($esp_id) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.nombre, u.apellido, u.correo,
               m.id AS medico_id, m.cedula,
               e.nombre AS especialidad
        FROM medicos m
        JOIN usuarios u ON m.usuario_id = u.id
        JOIN especialidades e ON m.especialidad_id = e.id
        WHERE m.especialidad_id = ? AND u.activo = 1
        ORDER BY u.nombre
    ");
    $stmt->execute([$esp_id]);
    $medicos = $stmt->fetchAll();
}

// ── DISPONIBILIDAD DEL MÉDICO ──
$medico_sel      = null;
$dias_disponibles= [];
$slots           = [];

if ($medico_id) {
    $stmt = $pdo->prepare("
        SELECT u.nombre, u.apellido, e.nombre AS especialidad, m.cedula, m.id AS med_id
        FROM medicos m
        JOIN usuarios u ON m.usuario_id = u.id
        JOIN especialidades e ON m.especialidad_id = e.id
        WHERE u.id = ?
    ");
    $stmt->execute([$medico_id]);
    $medico_sel = $stmt->fetch();

    if ($medico_sel) {
        // Días disponibles del médico
        $stmt = $pdo->prepare("SELECT * FROM disponibilidad_medico WHERE medico_id = ? AND activo = 1");
        $stmt->execute([$medico_sel['med_id']]);
        $disp_raw = $stmt->fetchAll();

        foreach ($disp_raw as $d) {
            $dias_disponibles[$d['dia_semana']] = $d;
        }

        // Fechas bloqueadas
        $stmt = $pdo->prepare("SELECT fecha FROM excepciones_disponibilidad WHERE medico_id = ? AND fecha >= CURDATE()");
        $stmt->execute([$medico_sel['med_id']]);
        $fechas_bloqueadas = array_column($stmt->fetchAll(), 'fecha');

        // Duración de cita
        $duracion = (int)($pdo->query("SELECT valor FROM configuracion WHERE clave = 'duracion_cita'")->fetchColumn() ?? 30);

        // Slots disponibles para fecha seleccionada
        if ($fecha_sel && isset($dias_disponibles)) {
            $dt      = new DateTime($fecha_sel);
            $dia_num = (int)$dt->format('N');

            if (isset($dias_disponibles[$dia_num]) && !in_array($fecha_sel, $fechas_bloqueadas)) {
                $disp = $dias_disponibles[$dia_num];
                $inicio = new DateTime($fecha_sel . ' ' . $disp['hora_inicio']);
                $fin    = new DateTime($fecha_sel . ' ' . $disp['hora_fin']);

                // Citas ya ocupadas
                $stmt = $pdo->prepare("SELECT hora FROM citas WHERE medico_id = ? AND fecha = ? AND estatus IN ('pendiente','confirmada')");
                $stmt->execute([$medico_sel['med_id'], $fecha_sel]);
                $ocupadas = array_column($stmt->fetchAll(), 'hora');

                $current = clone $inicio;
                while ($current < $fin) {
                    $hora_str = $current->format('H:i:s');
                    $slots[] = [
                        'hora'     => $hora_str,
                        'display'  => $current->format('H:i'),
                        'ocupado'  => in_array($hora_str, $ocupadas),
                    ];
                    $current->modify("+{$duracion} minutes");
                }
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
  <link rel="stylesheet" href="/CitaAgil1/assets/css/paciente/buscar.css"/>
</head>
<body>
<div class="layout">

<?php include __DIR__ . '/../../includes/paciente/layout.php'; ?>

  <div class="content">

    <?php if (!empty($error_agendar)): ?>
      <div class="toast toast-error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error_agendar) ?>
      </div>
    <?php endif; ?>

    <div class="page-header">
      <div>
        <h1 class="page-title-text">Buscar médico</h1>
        <p class="page-sub">Encuentra un médico y agenda tu cita.</p>
      </div>
    </div>

    <!-- Pasos -->
    <div class="pasos-wrap">
      <div class="paso <?= $paso >= 1 ? 'active' : '' ?> <?= $paso > 1 ? 'done' : '' ?>">
        <div class="paso-num"><?= $paso > 1 ? '✓' : '1' ?></div>
        <span>Especialidad</span>
      </div>
      <div class="paso-line <?= $paso > 1 ? 'done' : '' ?>"></div>
      <div class="paso <?= $paso >= 2 ? 'active' : '' ?> <?= $paso > 2 ? 'done' : '' ?>">
        <div class="paso-num"><?= $paso > 2 ? '✓' : '2' ?></div>
        <span>Médico</span>
      </div>
      <div class="paso-line <?= $paso > 2 ? 'done' : '' ?>"></div>
      <div class="paso <?= $paso >= 3 ? 'active' : '' ?> <?= $paso > 3 ? 'done' : '' ?>">
        <div class="paso-num"><?= $paso > 3 ? '✓' : '3' ?></div>
        <span>Fecha y hora</span>
      </div>
      <div class="paso-line <?= $paso > 3 ? 'done' : '' ?>"></div>
      <div class="paso <?= $paso >= 4 ? 'active' : '' ?>">
        <div class="paso-num">4</div>
        <span>Confirmar</span>
      </div>
    </div>

    <!-- PASO 1: Especialidad -->
    <?php if ($paso === 1): ?>
    <div class="step-card">
      <div class="step-header">
        <i class="ti ti-clipboard-list"></i>
        <div>
          <div class="step-titulo">Selecciona una especialidad</div>
          <div class="step-sub">¿Qué tipo de médico necesitas?</div>
        </div>
      </div>
      <div class="esp-grid">
        <?php foreach ($especialidades as $e): ?>
          <a href="?paso=2&especialidad=<?= $e['id'] ?>" class="esp-card">
            <i class="ti ti-stethoscope"></i>
            <span><?= htmlspecialchars($e['nombre']) ?></span>
            <i class="ti ti-chevron-right esp-arrow"></i>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- PASO 2: Médico -->
    <?php elseif ($paso === 2 && $esp_id): ?>
    <div class="step-card">
      <div class="step-header">
        <i class="ti ti-user-search"></i>
        <div>
          <div class="step-titulo">Selecciona un médico</div>
          <div class="step-sub"><?= htmlspecialchars($especialidades[array_search($esp_id, array_column($especialidades, 'id'))]['nombre'] ?? '') ?></div>
        </div>
        <a href="?paso=1" class="btn-back"><i class="ti ti-arrow-left"></i> Volver</a>
      </div>
      <?php if (empty($medicos)): ?>
        <div class="empty-state">
          <i class="ti ti-user-off"></i>
          <p>No hay médicos disponibles para esta especialidad.</p>
          <a href="?paso=1" class="btn-volver">Elegir otra especialidad</a>
        </div>
      <?php else: ?>
        <div class="medicos-list">
          <?php foreach ($medicos as $m): ?>
            <a href="?paso=3&especialidad=<?= $esp_id ?>&medico=<?= $m['id'] ?>" class="medico-card">
              <div class="medico-avatar">
                <?= strtoupper(substr($m['nombre'], 0, 1) . substr($m['apellido'], 0, 1)) ?>
              </div>
              <div class="medico-info">
                <div class="medico-nombre">Dr. <?= htmlspecialchars($m['nombre'] . ' ' . $m['apellido']) ?></div>
                <div class="medico-esp"><?= htmlspecialchars($m['especialidad']) ?></div>
                <?php if ($m['cedula']): ?>
                  <div class="medico-cedula"><i class="ti ti-id-badge"></i> <?= htmlspecialchars($m['cedula']) ?></div>
                <?php endif; ?>
              </div>
              <div class="medico-accion">
                <span class="btn-seleccionar">Seleccionar <i class="ti ti-arrow-right"></i></span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- PASO 3: Fecha y hora -->
    <?php elseif ($paso === 3 && $medico_id && $medico_sel): ?>
    <div class="step-card">
      <div class="step-header">
        <i class="ti ti-calendar-time"></i>
        <div>
          <div class="step-titulo">Selecciona fecha y hora</div>
          <div class="step-sub">Dr. <?= htmlspecialchars($medico_sel['nombre'] . ' ' . $medico_sel['apellido']) ?> · <?= htmlspecialchars($medico_sel['especialidad']) ?></div>
        </div>
        <a href="?paso=2&especialidad=<?= $esp_id ?>" class="btn-back"><i class="ti ti-arrow-left"></i> Volver</a>
      </div>

      <div class="fecha-slots-grid">
        <!-- Calendario -->
        <div class="calendario-wrap">
          <div class="cal-header">
            <button class="cal-nav" id="calPrev"><i class="ti ti-chevron-left"></i></button>
            <span class="cal-mes" id="calMes"></span>
            <button class="cal-nav" id="calNext"><i class="ti ti-chevron-right"></i></button>
          </div>
          <div class="cal-dias-nombres">
            <?php foreach (['Lu','Ma','Mi','Ju','Vi','Sá','Do'] as $d): ?>
              <span><?= $d ?></span>
            <?php endforeach; ?>
          </div>
          <div class="cal-dias" id="calDias"></div>
        </div>

        <!-- Slots -->
        <div class="slots-wrap">
          <?php if ($fecha_sel): ?>
            <div class="slots-fecha">
              <?= (new DateTime($fecha_sel))->format('d') ?> de
              <?= ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'][(int)(new DateTime($fecha_sel))->format('n') - 1] ?>
              de <?= (new DateTime($fecha_sel))->format('Y') ?>
            </div>
            <?php if (empty($slots)): ?>
              <div class="empty-state">
                <i class="ti ti-clock-off"></i>
                <p>No hay horarios disponibles para esta fecha.</p>
              </div>
            <?php else: ?>
              <div class="slots-grid">
                <?php foreach ($slots as $s): ?>
                  <?php if (!$s['ocupado']): ?>
                    <a href="?paso=4&especialidad=<?= $esp_id ?>&medico=<?= $medico_id ?>&fecha=<?= $fecha_sel ?>&hora=<?= urlencode($s['hora']) ?>"
                       class="slot-btn">
                      <?= $s['display'] ?>
                    </a>
                  <?php else: ?>
                    <span class="slot-btn ocupado"><?= $s['display'] ?></span>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="empty-state">
              <i class="ti ti-calendar"></i>
              <p>Selecciona una fecha disponible en el calendario.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- PASO 4: Confirmar -->
    <?php elseif ($paso === 4 && $medico_id && $fecha_sel && isset($_GET['hora'])): ?>
    <?php
      $hora_sel = $_GET['hora'] ?? '';
      $stmt = $pdo->prepare("SELECT u.nombre, u.apellido, e.nombre AS especialidad, m.id AS med_id FROM medicos m JOIN usuarios u ON m.usuario_id = u.id JOIN especialidades e ON m.especialidad_id = e.id WHERE u.id = ?");
      $stmt->execute([$medico_id]);
      $med = $stmt->fetch();
    ?>
    <div class="step-card">
      <div class="step-header">
        <i class="ti ti-calendar-check"></i>
        <div>
          <div class="step-titulo">Confirmar cita</div>
          <div class="step-sub">Revisa los detalles antes de confirmar.</div>
        </div>
        <a href="?paso=3&especialidad=<?= $esp_id ?>&medico=<?= $medico_id ?>&fecha=<?= $fecha_sel ?>" class="btn-back"><i class="ti ti-arrow-left"></i> Volver</a>
      </div>

      <div class="confirm-body">
        <div class="confirm-resumen">
          <div class="confirm-row">
            <span class="confirm-label"><i class="ti ti-stethoscope"></i> Médico</span>
            <span class="confirm-value">Dr. <?= htmlspecialchars($med['nombre'] . ' ' . $med['apellido']) ?></span>
          </div>
          <div class="confirm-row">
            <span class="confirm-label"><i class="ti ti-clipboard-list"></i> Especialidad</span>
            <span class="confirm-value"><?= htmlspecialchars($med['especialidad']) ?></span>
          </div>
          <div class="confirm-row">
            <span class="confirm-label"><i class="ti ti-calendar"></i> Fecha</span>
            <span class="confirm-value">
              <?= (new DateTime($fecha_sel))->format('d') ?> de
              <?= ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'][(int)(new DateTime($fecha_sel))->format('n') - 1] ?>
              de <?= (new DateTime($fecha_sel))->format('Y') ?>
            </span>
          </div>
          <div class="confirm-row">
            <span class="confirm-label"><i class="ti ti-clock"></i> Hora</span>
            <span class="confirm-value"><?= substr($hora_sel, 0, 5) ?> hrs</span>
          </div>
        </div>

        <form method="POST" class="confirm-form">
          <input type="hidden" name="action"    value="agendar">
          <input type="hidden" name="medico_id" value="<?= $med['med_id'] ?>">
          <input type="hidden" name="fecha"     value="<?= htmlspecialchars($fecha_sel) ?>">
          <input type="hidden" name="hora"      value="<?= htmlspecialchars($hora_sel) ?>">
          <div class="form-field">
            <label>Motivo de consulta <span class="hint">(opcional)</span></label>
            <textarea name="motivo" class="motivo-textarea" placeholder="Describe brevemente el motivo de tu consulta..." rows="3"></textarea>
          </div>
          <div class="confirm-actions">
            <a href="?paso=3&especialidad=<?= $esp_id ?>&medico=<?= $medico_id ?>&fecha=<?= $fecha_sel ?>" class="btn-cancel-confirm">Cancelar</a>
            <button type="submit" class="btn-confirm">
              <i class="ti ti-calendar-check"></i> Confirmar cita
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

</div><!-- .layout -->

<script src="/CitaAgil1/assets/js/paciente/layout.js"></script>
<script>
const DIAS_DISPONIBLES  = <?= json_encode(array_keys($dias_disponibles)) ?>;
const FECHAS_BLOQUEADAS = <?= json_encode($fechas_bloqueadas ?? []) ?>;
const FECHA_SEL         = <?= json_encode($fecha_sel) ?>;
const PASO              = <?= $paso ?>;
const ESP_ID            = <?= $esp_id ?>;
const MEDICO_ID         = <?= $medico_id ?>;
</script>
<script src="/CitaAgil1/assets/js/paciente/buscar.js"></script>
</body>
</html>