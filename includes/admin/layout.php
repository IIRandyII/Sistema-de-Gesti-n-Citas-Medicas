<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="4" width="18" height="18" rx="2"/>
        <line x1="16" y1="2" x2="16" y2="6"/>
        <line x1="8"  y1="2" x2="8"  y2="6"/>
        <line x1="3"  y1="10" x2="21" y2="10"/>
        <polyline points="9 16 11 18 15 14"/>
      </svg>
    </div>
    <div class="logo-text">Cita<span>Ágil</span></div>
  </div>

  <nav class="sidebar-nav">

    <div class="nav-label">Gestión</div>
    <a href="/CitaAgil1/pages/admin/dashboard.php"
       class="nav-item <?= $current_page === 'dashboard' ? 'active' : '' ?>">
      <i class="ti ti-calendar"></i> dashboard
    </a>

    <a href="/CitaAgil1/pages/admin/citas.php"
       class="nav-item <?= $current_page === 'citas' ? 'active' : '' ?>">
      <i class="ti ti-users"></i> Citas
    </a>

    <a href="/CitaAgil1/pages/admin/pacientes.php"
       class="nav-item <?= $current_page === 'pacientes' ? 'active' : '' ?>">
      <i class="ti ti-users"></i> Pacientes
    </a>
    <a href="/CitaAgil1/pages/admin/medicos.php"
       class="nav-item <?= $current_page === 'medicos' ? 'active' : '' ?>">
      <i class="ti ti-stethoscope"></i> Médicos
    </a>
    <a href="/CitaAgil1/pages/admin/especialidades.php"
       class="nav-item <?= $current_page === 'especialidades' ? 'active' : '' ?>">
      <i class="ti ti-clipboard-list"></i> Especialidades
    </a>

    <div class="nav-label">Reportes</div>
    <a href="/CitaAgil1/pages/admin/reportes.php"
       class="nav-item <?= $current_page === 'reportes' ? 'active' : '' ?>">
      <i class="ti ti-report"></i> Reportes
    </a>
    <a href="/CitaAgil1/pages/admin/estadisticas.php"
       class="nav-item <?= $current_page === 'estadisticas' ? 'active' : '' ?>">
      <i class="ti ti-chart-bar"></i> Estadísticas
    </a>

    <div class="nav-label">Sistema</div>
    <a href="/CitaAgil1/pages/admin/usuarios.php"
       class="nav-item <?= $current_page === 'usuarios' ? 'active' : '' ?>">
      <i class="ti ti-shield-check"></i> Usuarios
    </a>
    <a href="/CitaAgil1/pages/admin/actividad.php"
       class="nav-item <?= $current_page === 'actividad' ? 'active' : '' ?>">
      <i class="ti ti-activity"></i> Actividad
    </a>
    <a href="/CitaAgil1/pages/admin/configuracion.php"
       class="nav-item <?= $current_page === 'configuracion' ? 'active' : '' ?>">
      <i class="ti ti-settings"></i> Configuración
    </a>

  </nav>

  <div class="sidebar-footer">
    <a href="/CitaAgil1/includes/logout.php" class="nav-item logout">
      <i class="ti ti-logout"></i> Cerrar sesión
    </a>
  </div>
</aside>

<div class="main">
  <header class="header">
    <div class="header-left">
      <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="ti ti-menu-2"></i>
      </button>
      <div class="header-titles">
        <span class="header-title"><?= $page_title ?? 'Panel de administración' ?></span>
        <span class="header-sub">CitaÁgil · Sistema de citas médicas</span>
      </div>
    </div>
    <div class="header-right">
      <span class="header-date">
        <i class="ti ti-calendar" style="vertical-align:-2px"></i>
        <?= date('d') . ' de ' . ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'][date('n')-1] . ' de ' . date('Y') ?>
      </span>
      <div class="avatar" title="<?= htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?>">
        <?= strtoupper(substr($_SESSION['nombre'], 0, 1) . substr($_SESSION['apellido'], 0, 1)) ?>
      </div>
    </div>
  </header>