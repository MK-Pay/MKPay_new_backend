# MKPay Backend - Plano de Implementação em Andamento

## 📋 Status Geral

Este arquivo contém o planejamento detalhado das próximas etapas de implementação do backend MKPay.

**Última atualização:** 2025-11-10

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

---

## 🚀 Em Andamento

### Fase 1: Autenticação e Middleware

#### 1.1 Middleware de Autenticação para Integration API
- [ ] **Criar middleware `AuthenticateIntegrationApi`**
  - Localização: `app/Http/Middleware/AuthenticateIntegrationApi.php`
  - Validar headers `X-App-Id` e `X-App-Secret`
  - Buscar App pelo `app_id` (UUID)
  - Validar `app_secret_token` usando Hash::check()
  - Verificar se token está ativo e não expirado
  - Verificar permissões do token
  - Anexar app e account ao request
  - Registrar uso do token (`last_used_at`)

- [ ] **Criar middleware `CheckTokenPermission`**
  - Localização: `app/Http/Middleware/CheckTokenPermission.php`
  - Verificar se o token tem a permissão necessária para a rota
  - Retornar 403 se não tiver permissão

- [ ] **Registrar middlewares**
  - Adicionar em `bootstrap/app.php` ou `app/Http/Kernel.php`
  - Criar aliases: `auth.integration`, `permission`

#### 1.2 Autenticação Admin (Laravel Sanctum)
- [ ] **Instalar Laravel Sanctum**
  - `composer require laravel/sanctum`
  - Publicar configuração
  - Rodar migrations

- [ ] **Criar modelo User para admins**
  - Localização: `app/Models/User.php`
  - Usar HasApiTokens trait
  - Configurar fillable e hidden

- [ ] **Criar migration para users**
  - Tabela central `users` (não tenant)
  - Campos: name, email, password, timestamps

- [ ] **Criar seeder AdminUserSeeder**
  - Criar usuário super admin padrão
  - Email: admin@mail.com
  - Senha: power@123

---

### Fase 2: Controllers da Admin API

Baseado nos arquivos .http em `dev-contents/demo-requests/admin-*-demo.http`

#### 2.1 Admin Auth Controller
- [ ] **Criar `AdminAuthController`**
  - Localização: `app/Http/Controllers/Admin/AuthController.php`
  - **POST /admin/login**
    - Validar email e password
    - Retornar token Sanctum
  - **POST /admin/logout**
    - Revogar token atual
  - **GET /admin/user**
    - Retornar dados do usuário autenticado

#### 2.2 Admin Account Controller
- [ ] **Criar `AdminAccountController`**
  - Localização: `app/Http/Controllers/Admin/AccountController.php`
  - **GET /admin/accounts** - Listar contas com filtros
    - Query params: type, status, category, search, per_page
    - Eager load relationships
    - Retornar paginado
  - **POST /admin/accounts** - Criar conta
    - Validar dados (account_type, name, email, cpf/cnpj, phone)
    - Gerar UUID automaticamente
    - Validar unicidade de email/cpf/cnpj
    - Retornar conta criada
  - **GET /admin/accounts/{uuid}** - Detalhes da conta
    - Buscar por UUID
    - Eager load: type, category, status, apps, wallets
  - **PUT /admin/accounts/{uuid}** - Atualizar conta
    - Validar dados permitidos
    - Não permitir mudar CPF/CNPJ
  - **DELETE /admin/accounts/{uuid}** - Deletar conta (soft delete)
  - **POST /admin/accounts/{uuid}/suspend** - Suspender conta
    - Mudar status para 'suspended'
  - **POST /admin/accounts/{uuid}/activate** - Ativar conta
    - Mudar status para 'active' ou 'verified'
  - **POST /admin/accounts/{uuid}/verify** - Verificar conta
    - Marcar `verified_at`
    - Mudar status para 'verified'
  - **GET /admin/accounts/{uuid}/activity** - Log de atividades
    - Usar spatie/laravel-activitylog

#### 2.3 Admin App Controller
- [ ] **Criar `AdminAppController`**
  - Localização: `app/Http/Controllers/Admin/AppController.php`
  - **GET /admin/accounts/{accountUuid}/apps** - Listar apps da conta
  - **POST /admin/accounts/{accountUuid}/apps** - Criar app
    - Gerar `app_id` (UUID) automaticamente
    - Validar nome e descrição
  - **GET /admin/apps/{appId}** - Detalhes do app
    - Buscar por app_id (UUID)
  - **PUT /admin/apps/{appId}** - Atualizar app
  - **DELETE /admin/apps/{appId}** - Deletar app
  - **GET /admin/apps/{appId}/tokens** - Listar tokens do app
  - **POST /admin/apps/{appId}/tokens** - Criar token
    - Gerar token aleatório (32 chars)
    - Hash com Hash::make()
    - Retornar token em plain text apenas na criação
    - Validar permissões (array)
  - **PUT /admin/apps/{appId}/tokens/{tokenId}** - Atualizar token
    - Permitir mudar: name, permissions, is_active, expires_at
  - **DELETE /admin/apps/{appId}/tokens/{tokenId}** - Revogar token

