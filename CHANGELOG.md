# Changelog — Rede Ponteio · Sistema de Caixa

## [0.2.0] — 2026-06-13
### Adicionado
- Conexão real com MySQL via PDO (`includes/db.php`)
- Login autenticado pelo banco de dados (tabela `usuarios`)
- Mapa de Caixa salva todos os valores na tabela `mapas_caixa`

### Corrigido
- Erro de acesso negado ao MySQL (configuração de senha do root no XAMPP)

---

## [0.1.0] — 2026-06-13
### Adicionado
- Tela de login com visual dark/laranja da Rede Ponteio
- Dashboard com cards de resumo e ações rápidas
- Sidebar com menu por perfil (frentista e gerente)
- Mapa de Caixa digital com todas as bandeiras e meios de pagamento
- Cálculo automático em tempo real (subtotais, total pedido, diferença)
- Tela de confirmação após envio do fechamento
- Logout
- Estrutura de banco de dados (SQL) com tabelas: `postos`, `usuarios`, `mapas_caixa`
- Dados de demonstração inseridos no banco

---

## [Próximas versões planejadas]

### [0.3.0] — Histórico de fechamentos
- [ ] Listar mapas enviados por data no dashboard
- [ ] Filtro por período e funcionário

### [0.4.0] — Impressão e PDF
- [ ] Gerar comprovante do fechamento em PDF
- [ ] Layout fiel ao formulário físico para impressão

### [0.5.0] — Painel gerencial
- [ ] Visão consolidada de todos os postos da rede
- [ ] Comparativo entre postos por período

### [0.6.0] — Cadastros
- [ ] Tela de cadastro de funcionários
- [ ] Tela de cadastro e gestão dos 30 postos

### [0.7.0] — Alertas e melhorias
- [ ] Destaque visual para fechamentos com diferença negativa
- [ ] Notificação para o gerente quando houver diferença
- [ ] Senha criptografada com `password_hash()`

---

## [0.3.0] — 2026-06-29
### Adicionado
- Perfil `admin` criado (escritório central)
- Tela de gerenciamento de usuários (`admin/usuarios.php`)
  - Listagem com perfil, posto e status
  - Modal para criar novo usuário com seleção visual de perfil
  - Edição inline com opção de trocar senha
  - Ativar / desativar usuário
- CSS compartilhado (`assets/css/base.css`) — elimina repetição de estilos
- Sidebar reutilizável (`includes/sidebar.php`) — menu dinâmico por perfil
- Helper de autenticação (`includes/auth.php`) com hierarquia de perfis
- Migration SQL (`migration_001.sql`) — adiciona perfil admin e atualiza senhas

### Melhorado
- Senhas agora criptografadas com `password_hash()` / `password_verify()`
- Login atualizado para validar com hash seguro
