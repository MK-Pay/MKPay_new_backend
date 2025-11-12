# MKPay Backend - Plano de Implementação em Andamento

## 📋 Status Geral

Este arquivo contém o planejamento detalhado das próximas etapas de implementação do backend MKPay.

**Última atualização:** 2025-11-10 23:45

---

## ✅ Concluído (Fundação)

- [x] Instalação e configuração de pacotes fundamentais
- [x] Criação de migrações do banco de dados
- [x] Sistema de permissões e roles (Spatie)
- [x] Seeders de configuração de tenant
- [x] Modelos Eloquent com relacionamentos
- [x] Factories abrangentes para testes
- [x] Estrutura de testes unitários
- [x] TenantTestCase para testes com tenant
- [x] Arquivos de demonstração de API (.http)

## ✅ Concluído (Fase 1: Autenticação e Middleware)

- [x] **Middleware `AuthenticateIntegrationApi`** - Criado em `app/Http/Middleware/AuthenticateIntegrationApi.php`
  - ✅ Validação de headers `X-App-Id` e `X-App-Secret`
  - ✅ Busca de App pelo `app_id` (UUID)
  - ✅ Validação de `app_secret_token` usando Hash::check()
  - ✅ Verificação se token está ativo e não expirado
  - ✅ Anexação de app, token e account ao request
  - ✅ Registro de uso do token (`last_used_at`)

- [x] **Middleware `CheckTokenPermission`** - Criado em `app/Http/Middleware/CheckTokenPermission.php`
  - ✅ Verificação de permissão necessária para a rota
  - ✅ Retorno 403 se não tiver permissão

- [x] **Registrar middlewares**
  - ✅ Adicionado em `bootstrap/app.php`
  - ✅ Criados aliases: `auth.integration`, `permission`

- [x] **Laravel Sanctum**
  - ✅ Instalado via composer
  - ✅ Configuração publicada
  - ✅ Migrations rodadas

- [x] **Modelo User para admins**
  - ✅ HasApiTokens trait adicionado em `app/Models/User.php`
  - ✅ Configurado fillable e hidden

- [x] **Migration para users**
  - ✅ Tabela central `users` já existia
  - ✅ Campos: name, email, password, timestamps

- [x] **AdminUserSeeder**
  - ✅ Criado em `database/seeders/AdminUserSeeder.php`
  - ✅ Usuário super admin: admin@mail.com / power@123

- [x] **Admin AuthController**
  - ✅ Criado em `app/Http/Controllers/Admin/AuthController.php`
  - ✅ **POST /admin/login** - Autenticação com Sanctum token
  - ✅ **POST /admin/logout** - Revogação de token
  - ✅ **GET /admin/user** - Dados do usuário autenticado

- [x] **Rotas Admin**
  - ✅ Criado arquivo `routes/admin.php`
  - ✅ Registrado em `bootstrap/app.php` com prefixo `/admin`
  - ✅ Nomeação adequada: `admin.auth.login`, `admin.auth.logout`, `admin.auth.user`

---

## ✅ Concluído (Fase 2: Admin API Controllers)

### Fase 2: Controllers da Admin API (100% concluído)

Baseado nos arquivos .http em `dev-contents/demo-requests/admin-*-demo.http`

#### 2.2 Admin Account Controller ✅
- [x] **Criado `AdminAccountController`** - `app/Http/Controllers/Admin/AccountController.php`
  - ✅ GET /admin/accounts - Listar contas com filtros (type, status, category, search, pagination)
  - ✅ POST /admin/accounts - Criar conta (validação PF/CPF ou PJ/CNPJ)
  - ✅ GET /admin/accounts/{uuid} - Detalhes da conta com relationships
  - ✅ PUT /admin/accounts/{uuid} - Atualizar conta
  - ✅ DELETE /admin/accounts/{uuid} - Deletar conta (soft delete)
  - ✅ POST /admin/accounts/{uuid}/suspend - Suspender conta
  - ✅ POST /admin/accounts/{uuid}/activate - Ativar conta
  - ✅ POST /admin/accounts/{uuid}/verify - Verificar conta
  - ✅ GET /admin/accounts/{uuid}/activity - Log de atividades

