<?php
session_start();

// Redireciona se já estiver logado
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';

// Simulação de login (em produção: consulta ao banco)
$usuarios = [
    ['id' => 1, 'nome' => 'João Silva',    'usuario' => 'joao',   'senha' => '1234', 'posto' => 'Posto Centro',    'posto_id' => 1, 'perfil' => 'frentista'],
    ['id' => 2, 'nome' => 'Maria Souza',   'usuario' => 'maria',  'senha' => '1234', 'posto' => 'Posto Norte',     'posto_id' => 2, 'perfil' => 'frentista'],
    ['id' => 3, 'nome' => 'Carlos Admin',  'usuario' => 'admin',  'senha' => 'admin','posto' => 'Rede Ponteio',    'posto_id' => 0, 'perfil' => 'gerente'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['usuario'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    $encontrado = null;
    foreach ($usuarios as $u) {
        if ($u['usuario'] === $login && $u['senha'] === $senha) {
            $encontrado = $u;
            break;
        }
    }

    if ($encontrado) {
        $_SESSION['usuario_id']   = $encontrado['id'];
        $_SESSION['usuario_nome'] = $encontrado['nome'];
        $_SESSION['usuario_login']= $encontrado['usuario'];
        $_SESSION['posto']        = $encontrado['posto'];
        $_SESSION['posto_id']     = $encontrado['posto_id'];
        $_SESSION['perfil']       = $encontrado['perfil'];
        header('Location: dashboard.php');
        exit;
    } else {
        $erro = 'Usuário ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rede Ponteio — Acesso</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'Segoe UI', system-ui, sans-serif;
    background: #0f1923;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
  }

  /* Grade de linhas de fundo */
  body::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
      linear-gradient(rgba(255,165,0,0.04) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255,165,0,0.04) 1px, transparent 1px);
    background-size: 40px 40px;
  }

  .login-card {
    background: #1a2533;
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 16px;
    padding: 48px 40px;
    width: 100%;
    max-width: 400px;
    position: relative;
    box-shadow: 0 0 60px rgba(255,140,0,0.08);
  }

  .logo-area {
    text-align: center;
    margin-bottom: 36px;
  }

  .logo-badge {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: #ff8c00;
    color: #0f1923;
    font-weight: 800;
    font-size: 22px;
    letter-spacing: 2px;
    padding: 10px 24px;
    border-radius: 8px;
    margin-bottom: 12px;
  }

  .logo-badge span { font-size: 26px; }

  .logo-sub {
    color: #6b7f96;
    font-size: 13px;
    letter-spacing: 1px;
    text-transform: uppercase;
  }

  h2 {
    color: #e8eef4;
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 24px;
    text-align: center;
  }

  .campo {
    margin-bottom: 18px;
  }

  label {
    display: block;
    color: #8fa3b8;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    margin-bottom: 8px;
  }

  input[type="text"],
  input[type="password"] {
    width: 100%;
    background: #0f1923;
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 8px;
    color: #e8eef4;
    font-size: 15px;
    padding: 12px 16px;
    outline: none;
    transition: border-color .2s;
  }

  input:focus {
    border-color: #ff8c00;
    box-shadow: 0 0 0 3px rgba(255,140,0,0.1);
  }

  .erro {
    background: rgba(220,53,69,0.15);
    border: 1px solid rgba(220,53,69,0.4);
    color: #ff6b7a;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 13px;
    margin-bottom: 18px;
    text-align: center;
  }

  .btn-entrar {
    width: 100%;
    background: #ff8c00;
    color: #0f1923;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 700;
    letter-spacing: 0.5px;
    padding: 14px;
    cursor: pointer;
    transition: background .2s, transform .1s;
    margin-top: 8px;
  }

  .btn-entrar:hover { background: #ffa020; }
  .btn-entrar:active { transform: scale(0.99); }

  .hint {
    text-align: center;
    color: #455a6e;
    font-size: 12px;
    margin-top: 24px;
  }

  .hint strong { color: #6b7f96; }
</style>
</head>
<body>
<div class="login-card">
  <div class="logo-area">
    <div class="logo-badge"><span>⛽</span> PONTEIO</div>
    <div class="logo-sub">Rede de Postos — Sistema de Caixa</div>
  </div>

  <h2>Acesse sua conta</h2>

  <?php if ($erro): ?>
    <div class="erro">⚠ <?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="campo">
      <label>Usuário</label>
      <input type="text" name="usuario" placeholder="Seu login" autocomplete="username" required>
    </div>
    <div class="campo">
      <label>Senha</label>
      <input type="password" name="senha" placeholder="••••••••" autocomplete="current-password" required>
    </div>
    <button class="btn-entrar" type="submit">Entrar</button>
  </form>

  <div class="hint">
    Demo: <strong>joao / 1234</strong> &nbsp;|&nbsp; <strong>admin / admin</strong>
  </div>
</div>
</body>
</html>
