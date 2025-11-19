# Sistema de Multiadquirência - Laravel

Sistema de integração com múltiplas subadquirentes de pagamento (PIX e Saques) desenvolvido em Laravel.

## 📚 Documentação Completa

| Documento | Descrição |
|-----------|-----------|
| **[QUICK_START.md](QUICK_START.md)** | ⚡ Guia de início rápido (5 minutos) |
| **[EXAMPLES.md](EXAMPLES.md)** | 📝 Exemplos práticos de uso da API |
| **[ARCHITECTURE.md](ARCHITECTURE.md)** | 🏗️ Arquitetura detalhada do sistema |
| **[POSTMAN_GUIDE.md](POSTMAN_GUIDE.md)** | 📮 Guia completo do Postman |

## 🚀 Início Rápido

```bash
# 1. Instalar dependências (se necessário)
composer install

# 2. Rodar migrations e seeders
php artisan migrate
php artisan db:seed

# 3. Iniciar servidor e fila (2 terminais)
php artisan serve
php artisan queue:work

# 4. Testar! (via Postman ou cURL)
```

**👉 Veja o guia completo:** [QUICK_START.md](QUICK_START.md)

## Sobre o Projeto

Este projeto implementa um sistema escalável e extensível de integração com subadquirentes de pagamento, permitindo que cada usuário utilize uma subadquirente diferente para processar transações PIX e saques.

### Principais Características

- **Arquitetura Extensível**: Utiliza Strategy Pattern para facilitar a adição de novas subadquirentes
- **Processamento Assíncrono**: Jobs e filas para processar webhooks de forma eficiente
- **Multiadquirência**: Cada usuário pode estar vinculado a uma subadquirente diferente
- **Webhooks Simulados**: Sistema de simulação de webhooks para ambiente de desenvolvimento
- **Logs Completos**: Rastreamento detalhado de todas as operações

## Arquitetura

### Estrutura de Diretórios

```
app/
├── Http/Controllers/Api/
│   ├── PixController.php
│   └── WithdrawalController.php
├── Jobs/
│   ├── ProcessPixWebhook.php
│   └── ProcessWithdrawalWebhook.php
├── Models/
│   ├── Subacquirer.php
│   ├── PixTransaction.php
│   ├── Withdrawal.php
│   └── User.php
└── Services/Subacquirers/
    ├── Contracts/
    │   └── SubacquirerInterface.php
    ├── BaseSubacquirerService.php
    ├── SubadqAService.php
    ├── SubadqBService.php
    └── SubacquirerFactory.php
```

### Padrões de Projeto Utilizados

- **Strategy Pattern**: Para gerenciar diferentes subadquirentes
- **Factory Pattern**: Para instanciar serviços de subadquirentes
- **Repository Pattern**: Implícito através dos Models do Eloquent
- **Job Queue Pattern**: Para processamento assíncrono de webhooks

### Banco de Dados

```
┌─────────────────┐
│   subacquirers  │
├─────────────────┤
│ id              │
│ name            │
│ code            │
│ base_url        │
│ config          │
│ is_active       │
│ timestamps      │
└─────────────────┘
         │
         │ 1:N
         ▼
┌─────────────────┐       ┌───────────────────┐
│      users      │◄─────►│ pix_transactions  │
├─────────────────┤  1:N  ├───────────────────┤
│ id              │       │ id                │
│ name            │       │ user_id           │
│ email           │       │ subacquirer_id    │
│ password        │       │ external_id       │
│ subacquirer_id  │       │ transaction_id    │
│ timestamps      │       │ amount            │
└─────────────────┘       │ status            │
         │                │ qr_code           │
         │ 1:N            │ paid_at           │
         ▼                │ timestamps        │
┌─────────────────┐       └───────────────────┘
│   withdrawals   │
├─────────────────┤
│ id              │
│ user_id         │
│ subacquirer_id  │
│ external_id     │
│ withdrawal_id   │
│ amount          │
│ status          │
│ bank_code       │
│ completed_at    │
│ timestamps      │
└─────────────────┘
```

## Instalação e Configuração

### Requisitos

- PHP 8.2+
- Composer
- MySQL/PostgreSQL/SQLite
- Laravel 12

### Passos para Instalação

1. Clone o repositório:
```bash
git clone <repository-url>
cd multiadquirencia
```

2. Instale as dependências:
```bash
composer install
```

3. Configure o arquivo `.env`:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure o banco de dados no `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=multiadquirencia
DB_USERNAME=root
DB_PASSWORD=
```

