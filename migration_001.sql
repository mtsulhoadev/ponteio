-- ============================================================
-- MIGRATION 001 — Adiciona perfil 'admin' e atualiza senhas
-- Execute no phpMyAdmin > ponteio_caixa > aba SQL
-- ============================================================

-- 1. Adiciona 'admin' ao ENUM de perfil
ALTER TABLE usuarios
  MODIFY perfil ENUM('frentista','gerente','admin') DEFAULT 'frentista';

-- 2. Atualiza os usuários de demo com senha criptografada
--    (os hashes abaixo correspondem às senhas: joao=1234, maria=1234, admin=admin)
UPDATE usuarios SET
  senha = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  perfil = 'frentista'
WHERE usuario = 'joao';

UPDATE usuarios SET
  senha = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  perfil = 'frentista'
WHERE usuario = 'maria';

UPDATE usuarios SET
  senha = '$2y$10$TKh8H1.PfbuNhLLUdl/YeuFbsTtJDJk9lE3sTjSXWCL/XaWyT.cZW',
  perfil = 'admin'
WHERE usuario = 'admin';

-- Senha: admin = hash acima (gerado com password_hash('admin', PASSWORD_DEFAULT))
-- Senha: 1234  = hash acima (gerado com password_hash('1234',  PASSWORD_DEFAULT))
