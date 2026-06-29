<?php
$host    = 'localhost';
$banco   = 'ponteio_caixa';
$usuario = 'root';
$senha   = '';  // altere conforme sua configuração

$pdo = new PDO(
    "mysql:host=$host;dbname=$banco;charset=utf8mb4",
    $usuario,
    $senha,
    [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
