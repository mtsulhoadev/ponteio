<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

require 'includes/db.php';

$nome    = $_SESSION['usuario_nome'];
$posto   = $_SESSION['posto'];
$perfil  = $_SESSION['perfil'];
$hoje    = date('Y-m-d');
$hojeF   = date('d/m/Y');

$sucesso = false;
$dados   = null;

$cartoes_esquerda = [
    'elo_credito'           => 'ELO CRÉDITO',
    'elo_credito_rede'      => 'ELO CRÉDITO - REDE',
    'elo_debito_rede'       => 'ELO DÉBITO - REDE',
    'elo_parcelado'         => 'ELO PARCELADO',
    'elo_parcelado_rede'    => 'ELO PARCELADO - REDE',
    'master_cred_cielo'     => 'MASTER CRÉDITO - CIELO',
    'master_cred_rede'      => 'MASTER CRÉDITO - REDE',
    'master_deb_cielo'      => 'MASTER DÉBITO - CIELO',
    'master_deb_rede'       => 'MASTER DÉBITO - REDE',
    'master_parc_cielo'     => 'MASTER PARCELADO - CIELO',
    'master_parc_rede'      => 'MASTER PARCELADO - REDE',
    'visa_cred_cielo'       => 'VISA CRÉDITO - CIELO',
    'visa_cred_rede'        => 'VISA CRÉDITO - REDE',
    'visa_debito'           => 'VISA DÉBITO',
    'visa_parcelado'        => 'VISA PARCELADO',
    'visa_parc_rede'        => 'VISA PARCELADO - REDE',
    'amex_parc'             => 'AMEX PARC',
    'amex'                  => 'AMEX',
    'cabal_cred_rede'       => 'CABAL CRED - REDE',
    'cabal_deb_rede'        => 'CABAL DEB - REDE',
    'cheque_a_vista'        => 'CHEQUE À VISTA',
    'cheque_pre'            => 'CHEQUE PRÉ',
    'credsystem_parc_rede'  => 'CREDSYSTEM PARC REDE',
    'credsystem_rede'       => 'CREDSYSTEM - REDE',
    'diners_cred_cielo'     => 'DINERS CRED - CIELO',
    'diners_parc_cielo'     => 'DINERS PARC - CIELO',
    'diners_parc_rede'      => 'DINERS PARC - REDE',
    'diners_rede'           => 'DINERS - REDE',
    'fitcard'               => 'FITCARD',
    'goodcard'              => 'GOODCARD',
    'hipercard'             => 'HIPERCARD',
    'hipercard_parc'        => 'HIPERCARD PARC',
];