#### 2.3 Admin App Controller ✅
- [x] **Criado `AdminAppController`** - `app/Http/Controllers/Admin/AppController.php`
  - ✅ GET /admin/apps - Listar apps com filtros
  - ✅ GET /admin/accounts/{accountUuid}/apps - Apps por conta
  - ✅ POST /admin/apps - Criar app (auto-gera app_id UUID)
  - ✅ GET /admin/apps/{appId} - Detalhes do app
  - ✅ PUT /admin/apps/{appId} - Atualizar app
  - ✅ DELETE /admin/apps/{appId} - Deletar app
  - ✅ POST /admin/apps/{appId}/activate - Ativar app
  - ✅ POST /admin/apps/{appId}/deactivate - Desativar app
  - ✅ GET /admin/apps/{appId}/tokens - Listar tokens
  - ✅ POST /admin/apps/{appId}/tokens - Criar token (gera token de 64 chars, hash seguro)
  - ✅ PUT /admin/apps/{appId}/tokens/{tokenId} - Atualizar token
  - ✅ DELETE /admin/apps/{appId}/tokens/{tokenId} - Revogar token

#### 2.4 Admin Wallet Controller ✅
- [x] **Criado `AdminWalletController`** - `app/Http/Controllers/Admin/WalletController.php`
  - ✅ GET /admin/wallets - Listar wallets com filtros
  - ✅ GET /admin/accounts/{accountUuid}/wallets - Listar wallets da conta
  - ✅ POST /admin/wallets - Criar wallet
  - ✅ GET /admin/wallets/{uuid} - Detalhes da wallet
  - ✅ DELETE /admin/wallets/{uuid} - Deletar wallet
  - ✅ GET /admin/wallets/{uuid}/balances - Histórico de saldos
  - ✅ GET /admin/wallets/{uuid}/transactions - Transações da wallet
  - ✅ POST /admin/wallets/{uuid}/adjust - Ajuste manual de saldo

#### 2.5 Admin Transaction Controller ✅
- [x] **Criado `AdminTransactionController`** - `app/Http/Controllers/Admin/TransactionController.php`
  - ✅ GET /admin/transactions - Listar transações com filtros
  - ✅ POST /admin/transactions - Criar transação
  - ✅ GET /admin/transactions/{uuid} - Detalhes da transação
  - ✅ PUT /admin/transactions/{uuid} - Atualizar status da transação
  - ✅ DELETE /admin/transactions/{uuid} - Deletar transação
  - ✅ POST /admin/transactions/{uuid}/approve - Aprovar transação
  - ✅ POST /admin/transactions/{uuid}/reject - Rejeitar transação
  - ✅ POST /admin/transactions/{uuid}/refund - Reembolsar transação

#### 2.6 Services Layer ✅
- [x] **Criado `WalletService`** - `app/Services/WalletService.php`
  - ✅ credit() - Creditar fundos na carteira
  - ✅ debit() - Debitar fundos da carteira
  - ✅ hold() - Reservar fundos (disponível → retido)
  - ✅ releaseHold() - Liberar fundos retidos
  - ✅ transfer() - Transferir entre carteiras
  - ✅ adjust() - Ajuste manual de saldo (admin)
  - ✅ getBalanceHistory() - Histórico de saldos

- [x] **Criado `TransactionService`** - `app/Services/TransactionService.php`
  - ✅ create() - Criar transação
  - ✅ updateStatus() - Atualizar status da transação
  - ✅ refund() - Processar reembolso
  - ✅ cancel() - Cancelar transação
  - ✅ Processamento automático por tipo (payment_in, payment_out, transfer, hold, release)

