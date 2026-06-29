<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
require_auth('admin');

$msg   = '';
$tipo  = '';
$edit  = null;

// ── AÇÕES POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // CRIAR
    if ($acao === 'criar') {
        $nome     = trim($_POST['nome'] ?? '');
        $usuario  = trim($_POST['usuario'] ?? '');
        $senha    = trim($_POST['senha'] ?? '');
        $perfil   = $_POST['perfil'] ?? 'frentista';
        $posto_id = intval($_POST['posto_id'] ?? 0);

        if (!$nome || !$usuario || !$senha) {
            $msg = 'Preencha todos os campos obrigatórios.'; $tipo = 'error';
        } else {
            // Verifica usuário duplicado
            $chk = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
            $chk->execute([$usuario]);
            if ($chk->fetch()) {
                $msg = "O login \"$usuario\" já está em uso."; $tipo = 'error';
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO usuarios (posto_id, nome, usuario, senha, perfil) VALUES (?,?,?,?,?)")
                    ->execute([$posto_id ?: null, $nome, $usuario, $hash, $perfil]);
                $msg = "Usuário \"$nome\" criado com sucesso!"; $tipo = 'success';
            }
        }
    }

    // EDITAR
    if ($acao === 'editar') {
        $id       = intval($_POST['id']);
        $nome     = trim($_POST['nome'] ?? '');
        $usuario  = trim($_POST['usuario'] ?? '');
        $perfil   = $_POST['perfil'] ?? 'frentista';
        $posto_id = intval($_POST['posto_id'] ?? 0);
        $nova_senha = trim($_POST['senha'] ?? '');

        if ($nova_senha) {
            $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE usuarios SET nome=?, usuario=?, senha=?, perfil=?, posto_id=? WHERE id=?")
                ->execute([$nome, $usuario, $hash, $perfil, $posto_id ?: null, $id]);
        } else {
            $pdo->prepare("UPDATE usuarios SET nome=?, usuario=?, perfil=?, posto_id=? WHERE id=?")
                ->execute([$nome, $usuario, $perfil, $posto_id ?: null, $id]);
        }
        $msg = "Usuário atualizado com sucesso!"; $tipo = 'success';
    }

    // ATIVAR / DESATIVAR
    if ($acao === 'toggle') {
        $id = intval($_POST['id']);
        $pdo->prepare("UPDATE usuarios SET ativo = NOT ativo WHERE id = ?")->execute([$id]);
        $msg = 'Status do usuário atualizado.'; $tipo = 'success';
    }
}

// Carrega usuário para edição
if (isset($_GET['editar'])) {
    $edit = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $edit->execute([intval($_GET['editar'])]);
    $edit = $edit->fetch();
}