#### 2.4 Admin Wallet Controller
- [ ] **Criar `AdminWalletController`**
  - Localização: `app/Http/Controllers/Admin/WalletController.php`
  - **GET /admin/accounts/{accountUuid}/wallets** - Listar wallets
  - **POST /admin/accounts/{accountUuid}/wallets** - Criar wallet
    - Validar moeda
    - Iniciar balance em 0
  - **GET /admin/wallets/{walletUuid}** - Detalhes da wallet
  - **POST /admin/wallets/{walletUuid}/adjust** - Ajuste manual de saldo
    - Validar amount e reason
    - Criar transação de ajuste
    - Atualizar available_balance e held_balance
  - **GET /admin/wallets/{walletUuid}/balances** - Histórico de saldos
  - **GET /admin/wallets/{walletUuid}/transactions** - Transações da wallet

#### 2.5 Admin Transaction Controller
- [ ] **Criar `AdminTransactionController`**
  - Localização: `app/Http/Controllers/Admin/TransactionController.php`
  - **GET /admin/transactions** - Listar transações
    - Filtros: status, type, account, wallet, date_from, date_to
  - **GET /admin/transactions/{uuid}** - Detalhes da transação
  - **POST /admin/transactions/{uuid}/approve** - Aprovar transação
  - **POST /admin/transactions/{uuid}/reject** - Rejeitar transação
  - **POST /admin/transactions/{uuid}/refund** - Reembolsar transação

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

### Fase 3: Controllers da Integration API

Baseado em `dev-contents/demo-requests/integration-api-demo.http`

#### 3.1 Integration Payment Controller
- [ ] **Criar `PaymentController`**
  - Localização: `app/Http/Controllers/Api/V1/PaymentController.php`
  - **POST /api/v1/payments** - Criar pagamento
    - Validar dados do pagamento
    - Validar saldo disponível (para débito)
    - Criar transação
    - Retornar payment details
  - **GET /api/v1/payments/{id}** - Status do pagamento
  - **POST /api/v1/payments/{id}/cancel** - Cancelar pagamento

#### 3.2 Integration Transaction Controller
- [ ] **Criar `TransactionController`**
  - Localização: `app/Http/Controllers/Api/V1/TransactionController.php`
  - **GET /api/v1/transactions** - Listar transações
    - Filtros: status, type, date_from, date_to
    - Apenas transações da conta autenticada
  - **GET /api/v1/transactions/{uuid}** - Detalhes da transação

#### 3.3 Integration Wallet Controller
- [ ] **Criar `WalletController`**
  - Localização: `app/Http/Controllers/Api/V1/WalletController.php`
  - **GET /api/v1/wallets** - Listar wallets da conta
  - **GET /api/v1/wallets/{uuid}** - Detalhes da wallet
  - **GET /api/v1/wallets/{uuid}/balance** - Consultar saldo

#### 3.4 Integration Account Controller
- [ ] **Criar `AccountController`**
  - Localização: `app/Http/Controllers/Api/V1/AccountController.php`
  - **GET /api/v1/account** - Dados da conta autenticada
  - **PUT /api/v1/account** - Atualizar dados da conta

#### 3.5 Integration Webhook Controller
- [ ] **Criar `WebhookController`**
  - Localização: `app/Http/Controllers/Api/V1/WebhookController.php`
  - **GET /api/v1/webhooks** - Listar webhooks configurados
  - **POST /api/v1/webhooks** - Registrar webhook
  - **PUT /api/v1/webhooks/{id}** - Atualizar webhook
  - **DELETE /api/v1/webhooks/{id}** - Deletar webhook

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

1. **Iniciar Fase 1**: Criar middlewares de autenticação
2. **Iniciar Fase 2**: Criar controllers Admin API
3. **Iniciar Fase 3**: Criar controllers Integration API
4. **Iniciar Fase 8**: Escrever testes de integração

---

## 📊 Progresso

- **Fundação**: 100% ✅
- **Autenticação**: 0%
- **Admin API**: 0%
- **Integration API**: 0%
- **Services**: 0%
- **Testes**: 0%
- **Documentação**: 0%

---

**Última execução**: Aguardando início da Fase 1