---

## ✅ Concluído (Fase 3: Integration API)

### Fase 3: Controllers da Integration API (100% concluído)

Baseado em `dev-contents/demo-requests/integration-api-demo.http`

#### 3.1 Integration Payment Controller ✅
- [x] **Criado `PaymentController`** - `app/Http/Controllers/Api/V1/PaymentController.php`
  - ✅ GET /api/v1/payments - Listar pagamentos com filtros (status, payment_method, dates)
  - ✅ POST /api/v1/payments - Criar pagamento (PIX, cartão de crédito/débito)
  - ✅ GET /api/v1/payments/{uuid} - Status do pagamento
  - ✅ POST /api/v1/payments/{uuid}/cancel - Cancelar pagamento
  - ✅ POST /api/v1/payments/{uuid}/refund - Reembolsar pagamento (total ou parcial)

#### 3.2 Integration Transaction Controller ✅
- [x] **Criado `TransactionController`** - `app/Http/Controllers/Api/V1/TransactionController.php`
  - ✅ GET /api/v1/transactions - Listar transações (filtros: type, status, dates)
  - ✅ GET /api/v1/transactions/{uuid} - Detalhes da transação

#### 3.3 Integration Wallet Controller ✅
- [x] **Criado `WalletController`** - `app/Http/Controllers/Api/V1/WalletController.php`
  - ✅ GET /api/v1/wallets - Listar wallets da conta
  - ✅ GET /api/v1/wallets/{uuid} - Detalhes da wallet
  - ✅ GET /api/v1/wallets/{uuid}/balance - Consultar saldo
  - ✅ GET /api/v1/wallets/{uuid}/statement - Extrato com histórico de movimentações

#### 3.4 Integration Account Controller ✅
- [x] **Criado `AccountController`** - `app/Http/Controllers/Api/V1/AccountController.php`
  - ✅ GET /api/v1/account - Dados da conta autenticada
  - ✅ PUT /api/v1/account - Atualizar dados da conta

#### 3.5 Integration Webhook Controller ✅
- [x] **Criado `WebhookController`** - `app/Http/Controllers/Api/V1/WebhookController.php`
  - ✅ GET /api/v1/webhooks - Listar webhooks configurados
  - ✅ POST /api/v1/webhooks - Registrar webhook
  - ✅ GET /api/v1/webhooks/{id} - Detalhes do webhook
  - ✅ PUT /api/v1/webhooks/{id} - Atualizar webhook
  - ✅ DELETE /api/v1/webhooks/{id} - Deletar webhook
  - ✅ POST /api/v1/webhooks/{id}/test - Testar webhook
  - ✅ GET /api/v1/webhooks/{id}/logs - Logs do webhook

#### 3.6 Rotas e Registro ✅
- [x] **Criado `routes/integration-api.php`**
  - ✅ Todas as rotas registradas com prefixo /api/v1
  - ✅ Middleware auth.integration aplicado
  - ✅ Nomeação consistente: api.v1.{resource}.{action}
  - ✅ Registrado em bootstrap/app.php

---

## 🚀 Em Andamento

### Controllers Opcionais da Admin API (não prioritários para MVP)

#### 2.6 Admin Document Controller
- [ ] **Criar `AdminDocumentController`**
  - Localização: `app/Http/Controllers/Admin/DocumentController.php`
  - **GET /admin/documents/validations** - Listar validações pendentes
  - **GET /admin/documents/{id}** - Detalhes da validação
  - **POST /admin/documents/{id}/approve** - Aprovar documento
  - **POST /admin/documents/{id}/reject** - Rejeitar documento

#### 2.7 Admin Webhook Controller
- [ ] **Criar `AdminWebhookController`**
  - Localização: `app/Http/Controllers/Admin/WebhookController.php`
  - **GET /admin/accounts/{accountUuid}/webhooks** - Listar webhooks
  - **POST /admin/accounts/{accountUuid}/webhooks** - Criar webhook
  - **PUT /admin/webhooks/{id}** - Atualizar webhook
  - **DELETE /admin/webhooks/{id}** - Deletar webhook
  - **POST /admin/webhooks/{id}/test** - Testar webhook

