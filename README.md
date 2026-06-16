# Sistema de Caixa — Rede Ponteio

Sistema web em PHP para fechamento de caixa diário dos postos de combustível da Rede Ponteio.

---

## 📁 Estrutura de arquivos

```
ponteio/
├── index.php         → Tela de login
├── dashboard.php     → Painel do funcionário
├── mapa_caixa.php    → Formulário do Mapa de Caixa
├── logout.php        → Encerra sessão
└── README.md
```

---

## 🚀 Instalação

### Requisitos
- PHP 7.4+ (ou 8.x)
- MySQL 5.7+ / MariaDB
- Apache ou Nginx com mod_rewrite

### Passo a passo

1. Copie os arquivos para a raiz do seu servidor (ex.: `/var/www/html/ponteio`)
2. Crie o banco de dados com o SQL abaixo
3. Configure a conexão em `includes/db.php` (criar após integração)
4. Acesse `http://seuservidor/ponteio/`

---

## 🗄️ Banco de Dados (SQL)

```sql
CREATE DATABASE ponteio_caixa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ponteio_caixa;

-- Postos
CREATE TABLE postos (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  nome        VARCHAR(100) NOT NULL,
  cidade      VARCHAR(80),
  ativo       TINYINT(1) DEFAULT 1,
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Usuários / Funcionários
CREATE TABLE usuarios (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  posto_id    INT,
  nome        VARCHAR(120) NOT NULL,
  usuario     VARCHAR(50) UNIQUE NOT NULL,
  senha       VARCHAR(255) NOT NULL,  -- usar password_hash()
  perfil      ENUM('frentista','gerente','admin') DEFAULT 'frentista',
  ativo       TINYINT(1) DEFAULT 1,
  criado_em   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (posto_id) REFERENCES postos(id)
);

-- Mapas de caixa
CREATE TABLE mapas_caixa (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id          INT NOT NULL,
  posto_id            INT NOT NULL,
  data_fechamento     DATE NOT NULL,

  -- Dinheiro
  dinheiro            DECIMAL(12,2) DEFAULT 0,

  -- Cartões coluna esquerda
  elo_credito         DECIMAL(12,2) DEFAULT 0,
  elo_credito_rede    DECIMAL(12,2) DEFAULT 0,
  elo_debito_rede     DECIMAL(12,2) DEFAULT 0,
  elo_parcelado       DECIMAL(12,2) DEFAULT 0,
  elo_parcelado_rede  DECIMAL(12,2) DEFAULT 0,
  master_cred_cielo   DECIMAL(12,2) DEFAULT 0,
  master_cred_rede    DECIMAL(12,2) DEFAULT 0,
  master_deb_cielo    DECIMAL(12,2) DEFAULT 0,
  master_deb_rede     DECIMAL(12,2) DEFAULT 0,
  master_parc_cielo   DECIMAL(12,2) DEFAULT 0,
  master_parc_rede    DECIMAL(12,2) DEFAULT 0,
  visa_cred_cielo     DECIMAL(12,2) DEFAULT 0,
  visa_cred_rede      DECIMAL(12,2) DEFAULT 0,
  visa_debito         DECIMAL(12,2) DEFAULT 0,
  visa_parcelado      DECIMAL(12,2) DEFAULT 0,
  visa_parc_rede      DECIMAL(12,2) DEFAULT 0,
  amex_parc           DECIMAL(12,2) DEFAULT 0,
  amex                DECIMAL(12,2) DEFAULT 0,
  cabal_cred_rede     DECIMAL(12,2) DEFAULT 0,
  cabal_deb_rede      DECIMAL(12,2) DEFAULT 0,
  cheque_a_vista      DECIMAL(12,2) DEFAULT 0,
  cheque_pre          DECIMAL(12,2) DEFAULT 0,
  credsystem_parc_rede DECIMAL(12,2) DEFAULT 0,
  credsystem_rede     DECIMAL(12,2) DEFAULT 0,
  diners_cred_cielo   DECIMAL(12,2) DEFAULT 0,
  diners_parc_cielo   DECIMAL(12,2) DEFAULT 0,
  diners_parc_rede    DECIMAL(12,2) DEFAULT 0,
  diners_rede         DECIMAL(12,2) DEFAULT 0,
  fitcard             DECIMAL(12,2) DEFAULT 0,
  goodcard            DECIMAL(12,2) DEFAULT 0,
  hipercard           DECIMAL(12,2) DEFAULT 0,
  hipercard_parc      DECIMAL(12,2) DEFAULT 0,

  -- Cartões coluna direita
  maxxcard            DECIMAL(12,2) DEFAULT 0,
  planvale            DECIMAL(12,2) DEFAULT 0,
  policard            DECIMAL(12,2) DEFAULT 0,
  vr_auto             DECIMAL(12,2) DEFAULT 0,
  shell_box_paypal    DECIMAL(12,2) DEFAULT 0,
  shell_box_1         DECIMAL(12,2) DEFAULT 0,
  shell_box_2         DECIMAL(12,2) DEFAULT 0,
  shell_box_3         DECIMAL(12,2) DEFAULT 0,
  sorocred            DECIMAL(12,2) DEFAULT 0,
  sorocred_parc       DECIMAL(12,2) DEFAULT 0,
  ticket_card         DECIMAL(12,2) DEFAULT 0,
  valecard            DECIMAL(12,2) DEFAULT 0,
  valeshop            DECIMAL(12,2) DEFAULT 0,
  ame                 DECIMAL(12,2) DEFAULT 0,

  -- Totalizadores
  nota_a_prazo        DECIMAL(12,2) DEFAULT 0,
  despesas            DECIMAL(12,2) DEFAULT 0,
  total_pedido_caixa  DECIMAL(12,2) DEFAULT 0,
  total_depositado    DECIMAL(12,2) DEFAULT 0,
  diferenca           DECIMAL(12,2) DEFAULT 0,

  enviado_em          DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  FOREIGN KEY (posto_id)   REFERENCES postos(id)
);

-- Dados de exemplo
INSERT INTO postos (nome, cidade) VALUES
  ('Posto Centro',      'São Paulo'),
  ('Posto Norte',       'São Paulo'),
  ('Posto Sul',         'Campinas'),
  ('Posto Leste',       'Ribeirão Preto');

INSERT INTO usuarios (posto_id, nome, usuario, senha, perfil) VALUES
  (1, 'João Silva',   'joao',  MD5('1234'), 'frentista'),
  (2, 'Maria Souza',  'maria', MD5('1234'), 'frentista'),
  (0, 'Carlos Admin', 'admin', MD5('admin'),'gerente');
```

---

## 🔐 Segurança (produção)

Substitua `MD5` por `password_hash()` e valide com `password_verify()`:

```php
// Criar senha
$hash = password_hash('1234', PASSWORD_DEFAULT);

// Verificar no login
if (password_verify($senha_digitada, $hash_do_banco)) { ... }
```

---

## 📌 Próximos passos sugeridos

- [ ] Conexão real com MySQL via PDO (`includes/db.php`)
- [ ] Tela de histórico de fechamentos por data
- [ ] Impressão/exportação do mapa em PDF
- [ ] Painel gerencial com todos os postos da rede
- [ ] Cadastro de funcionários e postos
- [ ] Notificações de diferença de caixa
- [ ] API REST para integração com sistemas de gestão

---

## 👤 Credenciais de demonstração

| Usuário | Senha | Perfil    |
|---------|-------|-----------|
| joao    | 1234  | Frentista |
| maria   | 1234  | Frentista |
| admin   | admin | Gerente   |