5. Configure a fila no `.env`:
```env
QUEUE_CONNECTION=database
```

6. Execute as migrations:
```bash
php artisan migrate
```

7. Execute os seeders:
```bash
php artisan db:seed
```

Isso criará:
- 2 subadquirentes (SubadqA e SubadqB)
- 3 usuários de teste:
  - Usuário A (usuario_a@example.com) - SubadqA
  - Usuário B (usuario_b@example.com) - SubadqA
  - Usuário C (usuario_c@example.com) - SubadqB

8. Inicie o servidor e o worker de filas:
```bash
# Terminal 1 - Servidor
php artisan serve

# Terminal 2 - Queue Worker
php artisan queue:work
```

## Uso da API

### Endpoints Disponíveis

#### 1. Criar Transação PIX

**Endpoint:** `POST /api/pix`

**Request:**
```json
{
  "user_id": 1,
  "amount": 125.50,
  "description": "Pagamento via PIX",
  "customer_name": "João da Silva",
  "customer_document": "12345678900"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "transaction_id": "PIX-9c5e1234-5678-90ab-cdef-1234567890ab",
    "external_id": "PIX123456789",
    "amount": "125.50",
    "status": "PENDING",
    "qr_code": "00020126580014br.gov.bcb.pix...",
    "qr_code_url": "https://exemplo.com/qrcode/123",
    "created_at": "2025-11-19T13:30:00.000000Z"
  }
}
```

**Exemplo cURL:**
```bash
curl -X POST http://localhost:8000/api/pix \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "amount": 125.50,
    "description": "Pagamento via PIX",
    "customer_name": "João da Silva",
    "customer_document": "12345678900"
  }'
```

#### 2. Criar Saque

**Endpoint:** `POST /api/withdraw`

**Request:**
```json
{
  "user_id": 1,
  "amount": 500.00,
  "bank_code": "341",
  "agency": "0001",
  "account": "12345678",
  "account_type": "checking",
  "holder_name": "João da Silva",
  "holder_document": "12345678900"
}
```

**Response (201):**
```json
{
  "success": true,
  "data": {
    "withdrawal_id": "WD-9c5e1234-5678-90ab-cdef-1234567890ab",
    "external_id": "WD123456789",
    "amount": "500.00",
    "status": "PENDING",
    "requested_at": "2025-11-19T13:30:00.000000Z",
    "created_at": "2025-11-19T13:30:00.000000Z"
  }
}
```

**Exemplo cURL:**
```bash
curl -X POST http://localhost:8000/api/withdraw \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "amount": 500.00,
    "bank_code": "341",
    "agency": "0001",
    "account": "12345678",
    "account_type": "checking",
    "holder_name": "João da Silva",
    "holder_document": "12345678900"
  }'
```

### Status das Transações

#### PIX
- `PENDING`: PIX criado, aguardando pagamento
- `PROCESSING`: Em processamento
- `CONFIRMED`: Pagamento confirmado (SubadqA)
- `PAID`: Pagamento concluído (SubadqB)
- `CANCELLED`: Cancelado
- `FAILED`: Falhou

#### Saques
- `PENDING`: Saque criado, aguardando processamento
- `PROCESSING`: Em processamento
- `SUCCESS`: Saque realizado com sucesso (SubadqA)
- `DONE`: Saque concluído (SubadqB)
- `FAILED`: Falhou
- `CANCELLED`: Cancelado

## Fluxo de Processamento

### Fluxo PIX

1. Cliente envia requisição para `/api/pix`
2. Sistema valida dados e cria registro no banco
3. Sistema chama API da subadquirente configurada para o usuário
4. Sistema atualiza registro com resposta da subadquirente
5. Sistema dispara job para simular webhook (após 2 segundos)
6. Job processa webhook e atualiza status da transação

### Fluxo Saque

1. Cliente envia requisição para `/api/withdraw`
2. Sistema valida dados e cria registro no banco
3. Sistema chama API da subadquirente configurada para o usuário
4. Sistema atualiza registro com resposta da subadquirente
5. Sistema dispara job para simular webhook (após 3 segundos)
6. Job processa webhook e atualiza status do saque

## Simulação de Webhooks

O sistema utiliza Jobs com delay para simular o recebimento de webhooks das subadquirentes:

- **PIX**: Webhook simulado após 2 segundos
- **Saque**: Webhook simulado após 3 segundos