#### 2.8 Admin Currency Controller
- [ ] **Criar `AdminCurrencyController`**
  - Localização: `app/Http/Controllers/Admin/CurrencyController.php`
  - **GET /admin/currencies** - Listar moedas
  - **GET /admin/currencies/exchange-rates** - Taxas de câmbio
  - **POST /admin/currencies/exchange-rates** - Criar taxa
  - **PUT /admin/currencies/exchange-rates/{id}** - Atualizar taxa

---

### Fase 4: Form Requests (Validação)

#### 4.1 Admin Form Requests
- [ ] **Criar form requests para Admin API**
  - `Admin/Accounts/StoreAccountRequest.php`
  - `Admin/Accounts/UpdateAccountRequest.php`
  - `Admin/Apps/StoreAppRequest.php`
  - `Admin/Apps/StoreTokenRequest.php`
  - `Admin/Wallets/AdjustBalanceRequest.php`
  - `Admin/Transactions/RefundRequest.php`
  - `Admin/Webhooks/StoreWebhookRequest.php`

#### 4.2 Integration Form Requests
- [ ] **Criar form requests para Integration API**
  - `Api/V1/Payments/CreatePaymentRequest.php`
  - `Api/V1/Webhooks/StoreWebhookRequest.php`
  - `Api/V1/Account/UpdateAccountRequest.php`

---

### Fase 5: Rotas

#### 5.1 Rotas Admin
- [ ] **Criar arquivo `routes/admin.php`**
  - Prefixo: `/admin`
  - Middleware: `auth:sanctum`
  - Nomear todas as rotas: `admin.{resource}.{action}`
  - Grupos por recurso (accounts, apps, wallets, transactions)

- [ ] **Registrar rotas admin**
  - Adicionar em `bootstrap/app.php` ou `routes/web.php`

#### 5.2 Rotas Integration API
- [ ] **Criar arquivo `routes/integration-api.php`**
  - Prefixo: `/api/v1`
  - Middleware: `auth.integration`
  - Nomear rotas: `api.v1.{resource}.{action}`
  - Grupos por recurso

- [ ] **Registrar rotas integration**
  - Adicionar em `bootstrap/app.php`

---

### Fase 6: Services (Lógica de Negócio)

#### 6.1 Wallet Service
- [ ] **Criar `WalletService`**
  - Localização: `app/Services/WalletService.php`
  - Métodos:
    - `credit(Wallet $wallet, float $amount, string $description, ?Transaction $transaction)`
    - `debit(Wallet $wallet, float $amount, string $description, ?Transaction $transaction)`
    - `hold(Wallet $wallet, float $amount, string $reason)`
    - `releaseHold(Wallet $wallet, float $amount, string $reason)`
    - `transfer(Wallet $from, Wallet $to, float $amount, string $description)`
  - Usar transações DB (DB::transaction)
  - Registrar em wallet_balances

#### 6.2 Transaction Service
- [ ] **Criar `TransactionService`**
  - Localização: `app/Services/TransactionService.php`
  - Métodos:
    - `create(array $data): Transaction`
    - `updateStatus(Transaction $transaction, string $status)`
    - `refund(Transaction $transaction, float $amount, string $reason)`
    - `cancel(Transaction $transaction, string $reason)`
  - Validar estado (is_final)
  - Integrar com WalletService

#### 6.3 Payment Service
- [ ] **Criar `PaymentService`**
  - Localização: `app/Services/PaymentService.php`
  - Métodos:
    - `createPayment(array $data): Transaction`
    - `processPayment(Transaction $transaction)`
    - `handleCallback(array $callbackData)`
  - Integrar com adquirentes (futura implementação)

