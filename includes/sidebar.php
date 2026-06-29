<?php
$_perfil      = $_SESSION['perfil']       ?? '';
$_nome        = $_SESSION['usuario_nome'] ?? '';
$_posto_nome  = $_SESSION['posto']        ?? '';
$_pag_atual   = basename($_SERVER['PHP_SELF']);

function nav_link($href, $icon, $label, $atual) {
    $active = (basename($href) === $atual) ? ' class="active"' : '';
    echo "<a href=\"$href\"$active><span class=\"ic\">$icon</span> $label</a>";
}
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <span class="icon">⛽</span>
    <div>
      <div class="brand">PONTEIO</div>
      <div class="sub">Rede de Postos</div>
    </div>
  </div>

  <div class="user-card">
    <div class="user-avatar"><?= strtoupper(substr($_nome, 0, 1)) ?></div>
    <div class="user-name"><?= htmlspecialchars($_nome) ?></div>
    <div class="user-posto"><?= htmlspecialchars($_posto_nome) ?></div>
    <span class="user-perfil"><?= htmlspecialchars($_perfil) ?></span>
  </div>

  <nav>
    <div class="nav-section">Menu</div>
    <?php nav_link('/ponteio/dashboard.php',  '🏠', 'Início',        $_pag_atual) ?>
    <?php nav_link('/ponteio/mapa_caixa.php', '📋', 'Mapa de Caixa',$_pag_atual) ?>
    <?php nav_link('/ponteio/historico.php',  '🕐', 'Histórico',    $_pag_atual) ?>

    <?php if (in_array($_perfil, ['admin','gerente'])): ?>
    <div class="nav-section">Gerência</div>
    <?php nav_link('/ponteio/fechamentos.php','📊','Fechamentos do Posto',$_pag_atual) ?>
    <?php endif; ?>

    <?php if ($_perfil === 'admin'): ?>
    <div class="nav-section">Administração</div>
    <?php nav_link('/ponteio/admin/colaboradores.php','👥','Colaboradores', $_pag_atual) ?>
    <?php nav_link('/ponteio/admin/usuarios.php',     '⚙', 'Usuários',     $_pag_atual) ?>
    <?php nav_link('/ponteio/admin/postos.php',       '🏪','Postos',       $_pag_atual) ?>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <a href="/ponteio/logout.php" class="btn-logout">⬅ Sair do sistema</a>
  </div>
</aside>
