<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$nome     = $_SESSION['usuario_nome'];
$posto    = $_SESSION['posto'];
$perfil   = $_SESSION['perfil'];
$hoje     = date('d/m/Y');
$diaSem   = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'][date('w')];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Rede Ponteio</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'Segoe UI', system-ui, sans-serif;
    background: #0d1b2a;
    color: #c8d8e8;
    min-height: 100vh;
    display: flex;
  }

  /* ── SIDEBAR ── */
  .sidebar {
    width: 230px;
    background: #111e2d;
    border-right: 1px solid rgba(255,165,0,0.1);
    display: flex;
    flex-direction: column;
    padding: 0;
    flex-shrink: 0;
  }

  .sidebar-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 22px 20px;
    border-bottom: 1px solid rgba(255,165,0,0.1);
  }

  .sidebar-logo .icon { font-size: 24px; }

  .sidebar-logo .brand {
    font-size: 17px;
    font-weight: 800;
    color: #ff8c00;
    letter-spacing: 1.5px;
  }

  .sidebar-logo .sub {
    font-size: 10px;
    color: #455a6e;
    letter-spacing: 0.5px;
  }

  .user-card {
    margin: 16px 12px;
    background: rgba(255,140,0,0.07);
    border: 1px solid rgba(255,140,0,0.15);
    border-radius: 10px;
    padding: 14px;
  }

  .user-avatar {
    width: 38px; height: 38px;
    background: #ff8c00;
    color: #0f1923;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 15px;
    margin-bottom: 10px;
  }

  .user-name { color: #e8eef4; font-weight: 600; font-size: 13px; }
  .user-posto { color: #6b7f96; font-size: 11px; margin-top: 2px; }
  .user-perfil {
    display: inline-block;
    margin-top: 6px;
    background: rgba(255,140,0,0.15);
    color: #ff8c00;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    padding: 2px 8px;
    border-radius: 20px;
  }

  nav { flex: 1; padding: 8px 0; }

  nav a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: #6b7f96;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: all .15s;
    border-left: 3px solid transparent;
  }

  nav a:hover { color: #e8eef4; background: rgba(255,255,255,0.04); }
  nav a.active { color: #ff8c00; border-left-color: #ff8c00; background: rgba(255,140,0,0.07); }
  nav a .ic { font-size: 16px; width: 18px; text-align: center; }

  .nav-section {
    padding: 16px 20px 6px;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #2d4055;
  }

  .sidebar-footer {
    padding: 16px 20px;
    border-top: 1px solid rgba(255,165,0,0.1);
  }

  .btn-logout {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #455a6e;
    text-decoration: none;
    font-size: 13px;
    transition: color .15s;
  }

  .btn-logout:hover { color: #ff6b7a; }

  /* ── MAIN ── */
  .main {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: auto;
  }

  .topbar {
    background: #111e2d;
    border-bottom: 1px solid rgba(255,165,0,0.1);
    padding: 16px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .topbar-title { font-size: 18px; font-weight: 700; color: #e8eef4; }
  .topbar-date { color: #6b7f96; font-size: 13px; }

  .content { padding: 28px; }

  /* ── CARDS DE RESUMO ── */
  .cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 28px;
  }

  .card {
    background: #111e2d;
    border: 1px solid rgba(255,165,0,0.1);
    border-radius: 12px;
    padding: 20px;
    position: relative;
    overflow: hidden;
  }

  .card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--accent, #ff8c00);
  }

  .card-label { font-size: 11px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; color: #455a6e; margin-bottom: 10px; }
  .card-value { font-size: 26px; font-weight: 800; color: #e8eef4; }
  .card-sub { font-size: 12px; color: #6b7f96; margin-top: 4px; }
  .card-icon { position: absolute; top: 18px; right: 18px; font-size: 22px; opacity: 0.2; }

  /* ── AÇÕES RÁPIDAS ── */
  .section-title {
    font-size: 14px;
    font-weight: 700;
    color: #8fa3b8;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 16px;
  }

  .actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 28px;
  }

  .action-card {
    background: #111e2d;
    border: 1px solid rgba(255,165,0,0.1);
    border-radius: 12px;
    padding: 22px 20px;
    text-decoration: none;
    color: #c8d8e8;
    transition: all .2s;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }

  .action-card:hover {
    border-color: #ff8c00;
    background: rgba(255,140,0,0.05);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
  }

  .action-card .ac-icon { font-size: 28px; }
  .action-card .ac-title { font-size: 14px; font-weight: 700; color: #e8eef4; }
  .action-card .ac-desc { font-size: 12px; color: #6b7f96; line-height: 1.4; }

  /* ── ÚLTIMOS FECHAMENTOS ── */
  .table-card {
    background: #111e2d;
    border: 1px solid rgba(255,165,0,0.1);
    border-radius: 12px;
    overflow: hidden;
  }

  .table-header {
    padding: 16px 20px;
    border-bottom: 1px solid rgba(255,165,0,0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .table-header span { font-size: 14px; font-weight: 600; color: #e8eef4; }

  table { width: 100%; border-collapse: collapse; }
  th { padding: 11px 20px; text-align: left; font-size: 11px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; color: #455a6e; background: rgba(0,0,0,0.2); }
  td { padding: 13px 20px; font-size: 13px; border-top: 1px solid rgba(255,255,255,0.04); }

  .badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
  }

  .badge-ok { background: rgba(34,197,94,0.15); color: #4ade80; }
  .badge-pen { background: rgba(255,140,0,0.15); color: #ff8c00; }

  .empty-state {
    text-align: center;
    padding: 48px 20px;
    color: #455a6e;
  }
  .empty-state .icon { font-size: 40px; margin-bottom: 12px; }
  .empty-state p { font-size: 14px; }
  .empty-state a {
    display: inline-block;
    margin-top: 14px;
    background: #ff8c00;
    color: #0f1923;
    font-weight: 700;
    font-size: 13px;
    padding: 10px 22px;
    border-radius: 8px;
    text-decoration: none;
  }
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <span class="icon">⛽</span>
    <div>
      <div class="brand">PONTEIO</div>
      <div class="sub">Rede de Postos</div>
    </div>
  </div>

  <div class="user-card">
    <div class="user-avatar"><?= strtoupper(substr($nome, 0, 1)) ?></div>
    <div class="user-name"><?= htmlspecialchars($nome) ?></div>
    <div class="user-posto"><?= htmlspecialchars($posto) ?></div>
    <span class="user-perfil"><?= htmlspecialchars($perfil) ?></span>
  </div>

  <nav>
    <div class="nav-section">Menu</div>
    <a href="dashboard.php" class="active"><span class="ic">🏠</span> Início</a>
    <a href="mapa_caixa.php"><span class="ic">📋</span> Mapa de Caixa</a>
    <a href="#"><span class="ic">📊</span> Histórico</a>

    <?php if ($perfil === 'gerente'): ?>
    <div class="nav-section">Gerência</div>
    <a href="#"><span class="ic">🏪</span> Postos</a>
    <a href="#"><span class="ic">👥</span> Funcionários</a>
    <a href="#"><span class="ic">📈</span> Relatórios</a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <a href="logout.php" class="btn-logout">⬅ Sair do sistema</a>
  </div>
</aside>

<!-- CONTEÚDO PRINCIPAL -->
<div class="main">
  <div class="topbar">
    <div class="topbar-title">Dashboard</div>
    <div class="topbar-date">📅 <?= $diaSem ?>, <?= $hoje ?></div>
  </div>

  <div class="content">

    <!-- Cards de resumo -->
    <div class="cards-grid">
      <div class="card" style="--accent:#4ade80">
        <span class="card-icon">✅</span>
        <div class="card-label">Fechamentos hoje</div>
        <div class="card-value">0</div>
        <div class="card-sub">Nenhum lançado ainda</div>
      </div>
      <div class="card" style="--accent:#ff8c00">
        <span class="card-icon">💰</span>
        <div class="card-label">Total em caixa</div>
        <div class="card-value">R$ 0,00</div>
        <div class="card-sub">Aguardando lançamento</div>
      </div>
      <div class="card" style="--accent:#60a5fa">
        <span class="card-icon">💳</span>
        <div class="card-label">Total cartões</div>
        <div class="card-value">R$ 0,00</div>
        <div class="card-sub">Todos os meios</div>
      </div>
      <div class="card" style="--accent:#a78bfa">
        <span class="card-icon">📋</span>
        <div class="card-label">Diferença</div>
        <div class="card-value">R$ 0,00</div>
        <div class="card-sub">Caixa vs. depósito</div>
      </div>
    </div>

    <!-- Ações rápidas -->
    <div class="section-title">Ações rápidas</div>
    <div class="actions-grid">
      <a href="mapa_caixa.php" class="action-card">
        <span class="ac-icon">📋</span>
        <div class="ac-title">Novo Mapa de Caixa</div>
        <div class="ac-desc">Lançar o fechamento do dia com todos os meios de pagamento</div>
      </a>
      <a href="#" class="action-card">
        <span class="ac-icon">🕐</span>
        <div class="ac-title">Histórico de Caixas</div>
        <div class="ac-desc">Consultar fechamentos anteriores por data</div>
      </a>
      <?php if ($perfil === 'gerente'): ?>
      <a href="#" class="action-card">
        <span class="ac-icon">📊</span>
        <div class="ac-title">Relatório da Rede</div>
        <div class="ac-desc">Visão consolidada de todos os postos</div>
      </a>
      <?php endif; ?>
    </div>

    <!-- Últimos fechamentos -->
    <div class="section-title">Últimos fechamentos</div>
    <div class="table-card">
      <div class="table-header">
        <span>Histórico do dia</span>
      </div>
      <div class="empty-state">
        <div class="icon">📭</div>
        <p>Nenhum fechamento registrado hoje.</p>
        <a href="mapa_caixa.php">+ Lançar agora</a>
      </div>
    </div>

  </div>
</div>

</body>
</html>