#### 6.4 Document Validation Service
- [ ] **Criar `DocumentValidationService`**
  - Localização: `app/Services/DocumentValidationService.php`
  - Métodos:
    - `validateCPF(string $cpf): bool`
    - `validateCNPJ(string $cnpj): bool`
    - `checkDocumentExists(string $document): bool`
  - Validação básica de formato
  - Futura integração com ReceitaWS/Serpro

---

### Fase 7: Resources (API Responses)

#### 7.1 Admin Resources
- [ ] **Criar API Resources para Admin**
  - `Admin/AccountResource.php`
  - `Admin/AppResource.php`
  - `Admin/WalletResource.php`
  - `Admin/TransactionResource.php`
  - `Admin/WebhookResource.php`
  - Incluir relacionamentos
  - Formatação de datas e valores

#### 7.2 Integration Resources
- [ ] **Criar API Resources para Integration**
  - `Api/V1/PaymentResource.php`
  - `Api/V1/TransactionResource.php`
  - `Api/V1/WalletResource.php`
  - `Api/V1/AccountResource.php`
  - Dados públicos apenas (sem campos sensíveis)

---

### Fase 8: Testes de Integração

Baseado nos arquivos .http criados anteriormente

#### 8.1 Testes Admin API
- [ ] **Criar `AdminAuthTest.php`**
  - Testar login com credenciais válidas
  - Testar login com credenciais inválidas
  - Testar logout
  - Testar acesso a rotas protegidas

- [ ] **Criar `AdminAccountTest.php`**
  - Testar listagem de contas
  - Testar criação de conta PF
  - Testar criação de conta PJ
  - Testar validações de CPF/CNPJ
  - Testar suspensão/ativação
  - Testar verificação de conta

- [ ] **Criar `AdminAppTest.php`**
  - Testar criação de app
  - Testar geração de token
  - Testar revogação de token
  - Testar permissões de token

- [ ] **Criar `AdminWalletTest.php`**
  - Testar criação de wallet
  - Testar ajuste de saldo
  - Testar consulta de saldos

- [ ] **Criar `AdminTransactionTest.php`**
  - Testar listagem com filtros
  - Testar aprovação de transação
  - Testar rejeição de transação
  - Testar reembolso

#### 8.2 Testes Integration API
- [ ] **Criar `IntegrationAuthTest.php`**
  - Testar autenticação com app_id e app_secret
  - Testar autenticação inválida
  - Testar token expirado
  - Testar permissões insuficientes

- [ ] **Criar `IntegrationPaymentTest.php`**
  - Testar criação de pagamento PIX
  - Testar criação de pagamento cartão
  - Testar cancelamento de pagamento
  - Testar validação de saldo

- [ ] **Criar `IntegrationTransactionTest.php`**
  - Testar listagem de transações
  - Testar consulta de transação específica
  - Testar filtros

- [ ] **Criar `IntegrationWalletTest.php`**
  - Testar consulta de saldo
  - Testar listagem de wallets

#### 8.3 Testes de Fluxo Completo
- [ ] **Criar `CompletePaymentFlowTest.php`**
  - Criar conta → criar app → gerar token → criar wallet → criar pagamento → verificar saldo

- [ ] **Criar `RefundFlowTest.php`**
  - Criar pagamento → aprovar → reembolsar → verificar saldo

---

### Fase 9: Documentação API

#### 9.1 Scramble (OpenAPI)
- [ ] **Configurar Scramble**
  - Adicionar docblocks nos controllers
  - Configurar responses
  - Testar geração automática

- [ ] **Criar documentação adicional**
  - Exemplos de uso
  - Guia de autenticação
  - Códigos de erro

---

### Fase 10: Segurança e Performance

#### 10.1 Rate Limiting
- [ ] **Configurar rate limiting**
  - Admin API: 120 requests/minuto
  - Integration API: 60 requests/minuto por token

