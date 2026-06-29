<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
require_auth('admin');

// Filtros
$filtro_perfil = $_GET['perfil'] ?? '';
$filtro_posto  = $_GET['posto']  ?? '';
$filtro_busca  = trim($_GET['busca'] ?? '');

$where  = ['1=1'];
$params = [];

if ($filtro_perfil) { $where[] = 'u.perfil = ?';    $params[] = $filtro_perfil; }
if ($filtro_posto)  { $where[] = 'u.posto_id = ?';  $params[] = $filtro_posto;  }
if ($filtro_busca)  { $where[] = '(u.nome LIKE ? OR u.usuario LIKE ?)'; $params[] = "%$filtro_busca%"; $params[] = "%$filtro_busca%"; }

$where_sql = implode(' AND ', $where);

$colaboradores = $pdo->prepare("
    SELECT
        u.id, u.nome, u.usuario, u.perfil, u.ativo,
        p.nome AS posto_nome,
        COUNT(mc.id)          AS total_caixas,
        MAX(mc.data_fechamento) AS ultimo_caixa,
        COALESCE(SUM(mc.total_pedido_caixa), 0) AS total_movimentado
    FROM usuarios u
    LEFT JOIN postos p       ON p.id  = u.posto_id
    LEFT JOIN mapas_caixa mc ON mc.usuario_id = u.id
    WHERE $where_sql
    GROUP BY u.id, u.nome, u.usuario, u.perfil, u.ativo, p.nome
    ORDER BY u.perfil, u.nome
");
$colaboradores->execute($params);
$colaboradores = $colaboradores->fetchAll();

$postos = $pdo->query("SELECT id, nome FROM postos WHERE ativo=1 ORDER BY nome")->fetchAll();

$hoje   = date('d/m/Y');
$diaSem = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'][date('w')];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Colaboradores — Ponteio</title>
<link rel="stylesheet" href="/ponteio/assets/css/base.css">
<style>
  .filtros {
    background: #111e2d;
    border: 1px solid rgba(255,165,0,0.1);
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 20px;
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    align-items: flex-end;
  }
  .filtro-campo { display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 160px; }
  .filtro-campo label { font-size: 11px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: #455a6e; }
  .filtro-campo input,
  .filtro-campo select {
    background: #0d1b2a;
    border: 1px solid rgba(255,165,0,0.15);
    border-radius: 8px;
    color: #e8eef4;
    font-size: 13px;
    padding: 9px 12px;
    outline: none;
  }
  .filtro-campo input:focus,
  .filtro-campo select:focus { border-color: #ff8c00; }

  .colab-avatar {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 14px;
    flex-shrink: 0;
    color: #0f1923;
  }
  .av-admin    { background: #c084fc; }
  .av-gerente  { background: #60a5fa; }
  .av-frentista{ background: #ff8c00; }

  .stat-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(255,140,0,0.08);
    border: 1px solid rgba(255,165,0,0.15);
    color: #ff8c00;
    font-size: 12px; font-weight: 700;
    padding: 4px 10px; border-radius: 20px;
  }

  .resumo-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
  }
  .resumo-card {
    background: #111e2d;
    border: 1px solid rgba(255,165,0,0.1);
    border-radius: 10px;
    padding: 16px;
    position: relative;
    overflow: hidden;
  }
  .resumo-card::before { content:''; position:absolute; top:0;left:0;right:0; height:3px; background: var(--ac,#ff8c00); }
  .rc-label { font-size: 11px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: #455a6e; margin-bottom: 8px; }
  .rc-value { font-size: 22px; font-weight: 800; color: #e8eef4; }
</style>
</head>
<body>

<?php require '../includes/sidebar.php'; ?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">👥 Consulta de Colaboradores</div>
    <div class="topbar-date">📅 <?= $diaSem ?>, <?= $hoje ?></div>
  </div>

  <div class="content">

    <!-- CARDS DE RESUMO -->
    <?php
      $total_colab    = count($colaboradores);
      $total_ativos   = count(array_filter($colaboradores, fn($u) => $u['ativo']));
      $total_caixas   = array_sum(array_column($colaboradores, 'total_caixas'));
      $admins         = count(array_filter($colaboradores, fn($u) => $u['perfil'] === 'admin'));
      $gerentes       = count(array_filter($colaboradores, fn($u) => $u['perfil'] === 'gerente'));
      $frentistas     = count(array_filter($colaboradores, fn($u) => $u['perfil'] === 'frentista'));
    ?>
    <div class="resumo-cards">
      <div class="resumo-card" style="--ac:#ff8c00">
        <div class="rc-label">Total colaboradores</div>
        <div class="rc-value"><?= $total_colab ?></div>
      </div>
      <div class="resumo-card" style="--ac:#4ade80">
        <div class="rc-label">Ativos</div>
        <div class="rc-value"><?= $total_ativos ?></div>
      </div>
      <div class="resumo-card" style="--ac:#60a5fa">
        <div class="rc-label">Frentistas</div>
        <div class="rc-value"><?= $frentistas ?></div>
      </div>
      <div class="resumo-card" style="--ac:#c084fc">
        <div class="rc-label">Gerentes</div>
        <div class="rc-value"><?= $gerentes ?></div>
      </div>
      <div class="resumo-card" style="--ac:#f59e0b">
        <div class="rc-label">Caixas lançados</div>
        <div class="rc-value"><?= $total_caixas ?></div>
      </div>
    </div>

    <!-- FILTROS -->
    <form method="GET">
      <div class="filtros">
        <div class="filtro-campo" style="flex:2;min-width:200px">
          <label>🔍 Buscar</label>
          <input type="text" name="busca" placeholder="Nome ou login..." value="<?= htmlspecialchars($filtro_busca) ?>">
        </div>
        <div class="filtro-campo">
          <label>Perfil</label>
          <select name="perfil">
            <option value="">Todos</option>
            <option value="frentista" <?= $filtro_perfil==='frentista'?'selected':'' ?>>Frentista</option>
            <option value="gerente"   <?= $filtro_perfil==='gerente'  ?'selected':'' ?>>Gerente</option>
            <option value="admin"     <?= $filtro_perfil==='admin'    ?'selected':'' ?>>Admin</option>
          </select>
        </div>
        <div class="filtro-campo">
          <label>Posto</label>
          <select name="posto">
            <option value="">Todos</option>
            <?php foreach ($postos as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $filtro_posto==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="align-self:flex-end">Filtrar</button>
        <?php if ($filtro_busca || $filtro_perfil || $filtro_posto): ?>
          <a href="colaboradores.php" class="btn btn-secondary" style="align-self:flex-end">Limpar</a>
        <?php endif; ?>
      </div>
    </form>

    <!-- TABELA -->
    <div class="table-card">
      <div class="table-header">
        <span>Colaboradores <?= $filtro_busca || $filtro_perfil || $filtro_posto ? '(filtrado)' : '' ?></span>
        <a href="usuarios.php" class="btn btn-primary btn-sm">+ Novo usuário</a>
      </div>
      <table>
        <thead>
          <tr>
            <th>Colaborador</th>
            <th>Login</th>
            <th>Perfil</th>
            <th>Posto</th>
            <th>Caixas lançados</th>
            <th>Último caixa</th>
            <th>Status</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($colaboradores)): ?>
          <tr><td colspan="8" style="text-align:center;color:#455a6e;padding:32px">Nenhum colaborador encontrado.</td></tr>
          <?php endif; ?>
          <?php foreach ($colaboradores as $c): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div class="colab-avatar av-<?= $c['perfil'] ?>"><?= strtoupper(substr($c['nome'],0,1)) ?></div>
                <span style="font-weight:600;color:#e8eef4"><?= htmlspecialchars($c['nome']) ?></span>
              </div>
            </td>
            <td style="font-family:monospace;color:#6b7f96"><?= htmlspecialchars($c['usuario']) ?></td>
            <td>
              <span class="badge badge-<?= $c['perfil'] ?>">
                <?= $c['perfil'] === 'admin' ? '🔑 Admin' : ($c['perfil'] === 'gerente' ? '🏪 Gerente' : '⛽ Frentista') ?>
              </span>
            </td>
            <td><?= htmlspecialchars($c['posto_nome'] ?? '—') ?></td>
            <td>
              <?php if ($c['total_caixas'] > 0): ?>
                <span class="stat-pill">📋 <?= $c['total_caixas'] ?> caixa<?= $c['total_caixas'] > 1 ? 's' : '' ?></span>
              <?php else: ?>
                <span style="color:#455a6e;font-size:12px">Nenhum</span>
              <?php endif; ?>
            </td>
            <td style="color:#6b7f96;font-size:12px">
              <?= $c['ultimo_caixa'] ? date('d/m/Y', strtotime($c['ultimo_caixa'])) : '—' ?>
            </td>
            <td>
              <span class="badge <?= $c['ativo'] ? 'badge-ok' : 'badge-inativo' ?>">
                <?= $c['ativo'] ? 'Ativo' : 'Inativo' ?>
              </span>
            </td>
            <td>
              <a href="detalhe_colaborador.php?id=<?= $c['id'] ?>" class="btn btn-secondary btn-sm">
                👁 Ver caixas
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

</body>
</html>
