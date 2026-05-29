<?php
// ============================================================
//  includes/medico/layout.php — Sidebar + Header reutilizable
//  CitaÁgil · Sistema de citas médicas
// ============================================================
?>
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

    <div class="nav-label">General</div>
    <a href="/CitaAgil1/pages/medico/dashboard.php"
       class="nav-item <?= $current_page === 'dashboard' ? 'active' : '' ?>">
      <i class="ti ti-layout-dashboard"></i> Dashboard
    </a>

    <div class="nav-label">Agenda</div>
    <a href="/CitaAgil1/pages/medico/agenda.php"
       class="nav-item <?= $current_page === 'agenda' ? 'active' : '' ?>">
      <i class="ti ti-calendar-week"></i> Agenda
    </a>
    
    <div class="nav-label">Pacientes</div>
    <a href="/CitaAgil1/pages/medico/pacientes.php"
       class="nav-item <?= $current_page === 'pacientes' ? 'active' : '' ?>">
      <i class="ti ti-users"></i> Mis pacientes
    </a>

    <div class="nav-label">Mi cuenta</div>
    <a href="/CitaAgil1/pages/medico/disponibilidad.php"
       class="nav-item <?= $current_page === 'disponibilidad' ? 'active' : '' ?>">
      <i class="ti ti-clock"></i> Disponibilidad
    </a>
    <a href="/CitaAgil1/pages/medico/estadisticas.php"
       class="nav-item <?= $current_page === 'estadisticas' ? 'active' : '' ?>">
      <i class="ti ti-chart-bar"></i> Estadísticas
    </a>

  </nav>
</aside>

<div class="main">
  <header class="header">
    <div class="header-left">
      <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
        <i class="ti ti-menu-2"></i>
      </button>
      <div class="header-titles">
        <span class="header-title"><?= $page_title ?? 'Panel médico' ?></span>
        <span class="header-sub">CitaÁgil · <?= htmlspecialchars($medico_especialidad ?? 'Médico') ?></span>
      </div>
    </div>
    <div class="header-right">
      <span class="header-date">
        <i class="ti ti-calendar" style="vertical-align:-2px"></i>
        <?= date('d') . ' de ' . ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'][date('n')-1] . ' de ' . date('Y') ?>
      </span>

      <!-- Avatar + Dropdown -->
      <div class="avatar-wrap" id="avatarWrap">
        <div class="avatar-trigger" id="avatarBtn">
          <div class="avatar">
            <?= strtoupper(substr($_SESSION['nombre'], 0, 1) . substr($_SESSION['apellido'], 0, 1)) ?>
          </div>
          <span class="avatar-name">Dr. <?= htmlspecialchars($_SESSION['nombre']) ?></span>
          <i class="ti ti-chevron-down avatar-chevron" id="avatarChevron"></i>
        </div>
        <div class="avatar-dropdown" id="avatarDropdown">
          <div class="dropdown-info">
            <div class="dropdown-avatar">
              <?= strtoupper(substr($_SESSION['nombre'], 0, 1) . substr($_SESSION['apellido'], 0, 1)) ?>
            </div>
            <div class="dropdown-info-text">
              <div class="dropdown-name">Dr. <?= htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?></div>
              <div class="dropdown-email"><?= htmlspecialchars($_SESSION['correo']) ?></div>
            </div>
          </div>
          <div class="dropdown-divider"></div>
          <div class="dropdown-divider"></div>
          <button class="dropdown-item dropdown-logout" id="logoutBtn">
            <i class="ti ti-logout"></i> Cerrar sesión
          </button>
        </div>
      </div>
    </div>
  </header>

  <!-- Modal cerrar sesión -->
  <div class="modal-overlay" id="logoutModal">
    <div class="modal">
      <div class="modal-icon">
        <i class="ti ti-logout"></i>
      </div>
      <h3 class="modal-title">¿Cerrar sesión?</h3>
      <p class="modal-desc">Tu sesión se cerrará y tendrás que volver a iniciar sesión para acceder al sistema.</p>
      <div class="modal-actions">
        <button class="modal-btn modal-cancel" id="logoutCancel">Cancelar</button>
        <a href="/CitaAgil1/includes/logout.php" class="modal-btn modal-confirm">Sí, cerrar sesión</a>
      </div>
    </div>
  </div>