#### 10.2 Logs e Auditoria
- [ ] **Criar middleware de log de API**
  - Registrar todas as chamadas
  - Armazenar: endpoint, método, IP, user/app, response time

#### 10.3 Validações de Segurança
- [ ] **Revisar validações**
  - SQL Injection (usar Eloquent/Query Builder)
  - XSS (usar Resource classes)
  - CSRF (configurado por padrão no Laravel)

---

## 📝 Notas de Implementação

### Padrões a Seguir

1. **PSR-12**: Código formatado com Laravel Pint
2. **Indentação**: 4 espaços
3. **Nomenclatura**: Inglês para classes, métodos e variáveis
4. **Type Hints**: Sempre usar tipos estritos
5. **Validação**: Sempre usar Form Requests
6. **Responses**: Sempre usar API Resources
7. **Erros**: Usar códigos HTTP apropriados
8. **Logging**: Usar canais apropriados (financial, integrations)

### Estrutura de Response Padrão

```json
{
    "success": true,
    "data": {},
    "message": "Operation successful",
    "errors": []
}
```

### Códigos HTTP

- 200: Success (GET, PUT)
- 201: Created (POST)
- 204: No Content (DELETE)
- 400: Bad Request (validação)
- 401: Unauthorized (sem auth)
- 403: Forbidden (sem permissão)
- 404: Not Found
- 422: Unprocessable Entity (validação de negócio)
- 500: Internal Server Error

---

## 🎯 Próximos Passos Imediatos

1. ~~**Fase 1: Autenticação e Middleware**~~ ✅ Concluído
2. **Fase 2: Controllers da Admin API** - Em andamento
   - Criar AdminAccountController
   - Criar AdminAppController
   - Criar AdminWalletController
   - Criar AdminTransactionController
3. **Fase 3: Controllers da Integration API**
   - Criar PaymentController
   - Criar TransactionController
   - Criar WalletController
4. **Fase 6: Services** - Lógica de negócio
   - WalletService
   - TransactionService
   - PaymentService
5. **Fase 8: Testes de Integração**

---

## 📊 Progresso

- **Fundação**: 100% ✅
- **Autenticação (Fase 1)**: 100% ✅
- **Admin API (Fase 2)**: 100% ✅
  - AuthController ✅
  - AccountController ✅ (9 endpoints)
  - AppController ✅ (12 endpoints + token management)
  - WalletController ✅ (9 endpoints)
  - TransactionController ✅ (10 endpoints)
  - WalletService ✅ (7 métodos)
  - TransactionService ✅ (5 métodos)
- **Integration API (Fase 3)**: 100% ✅
  - PaymentController ✅ (5 endpoints: list, create, show, cancel, refund)
  - TransactionController ✅ (2 endpoints: list, show)
  - WalletController ✅ (4 endpoints: list, show, balance, statement)
  - AccountController ✅ (2 endpoints: show, update)
  - WebhookController ✅ (7 endpoints: CRUD + test + logs)
  - Rotas registradas com auth.integration middleware
- **Services (Fase 6)**: 50% ✅ (WalletService e TransactionService completos)
- **Testes (Fase 8)**: 0% ⚠️ (Unit tests failing - tenant schema issue)
- **Documentação (Fase 9)**: 0%

---

**Última execução**: 2025-11-11 10:15
**Última ação**: Fase 3 - 100% concluída (Integration API com 5 controllers e 20 endpoints)
**Commits**:
- f56ee1f - Add authentication system for Admin and Integration APIs
- 07551ea - Update EM_ANDAMENTO.md with Phase 1 completion status
- 8f6cea8 - Add Admin API controllers for Account and App management
- 45b5577 - Implement Wallet and Transaction admin controllers with service layer
- d2b6f6d - Update EM_ANDAMENTO.md with Phase 2 completion status
- 8f57741 - Implement Integration API (Phase 3) with payment processing