// Lista todos os usuários com nome do posto
$usuarios = $pdo->query("
    SELECT u.*, p.nome AS posto_nome
    FROM usuarios u
    LEFT JOIN postos p ON p.id = u.posto_id
    ORDER BY u.perfil, u.nome
")->fetchAll();

// Lista postos para o select
$postos = $pdo->query("SELECT id, nome FROM postos WHERE ativo = 1 ORDER BY nome")->fetchAll();

$hoje   = date('d/m/Y');
$diaSem = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'][date('w')];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Usuários — Ponteio</title>
<link rel="stylesheet" href="/ponteio/assets/css/base.css">
<style>
  .perfil-select { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; }
  .perfil-opt { position: relative; }
  .perfil-opt input[type=radio] { position: absolute; opacity: 0; width: 0; }
  .perfil-opt label {
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    padding: 14px 10px;
    background: #0d1b2a;
    border: 2px solid rgba(255,165,0,0.1);
    border-radius: 10px;
    cursor: pointer;
    font-size: 12px; font-weight: 600; color: #6b7f96;
    transition: all .2s;
    text-align: center;
  }
  .perfil-opt label .pi { font-size: 22px; }
  .perfil-opt input:checked + label {
    border-color: #ff8c00;
    background: rgba(255,140,0,0.08);
    color: #ff8c00;
  }
  .count-badge {
    background: rgba(255,140,0,0.12);
    border: 1px solid rgba(255,165,0,0.2);
    color: #ff8c00;
    font-size: 12px; font-weight: 700;
    padding: 3px 10px; border-radius: 20px;
  }
  .modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.6); z-index: 100;
    align-items: center; justify-content: center;
  }
  .modal-overlay.open { display: flex; }
  .modal {
    background: #111e2d;
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 14px;
    width: 100%; max-width: 560px;
    max-height: 90vh; overflow-y: auto;
    padding: 28px;
  }
  .modal-title { font-size: 16px; font-weight: 700; color: #e8eef4; margin-bottom: 24px; }
  .modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; }
  .senha-hint { font-size: 11px; color: #455a6e; margin-top: 5px; }
</style>
</head>
<body>

<?php require '../includes/sidebar.php'; ?>

<div class="main">
  <div class="topbar">
    <div class="topbar-title">👥 Gerenciar Usuários</div>
    <div class="topbar-date">📅 <?= $diaSem ?>, <?= $hoje ?></div>
  </div>

  <div class="content">

    <?php if ($msg): ?>
      <div class="alert alert-<?= $tipo ?>">
        <?= $tipo === 'success' ? '✅' : '⚠' ?> <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <!-- CABEÇALHO DA SEÇÃO -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <div>
        <div class="section-title">Usuários cadastrados</div>
        <span class="count-badge"><?= count($usuarios) ?> usuário(s)</span>
      </div>
      <button class="btn btn-primary" onclick="abrirModal()">+ Novo usuário</button>
    </div>

    <!-- TABELA DE USUÁRIOS -->
    <div class="table-card">
      <table>
        <thead>
          <tr>
            <th>Nome</th>
            <th>Login</th>
            <th>Perfil</th>
            <th>Posto</th>
            <th>Status</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($usuarios)): ?>
          <tr><td colspan="6" style="text-align:center;color:#455a6e;padding:32px">Nenhum usuário cadastrado.</td></tr>
          <?php endif; ?>
          <?php foreach ($usuarios as $u): ?>
          <tr>
            <td style="font-weight:600;color:#e8eef4">
              <div style="display:flex;align-items:center;gap:10px">
                <div style="width:32px;height:32px;border-radius:50%;background:#ff8c00;color:#0f1923;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0">
                  <?= strtoupper(substr($u['nome'],0,1)) ?>
                </div>
                <?= htmlspecialchars($u['nome']) ?>
              </div>
            </td>
            <td style="color:#6b7f96;font-family:monospace"><?= htmlspecialchars($u['usuario']) ?></td>
            <td>
              <span class="badge badge-<?= $u['perfil'] ?>">
                <?= $u['perfil'] === 'admin' ? '🔑 Admin' : ($u['perfil'] === 'gerente' ? '🏪 Gerente' : '⛽ Frentista') ?>
              </span>
            </td>
            <td><?= htmlspecialchars($u['posto_nome'] ?? '—') ?></td>
            <td>
              <span class="badge <?= $u['ativo'] ? 'badge-ok' : 'badge-inativo' ?>">
                <?= $u['ativo'] ? 'Ativo' : 'Inativo' ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:8px">
                <button class="btn btn-secondary btn-sm"
                  onclick="abrirEdicao(<?= htmlspecialchars(json_encode($u)) ?>)">
                  ✏ Editar
                </button>
                <form method="POST" style="display:inline" onsubmit="return confirm('Confirmar alteração de status?')">
                  <input type="hidden" name="acao" value="toggle">
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">
                    <?= $u['ativo'] ? '🚫 Desativar' : '✅ Ativar' ?>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- MODAL NOVO / EDITAR USUÁRIO -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal">
    <div class="modal-title" id="modalTitulo">➕ Novo Usuário</div>

    <form method="POST" id="formUsuario">
      <input type="hidden" name="acao" id="formAcao" value="criar">
      <input type="hidden" name="id"   id="formId"   value="">

      <!-- Perfil -->
      <div style="margin-bottom:20px">
        <div style="font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#455a6e;margin-bottom:12px">Perfil</div>
        <div class="perfil-select">
          <div class="perfil-opt">
            <input type="radio" name="perfil" id="p_frentista" value="frentista" checked>
            <label for="p_frentista"><span class="pi">⛽</span>Frentista</label>
          </div>
          <div class="perfil-opt">
            <input type="radio" name="perfil" id="p_gerente" value="gerente">
            <label for="p_gerente"><span class="pi">🏪</span>Gerente</label>
          </div>
          <div class="perfil-opt">
            <input type="radio" name="perfil" id="p_admin" value="admin">
            <label for="p_admin"><span class="pi">🔑</span>Admin</label>
          </div>
        </div>
      </div>

      <!-- Dados -->
      <div class="form-grid" style="margin-bottom:18px">
        <div class="campo">
          <label>Nome completo *</label>
          <input type="text" name="nome" id="formNome" placeholder="Ex: João Silva" required>
        </div>
        <div class="campo">
          <label>Login (usuário) *</label>
          <input type="text" name="usuario" id="formUsuario" placeholder="Ex: joao.silva" required autocomplete="off">
        </div>
        <div class="campo">
          <label>Posto</label>
          <select name="posto_id" id="formPosto">
            <option value="">— Sem posto (Admin/Rede) —</option>
            <?php foreach ($postos as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="campo">
          <label>Senha *</label>
          <input type="password" name="senha" id="formSenha" placeholder="••••••••" autocomplete="new-password">
          <div class="senha-hint" id="senhaHint">Mínimo 6 caracteres.</div>
        </div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="btnSalvar">Criar usuário</button>
      </div>
    </form>
  </div>
</div>

<script>
function abrirModal() {
    document.getElementById('modalTitulo').textContent = '➕ Novo Usuário';
    document.getElementById('formAcao').value  = 'criar';
    document.getElementById('formId').value    = '';
    document.getElementById('formNome').value  = '';
    document.getElementById('formUsuario').value = '';
    document.getElementById('formSenha').value = '';
    document.getElementById('formSenha').required = true;
    document.getElementById('senhaHint').textContent = 'Mínimo 6 caracteres.';
    document.getElementById('formPosto').value = '';
    document.getElementById('btnSalvar').textContent = 'Criar usuário';
    document.querySelector('input[name=perfil][value=frentista]').checked = true;
    document.getElementById('modalOverlay').classList.add('open');
}

function abrirEdicao(u) {
    document.getElementById('modalTitulo').textContent = '✏ Editar Usuário';
    document.getElementById('formAcao').value    = 'editar';
    document.getElementById('formId').value      = u.id;
    document.getElementById('formNome').value    = u.nome;
    document.getElementById('formUsuario').value = u.usuario;
    document.getElementById('formSenha').value   = '';
    document.getElementById('formSenha').required = false;
    document.getElementById('senhaHint').textContent = 'Deixe em branco para manter a senha atual.';
    document.getElementById('formPosto').value   = u.posto_id || '';
    document.getElementById('btnSalvar').textContent = 'Salvar alterações';

    const perfil = u.perfil || 'frentista';
    const radio = document.querySelector(`input[name=perfil][value=${perfil}]`);
    if (radio) radio.checked = true;

    document.getElementById('modalOverlay').classList.add('open');
}

function fecharModal() {
    document.getElementById('modalOverlay').classList.remove('open');
}

// Fecha ao clicar fora do modal
document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
});
</script>
</body>
</html>