$cartoes_direita = [
    'maxxcard'         => 'MAXXCARD',
    'planvale'         => 'PLANVALE',
    'policard'         => 'POLICARD',
    'vr_auto'          => 'VR AUTO',
    'shell_box_paypal' => 'SHELL BOX - PAYPAL',
    'shell_box_1'      => 'SHELL BOX -',
    'shell_box_2'      => 'SHELL BOX -',
    'shell_box_3'      => 'SHELL BOX -',
    'sorocred'         => 'SOROCRED',
    'sorocred_parc'    => 'SOROCRED PARC',
    'ticket_card'      => 'TICKET CARD',
    'valecard'         => 'VALECARD',
    'valeshop'         => 'VALESHOP',
    'ame'              => 'AME',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p = $_POST;

    $v = function($k) use ($p) {
        return floatval(str_replace(',', '.', $p[$k] ?? '0'));
    };

    $total_esq = array_sum(array_map($v, array_keys($cartoes_esquerda)));
    $total_dir = array_sum(array_map($v, array_keys($cartoes_direita)));

    $dinheiro         = $v('dinheiro');
    $nota_a_prazo     = $v('nota_a_prazo');
    $despesas         = $v('despesas');
    $total_depositado = $v('total_depositado');
    $total_pedido     = $dinheiro + $total_esq + $total_dir + $nota_a_prazo + $despesas;
    $diferenca        = $total_pedido - $total_depositado;
    $data_fecha       = $p['data'] ?? $hoje;

    // Monta SQL dinâmico com todas as colunas
    $campos = array_merge(
        ['usuario_id', 'posto_id', 'data_fechamento', 'dinheiro'],
        array_keys($cartoes_esquerda),
        array_keys($cartoes_direita),
        ['nota_a_prazo', 'despesas', 'total_pedido_caixa', 'total_depositado', 'diferenca']
    );

    $placeholders = implode(', ', array_fill(0, count($campos), '?'));
    $cols = implode(', ', $campos);

    $valores = [
        $_SESSION['usuario_id'],
        $_SESSION['posto_id'],
        $data_fecha,
        $dinheiro,
    ];
    foreach (array_keys($cartoes_esquerda) as $k) $valores[] = $v($k);
    foreach (array_keys($cartoes_direita)  as $k) $valores[] = $v($k);
    $valores[] = $nota_a_prazo;
    $valores[] = $despesas;
    $valores[] = $total_pedido;
    $valores[] = $total_depositado;
    $valores[] = $diferenca;

    $stmt = $pdo->prepare("INSERT INTO mapas_caixa ($cols) VALUES ($placeholders)");
    $stmt->execute($valores);

    $sucesso = true;
    $dados = compact('dinheiro', 'total_depositado', 'total_pedido', 'diferenca');
    $dados['total_cartoes'] = $total_esq + $total_dir;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mapa de Caixa — Ponteio</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Segoe UI', system-ui, sans-serif; background: #0d1b2a; color: #c8d8e8; min-height: 100vh; }
  .topbar { background: #111e2d; border-bottom: 1px solid rgba(255,165,0,0.1); padding: 14px 24px; display: flex; align-items: center; gap: 16px; }
  .back-link { color: #6b7f96; text-decoration: none; font-size: 13px; }
  .back-link:hover { color: #ff8c00; }
  .topbar-title { font-size: 17px; font-weight: 700; color: #e8eef4; }
  .topbar-right { margin-left: auto; display: flex; align-items: center; gap: 16px; font-size: 13px; color: #6b7f96; }
  .user-badge { background: rgba(255,140,0,0.1); border: 1px solid rgba(255,140,0,0.2); color: #ff8c00; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
  .container { max-width: 1200px; margin: 28px auto; padding: 0 20px; }
  .sucesso-banner { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.3); border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 16px; }
  .sucesso-icon { font-size: 28px; flex-shrink: 0; }
  .sucesso-title { font-size: 16px; font-weight: 700; color: #4ade80; margin-bottom: 6px; }
  .sucesso-meta { font-size: 13px; color: #6b7f96; }
  .sucesso-totais { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-top: 16px; }
  .st-item { background: rgba(0,0,0,0.3); border-radius: 8px; padding: 12px; }
  .st-label { font-size: 10px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; color: #455a6e; margin-bottom: 4px; }
  .st-value { font-size: 18px; font-weight: 800; color: #e8eef4; }
  .st-value.verde { color: #4ade80; } .st-value.vermelho { color: #ff6b7a; } .st-value.amarelo { color: #ff8c00; }
  .form-card { background: #111e2d; border: 1px solid rgba(255,165,0,0.1); border-radius: 14px; overflow: hidden; }
  .form-header { background: rgba(255,140,0,0.06); border-bottom: 1px solid rgba(255,165,0,0.12); padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; }
  .form-title { font-size: 16px; font-weight: 700; color: #e8eef4; }
  .meta-fields { display: flex; gap: 16px; flex-wrap: wrap; }
  .meta-field { display: flex; flex-direction: column; gap: 4px; }
  .meta-field label { font-size: 10px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; color: #455a6e; }
  .meta-field input { background: #0d1b2a; border: 1px solid rgba(255,165,0,0.15); border-radius: 7px; color: #e8eef4; font-size: 13px; padding: 8px 12px; min-width: 140px; outline: none; }
  .meta-field input:focus { border-color: #ff8c00; }
  .form-body { padding: 24px; }
  .colunas-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
  @media (max-width: 900px) { .colunas-grid { grid-template-columns: 1fr; } }
  .coluna-header { font-size: 11px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; color: #455a6e; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.06); margin-bottom: 8px; display: grid; grid-template-columns: 1fr 110px; gap: 8px; }
  .row-cartao { display: grid; grid-template-columns: 1fr 110px; gap: 8px; align-items: center; padding: 4px 0; border-bottom: 1px solid rgba(255,255,255,0.03); }
  .row-cartao:hover { background: rgba(255,255,255,0.02); margin: 0 -4px; padding: 4px 4px; border-radius: 4px; }
  .cartao-nome { font-size: 12px; color: #8fa3b8; font-weight: 500; }
  .input-valor { background: #0d1b2a; border: 1px solid rgba(255,255,255,0.06); border-radius: 6px; color: #e8eef4; font-size: 13px; font-weight: 600; text-align: right; padding: 6px 10px; width: 100%; outline: none; transition: border-color .15s; }
  .input-valor:focus { border-color: #ff8c00; background: rgba(255,140,0,0.04); }
  .input-valor:not(:placeholder-shown) { color: #ff8c00; }
  .linha-total { background: rgba(255,140,0,0.06); border: 1px solid rgba(255,165,0,0.15); border-radius: 10px; padding: 14px 16px; display: grid; grid-template-columns: 1fr 110px; align-items: center; margin-top: 10px; }
  .linha-total .lt-label { font-size: 13px; font-weight: 700; color: #e8eef4; }
  .linha-total .lt-value { text-align: right; font-size: 15px; font-weight: 800; color: #ff8c00; }
  .separator { border: none; border-top: 1px solid rgba(255,255,255,0.06); margin: 24px 0; }
  .bottom-section { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
  .campo-destaque { background: #0d1b2a; border: 1px solid rgba(255,165,0,0.15); border-radius: 10px; padding: 16px; }
  .campo-destaque label { display: block; font-size: 11px; font-weight: 700; letter-spacing: 0.8px; text-transform: uppercase; color: #455a6e; margin-bottom: 10px; }
  .campo-destaque input { width: 100%; background: transparent; border: none; border-bottom: 2px solid rgba(255,165,0,0.2); color: #e8eef4; font-size: 22px; font-weight: 800; padding: 6px 0; outline: none; text-align: right; }
  .campo-destaque input:focus { border-bottom-color: #ff8c00; }
  .totais-gerais { background: rgba(255,140,0,0.06); border: 1px solid rgba(255,165,0,0.2); border-radius: 12px; padding: 20px; margin-bottom: 24px; }
  .tg-title { font-size: 13px; font-weight: 700; color: #8fa3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 16px; }
  .tg-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; }
  .tg-label { font-size: 11px; font-weight: 600; color: #455a6e; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
  .tg-value { font-size: 20px; font-weight: 800; color: #e8eef4; }
  .tg-value.total { color: #ff8c00; font-size: 24px; }
  .tg-value.diferenca-zero { color: #4ade80; }
  .tg-value.diferenca-pos  { color: #4ade80; }
  .tg-value.diferenca-neg  { color: #ff6b7a; }
  .assinatura-box { border: 1px dashed rgba(255,255,255,0.1); border-radius: 10px; padding: 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 20px; }
  .assinatura-box .as-text { font-size: 12px; color: #455a6e; line-height: 1.6; flex: 1; }
  .as-campo { flex: 1; border-bottom: 1px solid rgba(255,255,255,0.15); padding-bottom: 8px; color: #8fa3b8; font-size: 12px; text-align: center; min-width: 160px; }
  .form-actions { display: flex; gap: 12px; justify-content: flex-end; }
  .btn { padding: 13px 28px; border-radius: 9px; font-size: 14px; font-weight: 700; cursor: pointer; border: none; transition: all .2s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
  .btn-primary { background: #ff8c00; color: #0f1923; }
  .btn-primary:hover { background: #ffa020; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(255,140,0,0.25); }
  .btn-secondary { background: rgba(255,255,255,0.05); color: #8fa3b8; border: 1px solid rgba(255,255,255,0.1); }
  .btn-secondary:hover { color: #e8eef4; }
  .total-live-badge { background: rgba(255,140,0,0.12); border: 1px solid rgba(255,165,0,0.25); color: #ff8c00; font-weight: 700; font-size: 13px; padding: 6px 14px; border-radius: 20px; }
</style>
</head>
<body>

<div class="topbar">
  <a href="dashboard.php" class="back-link">← Voltar</a>
  <div class="topbar-title">📋 Mapa de Caixa</div>
  <div class="topbar-right">
    <span class="user-badge">⛽ <?= htmlspecialchars($posto) ?></span>
    <span><?= htmlspecialchars($nome) ?></span>
  </div>
</div>

<div class="container">

<?php if ($sucesso && $dados): ?>
<div class="sucesso-banner">
  <div class="sucesso-icon">✅</div>
  <div>
    <div class="sucesso-title">Mapa de caixa salvo com sucesso!</div>
    <div class="sucesso-meta"><?= htmlspecialchars($nome) ?> · <?= htmlspecialchars($posto) ?> · <?= $hojeF ?></div>
    <div class="sucesso-totais">
      <div class="st-item"><div class="st-label">Dinheiro</div><div class="st-value">R$ <?= number_format($dados['dinheiro'], 2, ',', '.') ?></div></div>
      <div class="st-item"><div class="st-label">Total cartões</div><div class="st-value">R$ <?= number_format($dados['total_cartoes'], 2, ',', '.') ?></div></div>
      <div class="st-item"><div class="st-label">Total pedido no caixa</div><div class="st-value amarelo">R$ <?= number_format($dados['total_pedido'], 2, ',', '.') ?></div></div>
      <div class="st-item"><div class="st-label">Total depositado</div><div class="st-value">R$ <?= number_format($dados['total_depositado'], 2, ',', '.') ?></div></div>
      <div class="st-item">
        <div class="st-label">Diferença (+/-)</div>
        <div class="st-value <?= $dados['diferenca'] >= 0 ? 'verde' : 'vermelho' ?>">R$ <?= number_format($dados['diferenca'], 2, ',', '.') ?></div>
      </div>
    </div>
    <div style="margin-top:16px">
      <a href="dashboard.php" class="btn btn-secondary" style="font-size:12px;padding:8px 16px;">← Voltar ao início</a>
      &nbsp;
      <a href="mapa_caixa.php" class="btn btn-primary" style="font-size:12px;padding:8px 16px;">+ Novo mapa</a>
    </div>
  </div>
</div>
<?php else: ?>

<form method="POST" id="formCaixa">
<div class="form-card">
  <div class="form-header">
    <div class="form-title">⛽ Mapa de Caixa Diário</div>
    <div class="meta-fields">
      <div class="meta-field"><label>Posto</label><input type="text" value="<?= htmlspecialchars($posto) ?>" readonly></div>
      <div class="meta-field"><label>Funcionário</label><input type="text" value="<?= htmlspecialchars($nome) ?>" readonly></div>
      <div class="meta-field"><label>Data</label><input type="date" name="data" value="<?= $hoje ?>" required></div>
    </div>
    <div class="total-live-badge" id="totalBadge">Total: R$ 0,00</div>
  </div>

  <div class="form-body">
    <div class="colunas-grid">
      <div>
        <div class="coluna-header"><span>Cartões</span><span style="text-align:right">Total (R$)</span></div>
        <?php foreach ($cartoes_esquerda as $key => $label): ?>
        <div class="row-cartao">
          <div class="cartao-nome"><?= $label ?></div>
          <input type="text" class="input-valor" name="<?= $key ?>" placeholder="0,00" inputmode="decimal" oninput="recalcular()">
        </div>
        <?php endforeach; ?>
        <div class="linha-total"><span class="lt-label">Subtotal coluna</span><span class="lt-value" id="subtotal_esq">R$ 0,00</span></div>
      </div>
      <div>
        <div class="coluna-header"><span>Cartões</span><span style="text-align:right">Total (R$)</span></div>
        <?php foreach ($cartoes_direita as $key => $label): ?>
        <div class="row-cartao">
          <div class="cartao-nome"><?= $label ?></div>
          <input type="text" class="input-valor" name="<?= $key ?>" placeholder="0,00" inputmode="decimal" oninput="recalcular()">
        </div>
        <?php endforeach; ?>
        <div class="linha-total"><span class="lt-label">Subtotal coluna</span><span class="lt-value" id="subtotal_dir">R$ 0,00</span></div>
      </div>
    </div>

    <hr class="separator">

    <div class="bottom-section">
      <div class="campo-destaque"><label>💵 Dinheiro</label><input type="text" name="dinheiro" id="dinheiro" placeholder="0,00" inputmode="decimal" oninput="recalcular()"></div>
      <div class="campo-destaque"><label>📄 Nota a Prazo</label><input type="text" name="nota_a_prazo" id="nota_a_prazo" placeholder="0,00" inputmode="decimal" oninput="recalcular()"></div>
      <div class="campo-destaque"><label>🧾 Despesas</label><input type="text" name="despesas" id="despesas" placeholder="0,00" inputmode="decimal" oninput="recalcular()"></div>
      <div class="campo-destaque"><label>🏦 Total Depositado</label><input type="text" name="total_depositado" id="total_depositado" placeholder="0,00" inputmode="decimal" oninput="recalcular()"></div>
    </div>

    <div class="totais-gerais">
      <div class="tg-title">📊 Resumo do Caixa</div>
      <div class="tg-grid">
        <div><div class="tg-label">Total pedido no caixa</div><div class="tg-value total" id="res_total_pedido">R$ 0,00</div></div>
        <div><div class="tg-label">Total depositado</div><div class="tg-value" id="res_total_dep">R$ 0,00</div></div>
        <div><div class="tg-label">Diferença (+/-)</div><div class="tg-value diferenca-zero" id="res_diferenca">R$ 0,00</div></div>
      </div>
    </div>

    <div class="assinatura-box">
      <div class="as-text">Declaro que o meu caixa foi conferido e estou ciente que posso ser punido por qualquer irregularidade, conforme o Regulamento Interno de Trabalho.</div>
      <div class="as-campo">Assinatura digital confirmada pelo login do sistema</div>
    </div>

    <div class="form-actions">
      <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
      <button type="submit" class="btn btn-primary">✔ Confirmar fechamento</button>
    </div>
  </div>
</div>
</form>
<?php endif; ?>

</div>

<script>
function parseBR(v) {
    if (!v) return 0;
    v = v.trim().replace(/\./g, '').replace(',', '.');
    const n = parseFloat(v);
    return isNaN(n) ? 0 : n;
}
function fmt(v) {
    return 'R$ ' + v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}
function recalcular() {
    let esqKeys = <?= json_encode(array_keys($cartoes_esquerda)) ?>;
    let totalEsq = 0;
    esqKeys.forEach(k => { const el = document.querySelector(`input[name="${k}"]`); if (el) totalEsq += parseBR(el.value); });

    let dirKeys = <?= json_encode(array_keys($cartoes_direita)) ?>;
    let totalDir = 0;
    dirKeys.forEach(k => { const el = document.querySelector(`input[name="${k}"]`); if (el) totalDir += parseBR(el.value); });

    const dinheiro   = parseBR(document.getElementById('dinheiro')?.value);
    const notaPrazo  = parseBR(document.getElementById('nota_a_prazo')?.value);
    const despesas   = parseBR(document.getElementById('despesas')?.value);
    const totalDep   = parseBR(document.getElementById('total_depositado')?.value);
    const totalPedido = dinheiro + totalEsq + totalDir + notaPrazo + despesas;
    const diferenca  = totalPedido - totalDep;

    document.getElementById('subtotal_esq').textContent = fmt(totalEsq);
    document.getElementById('subtotal_dir').textContent = fmt(totalDir);
    document.getElementById('res_total_pedido').textContent = fmt(totalPedido);
    document.getElementById('res_total_dep').textContent    = fmt(totalDep);

    const elDif = document.getElementById('res_diferenca');
    elDif.textContent = fmt(diferenca);
    elDif.className = 'tg-value ' + (diferenca === 0 ? 'diferenca-zero' : diferenca > 0 ? 'diferenca-pos' : 'diferenca-neg');
    document.getElementById('totalBadge').textContent = 'Total: ' + fmt(totalPedido);
}
document.addEventListener('blur', function(e) {
    if (e.target.classList.contains('input-valor') || ['dinheiro','nota_a_prazo','despesas','total_depositado'].includes(e.target.id)) {
        const v = parseBR(e.target.value);
        if (v > 0) e.target.value = v.toFixed(2).replace('.', ',');
    }
}, true);
recalcular();
</script>
</body>
</html>