Para ambientes de alta carga (3+ requisições por segundo), o sistema utiliza:
- **Queue Driver**: Database (pode ser alterado para Redis/SQS para maior performance)
- **Jobs Assíncronos**: Processamento em background
- **Logs Estruturados**: Para debugging e monitoramento

## Adicionando Novas Subadquirentes

Para adicionar uma nova subadquirente, siga estes passos:

1. Crie uma nova classe de serviço em `app/Services/Subacquirers/`:

```php
<?php

namespace App\Services\Subacquirers;

use App\Services\Subacquirers\Contracts\SubacquirerInterface;

class NovaSubadqService extends BaseSubacquirerService implements SubacquirerInterface
{
    public function createPix(array $data): array
    {
        // Implementar lógica específica
    }

    public function createWithdrawal(array $data): array
    {
        // Implementar lógica específica
    }

    public function parsePixWebhook(array $payload): array
    {
        // Implementar parsing do webhook
    }

    public function parseWithdrawalWebhook(array $payload): array
    {
        // Implementar parsing do webhook
    }

    protected function normalizeStatus(string $status): string
    {
        // Normalizar status para o padrão interno
    }
}
```

2. Atualize o `SubacquirerFactory`:

```php
public static function make(Subacquirer $subacquirer): SubacquirerInterface
{
    return match ($subacquirer->code) {
        'subadq_a' => new SubadqAService($subacquirer),
        'subadq_b' => new SubadqBService($subacquirer),
        'nova_subadq' => new NovaSubadqService($subacquirer), // Nova subadquirente
        default => throw new \Exception("Subacquirer {$subacquirer->code} not supported"),
    };
}
```

3. Adicione o registro no banco via seeder ou manualmente.

## Logs

Os logs são armazenados em `storage/logs/laravel.log` e incluem:

- Requisições para subadquirentes
- Respostas das subadquirentes
- Processamento de webhooks
- Erros e exceções

Exemplo de log:
```
[2025-11-19 13:30:00] local.INFO: PIX transaction created {"transaction_id":"PIX-123","user_id":1,"subacquirer":"subadq_a"}
[2025-11-19 13:30:02] local.INFO: Processing PIX webhook {"pix_id":1,"payload":{...}}
[2025-11-19 13:30:02] local.INFO: PIX webhook processed successfully {"pix_id":1,"status":"CONFIRMED"}
```

## Testes

Para executar os testes:

```bash
php artisan test
```

## Decisões Técnicas

### Por que Strategy Pattern?
O Strategy Pattern foi escolhido para permitir que cada subadquirente tenha sua própria implementação de criação de PIX, saque e parsing de webhooks, mantendo o código desacoplado e facilitando a adição de novas subadquirentes.

### Por que Jobs e Filas?
Jobs e filas foram implementados para:
- Processar webhooks de forma assíncrona
- Lidar com alta carga (3+ req/s)
- Permitir retry em caso de falhas
- Melhorar performance da API

### Por que SQLite/Database Queue?
Para facilitar o desenvolvimento e teste, mas pode ser facilmente substituído por Redis ou SQS em produção.

### Armazenamento de Payloads
Todos os payloads de request/response são armazenados nos campos `raw_request`, `raw_response` e `webhook_payload` para:
- Debugging
- Auditoria
- Reconciliação financeira
- Análise de problemas

## Considerações de Produção

Para ambiente de produção, considere:

1. **Fila**: Migrar para Redis ou SQS
```env
QUEUE_CONNECTION=redis
```

2. **Cache**: Implementar cache para reduzir consultas ao banco
```php
Cache::remember("subacquirer_{$code}", 3600, fn() => Subacquirer::where('code', $code)->first());
```

3. **Rate Limiting**: Adicionar rate limiting nas rotas
```php
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/pix', [PixController::class, 'create']);
});
```

4. **Monitoramento**: Integrar com ferramentas como:
   - Sentry (erros)
   - New Relic (performance)
   - Datadog (métricas)

5. **Segurança**:
   - Validar assinaturas de webhooks reais
   - Implementar autenticação nas APIs
   - Adicionar CORS configurado

6. **Webhook Real**: Implementar endpoint para receber webhooks reais:
```php
Route::post('/webhooks/subacquirers/{code}/pix', [WebhookController::class, 'pix']);
Route::post('/webhooks/subacquirers/{code}/withdrawal', [WebhookController::class, 'withdrawal']);
```

## Suporte e Contato

Para dúvidas ou sugestões, entre em contato com a equipe de desenvolvimento.

## Licença

Este projeto é proprietário e confidencial.
