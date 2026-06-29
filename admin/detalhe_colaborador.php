<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
require_auth('admin');

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: colaboradores.php'); exit; }

// Dados do colaborador
$stmt = $pdo->prepare("
    SELECT u.*, p.nome AS posto_nome
    FROM usuarios u
    LEFT JOIN postos p ON p.id = u.posto_id
    WHERE u.id = ?
");
$stmt->execute([$id]);
$colab = $stmt->fetch();
if (!$colab) { header('Location: colaboradores.php'); exit; }

// Filtro de período
$data_ini = $_GET['data_ini'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

// Caixas lançados pelo colaborador
$caixas = $pdo->prepare("
    SELECT
        mc.*,
        (mc.dinheiro
         + mc.elo_credito + mc.elo_credito_rede + mc.elo_debito_rede
         + mc.elo_parcelado + mc.elo_parcelado_rede
         + mc.master_cred_cielo + mc.master_cred_rede
         + mc.master_deb_cielo + mc.master_deb_rede
         + mc.master_parc_cielo + mc.master_parc_rede
         + mc.visa_cred_cielo + mc.visa_cred_rede
         + mc.visa_debito + mc.visa_parcelado + mc.visa_parc_rede
         + mc.amex_parc + mc.amex
         + mc.cabal_cred_rede + mc.cabal_deb_rede
         + mc.cheque_a_vista + mc.cheque_pre
         + mc.credsystem_parc_rede + mc.credsystem_rede
         + mc.diners_cred_cielo + mc.diners_parc_cielo + mc.diners_parc_rede + mc.diners_rede
         + mc.fitcard + mc.goodcard + mc.hipercard + mc.hipercard_parc
         + mc.maxxcard + mc.planvale + mc.policard + mc.vr_auto
         + mc.shell_box_paypal + mc.shell_box_1 + mc.shell_box_2 + mc.shell_box_3
         + mc.sorocred + mc.sorocred_parc + mc.ticket_card + mc.valecard + mc.valeshop + mc.ame
         + mc.nota_a_prazo + mc.despesas
        ) AS total_calc
    FROM mapas_caixa mc
    WHERE mc.usuario_id = ?
      AND mc.data_fechamento BETWEEN ? AND ?
    ORDER BY mc.data_fechamento DESC
");
$caixas->execute([$id, $data_ini, $data_fim]);
$caixas = $caixas->fetchAll();

// Totalizadores do período
$total_geral     = array_sum(array_column($caixas, 'total_pedido_caixa'));
$total_depositado= array_sum(array_column($caixas, 'total_depositado'));
$total_diferenca = array_sum(array_column($caixas, 'diferenca'));
$total_dinheiro  = array_sum(array_column($caixas, 'dinheiro'));

$hoje   = date('d/m/Y');
$diaSem = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'][date('w')];

function fmt($v) { return 'R$ ' . number_format($v, 2, ',', '.'); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detalhe — <?= htmlspecialchars($colab['nome']) ?></title>
<link rel="stylesheet" href="/ponteio/assets/css/base.css">
<style>
  .perfil-header {
    background: #111e2d;
    border: 1px solid rgba(255,165,0,0.1);
    border-radius: 12px;
    padding: 22px 24px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
  }
  .ph-avatar {
    width: 54px; height: 54px;
    border-radius: 50%;
    background: #ff8c00;
    color: #0f1923;
    font-size: 22px; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .ph-avatar.av-admin   { background: #c084fc; }
  .ph-avatar.av-gerente { background: #60a5fa; }
  .ph-nome  { font-size: 18px; font-weight: 800; color: #e8eef4; }
  .ph-meta  { font-size: 13px; color: #6b7f96; margin-top: 4px; }

  .periodo-filtro {
    background: #111e2d;
    border: 1px solid rgba(255,165,0,0.1);
    border-radius: 10px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    gap: 14px;
    align-items: flex-end;
    flex-wrap: wrap;
  }
  .periodo-filtro label { font-size: 11px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: #455a6e; display: block; margin-bottom: 6px; }
  .periodo-filtro input { background: #0d1b2a; border: 1px solid rgba(255,165,0,0.15); border-radius: 8px; color: #e8eef4; font-size: 13px; padding: 9px 12px; outline: none; }
  .periodo-filtro input:focus { border-color: #ff8c00; }

  .resumo-periodo {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 14px;
    margin-bottom: 22px;
  }
  .rp-card {
    background: #111e2d;
    border: 1px solid rgba(255,165,0,0.1);
    border-radius: 10px;
    padding: 16px;
    position: relative; overflow: hidden;
  }
  .rp-card::before { content:''; position:absolute; top:0;left:0;right:0; height:3px; background: var(--ac,#ff8c00); }
  .rp-label { font-size: 10px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; color: #455a6e; margin-bottom: 6px; }
  .rp-value { font-size: 18px; font-weight: 800; color: #e8eef4; }

  .dif-pos { color: #4ade80 !important; }
  .dif-neg { color: #ff6b7a !important; }
  .dif-zero{ color: #4ade80 !important; }

  .detalhe-modal {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.65);
    z-index: 100;
    align-items: center; justify-content: center;
  }
  .detalhe-modal.open { display: flex; }
  .dm-box {
    background: #111e2d;
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 14px;
    width: 100%; max-width: 600px;
    max-height: 85vh; overflow-y: auto;
    padding: 28px;
  }
  .dm-title { font-size: 15px; font-weight: 700; color: #e8eef4; margin-bottom: 20px; }
  .dm-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .dm-item  { background: #0d1b2a; border-radius: 8px; padding: 12px; }
  .dm-item-label { font-size: 10px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: #455a6e; margin-bottom: 4px; }
  .dm-item-value { font-size: 14px; font-weight: 700; color: #e8eef4; }
  .dm-item-value.destaque { color: #ff8c00; font-size: 18px; }
</style>
</head>
<body>

<?php require '../includes/sidebar.php'; ?>

<div class="main">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:12px">
      <a href="colaboradores.php" style="color:#6b7f96;text-decoration:none;font-size:13px">← Colaboradores</a>
      <div class="topbar-title">📋 Histórico de Caixas</div>
    </div>
    <div class="topbar-date">📅 <?= $diaSem ?>, <?= $hoje ?></div>
  </div>

  <div class="content">

    <!-- PERFIL DO COLABORADOR -->
    <div class="perfil-header">
      <div class="ph-avatar av-<?= $colab['perfil'] ?>"><?= strtoupper(substr($colab['nome'],0,1)) ?></div>
      <div>
        <div class="ph-nome"><?= htmlspecialchars($colab['nome']) ?></div>
        <div class="ph-meta">
          @<?= htmlspecialchars($colab['usuario']) ?>
          &nbsp;·&nbsp;
          <?= $colab['posto_nome'] ?? 'Sem posto' ?>
          &nbsp;·&nbsp;
          <span class="badge badge-<?= $colab['perfil'] ?>">
            <?= $colab['perfil'] === 'admin' ? '🔑 Admin' : ($colab['perfil'] === 'gerente' ? '🏪 Gerente' : '⛽ Frentista') ?>
          </span>
          &nbsp;·&nbsp;
          <span class="badge <?= $colab['ativo'] ? 'badge-ok' : 'badge-inativo' ?>">
            <?= $colab['ativo'] ? 'Ativo' : 'Inativo' ?>
          </span>
        </div>
      </div>
      <div style="margin-left:auto">
        <a href="usuarios.php?editar=<?= $colab['id'] ?>" class="btn btn-secondary btn-sm">✏ Editar usuário</a>
      </div>
    </div>

    <!-- FILTRO DE PERÍODO -->
    <form method="GET">
      <input type="hidden" name="id" value="<?= $id ?>">
      <div class="periodo-filtro">
        <div>
          <label>Data inicial</label>
          <input type="date" name="data_ini" value="<?= $data_ini ?>">
        </div>
        <div>
          <label>Data final</label>
          <input type="date" name="data_fim" value="<?= $data_fim ?>">
        </div>
        <button type="submit" class="btn btn-primary">Filtrar</button>
      </div>
    </form>

    <!-- RESUMO DO PERÍODO -->
    <div class="resumo-periodo">
      <div class="rp-card" style="--ac:#ff8c00">
        <div class="rp-label">Caixas no período</div>
        <div class="rp-value"><?= count($caixas) ?></div>
      </div>
      <div class="rp-card" style="--ac:#60a5fa">
        <div class="rp-label">Total pedido</div>
        <div class="rp-value" style="font-size:15px"><?= fmt($total_geral) ?></div>
      </div>
      <div class="rp-card" style="--ac:#4ade80">
        <div class="rp-label">Total depositado</div>
        <div class="rp-value" style="font-size:15px"><?= fmt($total_depositado) ?></div>
      </div>
      <div class="rp-card" style="--ac:<?= $total_diferenca < 0 ? '#ff6b7a' : '#4ade80' ?>">
        <div class="rp-label">Diferença acumulada</div>
        <div class="rp-value <?= $total_diferenca < 0 ? 'dif-neg' : ($total_diferenca > 0 ? 'dif-pos' : 'dif-zero') ?>" style="font-size:15px">
          <?= fmt($total_diferenca) ?>
        </div>
      </div>
      <div class="rp-card" style="--ac:#f59e0b">
        <div class="rp-label">Total dinheiro</div>
        <div class="rp-value" style="font-size:15px"><?= fmt($total_dinheiro) ?></div>
      </div>
    </div>

    <!-- LISTA DE CAIXAS -->
    <div class="table-card">
      <div class="table-header">
        <span>Fechamentos de caixa</span>
        <span style="font-size:12px;color:#6b7f96"><?= date('d/m/Y', strtotime($data_ini)) ?> até <?= date('d/m/Y', strtotime($data_fim)) ?></span>
      </div>
      <table>
        <thead>
          <tr>
            <th>Data</th>
            <th>Dinheiro</th>
            <th>Cartões</th>
            <th>Nota a Prazo</th>
            <th>Despesas</th>
            <th>Total Pedido</th>
            <th>Depositado</th>
            <th>Diferença</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($caixas)): ?>
          <tr>
            <td colspan="9" style="text-align:center;color:#455a6e;padding:40px">
              Nenhum caixa lançado no período selecionado.
            </td>
          </tr>
          <?php endif; ?>
          <?php foreach ($caixas as $cx):
            $total_cartoes = $cx['total_pedido_caixa'] - $cx['dinheiro'] - $cx['nota_a_prazo'] - $cx['despesas'];
            $dif = $cx['diferenca'];
            $dif_class = $dif < 0 ? 'dif-neg' : ($dif > 0 ? 'dif-pos' : 'dif-zero');
          ?>
          <tr>
            <td style="font-weight:600;color:#e8eef4">
              <?= date('d/m/Y', strtotime($cx['data_fechamento'])) ?>
              <div style="font-size:11px;color:#455a6e"><?= ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'][date('w', strtotime($cx['data_fechamento']))] ?></div>
            </td>
            <td><?= fmt($cx['dinheiro']) ?></td>
            <td><?= fmt(max(0, $total_cartoes)) ?></td>
            <td><?= fmt($cx['nota_a_prazo']) ?></td>
            <td><?= fmt($cx['despesas']) ?></td>
            <td style="font-weight:700;color:#ff8c00"><?= fmt($cx['total_pedido_caixa']) ?></td>
            <td><?= fmt($cx['total_depositado']) ?></td>
            <td class="<?= $dif_class ?>" style="font-weight:700"><?= fmt($dif) ?></td>
            <td>
              <button class="btn btn-secondary btn-sm"
                onclick='verDetalhe(<?= htmlspecialchars(json_encode($cx)) ?>)'>
                🔍 Detalhe
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- MODAL DETALHE DO CAIXA -->
<div class="detalhe-modal" id="detalheModal">
  <div class="dm-box">
    <div class="dm-title" id="dmTitulo">📋 Detalhe do Caixa</div>
    <div class="dm-grid" id="dmGrid"></div>
    <div style="text-align:right;margin-top:20px">
      <button class="btn btn-secondary" onclick="fecharDetalhe()">Fechar</button>
    </div>
  </div>
</div>

<script>
const labels = {
  dinheiro:'Dinheiro',
  elo_credito:'ELO Crédito', elo_credito_rede:'ELO Crédito Rede',
  elo_debito_rede:'ELO Débito Rede', elo_parcelado:'ELO Parcelado', elo_parcelado_rede:'ELO Parcelado Rede',
  master_cred_cielo:'Master Cred Cielo', master_cred_rede:'Master Cred Rede',
  master_deb_cielo:'Master Déb Cielo', master_deb_rede:'Master Déb Rede',
  master_parc_cielo:'Master Parc Cielo', master_parc_rede:'Master Parc Rede',
  visa_cred_cielo:'Visa Cred Cielo', visa_cred_rede:'Visa Cred Rede',
  visa_debito:'Visa Débito', visa_parcelado:'Visa Parcelado', visa_parc_rede:'Visa Parc Rede',
  amex_parc:'Amex Parc', amex:'Amex',
  cabal_cred_rede:'Cabal Cred Rede', cabal_deb_rede:'Cabal Déb Rede',
  cheque_a_vista:'Cheque à Vista', cheque_pre:'Cheque Pré',
  credsystem_parc_rede:'Credsystem Parc', credsystem_rede:'Credsystem Rede',
  diners_cred_cielo:'Diners Cred Cielo', diners_parc_cielo:'Diners Parc Cielo',
  diners_parc_rede:'Diners Parc Rede', diners_rede:'Diners Rede',
  fitcard:'Fitcard', goodcard:'Goodcard', hipercard:'Hipercard', hipercard_parc:'Hipercard Parc',
  maxxcard:'Maxxcard', planvale:'Planvale', policard:'Policard', vr_auto:'VR Auto',
  shell_box_paypal:'Shell Box Paypal', shell_box_1:'Shell Box 1', shell_box_2:'Shell Box 2', shell_box_3:'Shell Box 3',
  sorocred:'Sorocred', sorocred_parc:'Sorocred Parc', ticket_card:'Ticket Card',
  valecard:'Valecard', valeshop:'Valeshop', ame:'AME',
  nota_a_prazo:'Nota a Prazo', despesas:'Despesas',
  total_pedido_caixa:'TOTAL PEDIDO', total_depositado:'TOTAL DEPOSITADO', diferenca:'DIFERENÇA'
};

const totais = ['total_pedido_caixa','total_depositado','diferenca'];
const skip   = ['id','usuario_id','posto_id','enviado_em','total_calc'];

function fmt(v) {
  return 'R$ ' + parseFloat(v).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
}

function verDetalhe(cx) {
  document.getElementById('dmTitulo').textContent =
    '📋 Caixa de ' + new Date(cx.data_fechamento + 'T12:00:00').toLocaleDateString('pt-BR');

  let html = '';
  for (const [k, v] of Object.entries(cx)) {
    if (skip.includes(k) || !labels[k]) continue;
    const val = parseFloat(v);
    if (val === 0 && !totais.includes(k)) continue;
    const destaque = totais.includes(k) ? ' destaque' : '';
    html += `<div class="dm-item">
      <div class="dm-item-label">${labels[k]}</div>
      <div class="dm-item-value${destaque}">${fmt(v)}</div>
    </div>`;
  }

  document.getElementById('dmGrid').innerHTML = html || '<p style="color:#455a6e">Todos os valores são zero.</p>';
  document.getElementById('detalheModal').classList.add('open');
}

function fecharDetalhe() {
  document.getElementById('detalheModal').classList.remove('open');
}

document.getElementById('detalheModal').addEventListener('click', function(e) {
  if (e.target === this) fecharDetalhe();
});
</script>
</body>
</html>
