<?php
function require_auth($perfil_minimo = null) {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /ponteio/index.php');
        exit;
    }
    if ($perfil_minimo) {
        $hierarquia    = ['frentista' => 1, 'gerente' => 2, 'admin' => 3];
        $nivel_usuario = $hierarquia[$_SESSION['perfil']] ?? 0;
        $nivel_minimo  = $hierarquia[$perfil_minimo] ?? 99;
        if ($nivel_usuario < $nivel_minimo) {
            header('Location: /ponteio/dashboard.php');
            exit;
        }
    }
}

function is_admin()   { return ($_SESSION['perfil'] ?? '') === 'admin'; }
function is_gerente() { return in_array($_SESSION['perfil'] ?? '', ['admin', 'gerente']); }
