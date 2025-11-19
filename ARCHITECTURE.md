# Arquitetura do Sistema de Multiadquirência

## Visão Geral

Este documento detalha a arquitetura técnica do sistema de integração com múltiplas subadquirentes de pagamento.

## Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────────────┐
│                         API Layer                                │
│  ┌──────────────────┐           ┌──────────────────┐            │
│  │  PixController   │           │WithdrawalController│           │
│  └────────┬─────────┘           └────────┬───────────┘          │
└───────────┼──────────────────────────────┼──────────────────────┘
            │                              │
            ▼                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Service Layer                               │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │           SubacquirerFactory (Factory Pattern)           │   │
│  └────────────────────┬─────────────────────────────────────┘   │
│                       │                                          │
│      ┌────────────────┼────────────────┐                        │
│      │                │                │                        │
│      ▼                ▼                ▼                        │
│  ┌────────┐     ┌────────┐     ┌────────┐                      │
│  │SubadqA │     │SubadqB │     │Future  │                      │
│  │Service │     │Service │     │Services│                      │
│  └───┬────┘     └───┬────┘     └────────┘                      │
│      │              │                                            │
│      └──────┬───────┘                                           │
│             │                                                    │
│    ┌────────▼────────┐                                          │
│    │ BaseSubacquirer │  (Template Pattern)                      │
│    │    Service      │                                          │
│    └─────────────────┘                                          │
└─────────────────────────────────────────────────────────────────┘
            │
            ▼
┌─────────────────────────────────────────────────────────────────┐
│                    External APIs                                 │
│  ┌─────────────┐              ┌─────────────┐                   │
│  │  SubadqA    │              │  SubadqB    │                   │
│  │  Mock API   │              │  Mock API   │                   │
│  └─────────────┘              └─────────────┘                   │
└─────────────────────────────────────────────────────────────────┘
            │
            ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Queue System                                  │
│  ┌─────────────────┐         ┌─────────────────┐                │
│  │ProcessPixWebhook│         │ProcessWithdrawal│                │
│  │      Job        │         │   Webhook Job   │                │
│  └────────┬────────┘         └────────┬────────┘                │
│           │                           │                          │
└───────────┼───────────────────────────┼──────────────────────────┘
            │                           │
            ▼                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Data Layer                                   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │     User     │  │PixTransaction│  │  Withdrawal  │          │
│  │    Model     │  │    Model     │  │    Model     │          │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘          │
│         │                 │                  │                  │
│         └─────────┬───────┴──────────────────┘                  │
│                   │                                              │
│         ┌─────────▼──────────┐                                  │
│         │   Subacquirer      │                                  │
│         │      Model         │                                  │
│         └────────────────────┘                                  │
└─────────────────────────────────────────────────────────────────┘
            │
            ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Database                                    │
│         MySQL / PostgreSQL / SQLite                              │
└─────────────────────────────────────────────────────────────────┘
```

## Padrões de Projeto Utilizados

### 1. Strategy Pattern

**Onde:** `SubacquirerInterface` e suas implementações

**Por quê:** Permite que cada subadquirente tenha sua própria estratégia de integração (diferentes payloads, diferentes parsings de webhook, etc.)

**Exemplo:**
```php
interface SubacquirerInterface {
    public function createPix(array $data): array;
    public function parsePixWebhook(array $payload): array;
}

class SubadqAService implements SubacquirerInterface {
    // Implementação específica para SubadqA
}

class SubadqBService implements SubacquirerInterface {
    // Implementação específica para SubadqB
}
```

### 2. Factory Pattern

**Onde:** `SubacquirerFactory`

**Por quê:** Centraliza a criação de instâncias de serviços de subadquirentes, facilitando a manutenção e adição de novas subadquirentes.

**Exemplo:**
```php
class SubacquirerFactory {
    public static function make(Subacquirer $subacquirer): SubacquirerInterface {
        return match ($subacquirer->code) {
            'subadq_a' => new SubadqAService($subacquirer),
            'subadq_b' => new SubadqBService($subacquirer),
            default => throw new \Exception("Subacquirer not supported"),
        };
    }
}
```

### 3. Template Method Pattern

**Onde:** `BaseSubacquirerService`

**Por quê:** Define a estrutura básica de uma requisição HTTP para qualquer subadquirente, permitindo que as classes filhas implementem apenas as partes específicas.

**Exemplo:**
```php
abstract class BaseSubacquirerService {
    protected function makeRequest(string $method, string $endpoint, array $data = []): array {
        // Template method: estrutura comum para todas as requisições
        // - Log de request
        // - HTTP call
        // - Log de response
        // - Error handling
    }

    abstract protected function normalizeStatus(string $status): string;
}
```

### 4. Repository Pattern (Implicit)

**Onde:** Models do Eloquent

**Por quê:** O Eloquent já implementa o padrão Repository, abstraindo as operações de banco de dados.

### 5. Job Queue Pattern

**Onde:** `ProcessPixWebhook`, `ProcessWithdrawalWebhook`

**Por quê:** Permite processamento assíncrono de webhooks, melhorando performance e permitindo retry em caso de falhas.

## Fluxo de Dados

### Fluxo de Criação de PIX

```
[Client Request]
      │
      ▼
[PixController::create]
      │
      ├─► Validação de dados
      │
      ├─► Busca usuário e subadquirente
      │
      ├─► Cria registro no DB (status: PENDING)
      │
      ├─► SubacquirerFactory::make()
      │        │
      │        ├─► SubadqAService ou SubadqBService
      │        │
      │        └─► makeRequest() → API Externa
      │
      ├─► Atualiza registro com resposta
      │
      ├─► Despacha ProcessPixWebhook (delay: 2s)
      │
      └─► Retorna resposta ao cliente

[2 segundos depois...]

[ProcessPixWebhook::handle]
      │
      ├─► Parse webhook payload
      │
      ├─► Atualiza status da transação
      │
      └─► Log de sucesso
```

### Fluxo de Webhook

```
[Webhook Simulado via Job]
      │
      ▼
[ProcessPixWebhook/ProcessWithdrawalWebhook]
      │
      ├─► Load transaction/withdrawal
      │
      ├─► Get subacquirer service
      │
      ├─► Parse webhook payload
      │        │
      │        └─► Normaliza dados (diferentes formatos)
      │
      ├─► Update transaction status
      │
      └─► Log resultado
```

## Estrutura de Dados

### ER Diagram

```
┌─────────────────────┐
│   subacquirers      │
├─────────────────────┤
│ id (PK)            │
│ name               │
│ code (UNIQUE)      │◄────────────┐
│ base_url           │             │ N:1
│ config (JSON)      │             │
│ is_active          │             │
│ created_at         │             │
│ updated_at         │             │
└─────────────────────┘             │
                                    │
┌─────────────────────┐             │
│       users         │             │
├─────────────────────┤             │
│ id (PK)            │             │
│ name               │             │
│ email (UNIQUE)     │             │
│ password           │             │
│ subacquirer_id (FK)├─────────────┘
│ created_at         │◄────────────┐
│ updated_at         │             │ N:1
└─────────────────────┘             │
         │                          │
         │ 1:N                      │
         │                          │
         ├──────────────────────────┘
         │
         ├───────────────────────┐
         │                       │
         ▼                       ▼
┌─────────────────────┐  ┌─────────────────────┐
│  pix_transactions   │  │    withdrawals      │
├─────────────────────┤  ├─────────────────────┤
│ id (PK)            │  │ id (PK)            │
│ user_id (FK)       │  │ user_id (FK)       │
│ subacquirer_id (FK)│  │ subacquirer_id (FK)│
│ external_id        │  │ external_id        │
│ transaction_id (UQ)│  │ withdrawal_id (UQ) │
│ amount             │  │ amount             │
│ status             │  │ status             │
│ qr_code            │  │ bank_code          │
│ qr_code_url        │  │ agency             │
│ payer_name         │  │ account            │
│ payer_document     │  │ account_type       │
│ paid_at            │  │ document           │
│ raw_request (JSON) │  │ requested_at       │
│ raw_response (JSON)│  │ completed_at       │
│ webhook_payload    │  │ raw_request (JSON) │
│ created_at         │  │ raw_response (JSON)│
│ updated_at         │  │ webhook_payload    │
└─────────────────────┘  │ created_at         │
                         │ updated_at         │
                         └─────────────────────┘
```

## Escalabilidade

### Considerações para Alta Carga

#### 1. Queue System
- **Current:** Database queue
- **Recomendado para produção:** Redis ou AWS SQS
- **Benefícios:**
  - Menor latência
  - Maior throughput
  - Melhor monitoramento

#### 2. Database
- **Indexes:** Criados em campos frequentemente consultados
  - `pix_transactions.status`
  - `pix_transactions.external_id`
  - `withdrawals.status`
  - `withdrawals.external_id`

#### 3. Caching
- **Candidates for caching:**
  - Subacquirers (raramente mudam)
  - Users (para lookup rápido)

```php
Cache::remember("subacquirer_{$code}", 3600, function() use ($code) {
    return Subacquirer::where('code', $code)->first();
});
```

#### 4. Rate Limiting
```php
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/pix', [PixController::class, 'create']);
    Route::post('/withdraw', [WithdrawalController::class, 'create']);
});
```

#### 5. Load Balancing
Para múltiplas instâncias:
```
                    ┌─► Laravel Instance 1 ─┐
[Load Balancer] ────┼─► Laravel Instance 2 ─┼─► [Shared Queue]
                    └─► Laravel Instance 3 ─┘        │
                                                      ▼
                                              [Queue Workers]
```

## Segurança

### 1. Validação de Entrada
- Todos os requests são validados via Laravel Validator
- Tipos de dados são verificados
- Valores mínimos/máximos são aplicados

### 2. SQL Injection
- Proteção automática via Eloquent ORM
- Prepared statements em todas as queries

### 3. XSS Protection
- Headers de segurança configurados
- Sanitização automática de inputs

### 4. CSRF Protection
- Token CSRF em todas as requisições não-API
- API pode usar tokens bearer para autenticação

### 5. Webhook Signature (Produção)
```php
// Exemplo de validação de assinatura
public function validateWebhookSignature(Request $request): bool {
    $signature = $request->header('X-Signature');
    $payload = $request->getContent();
    $secret = config('subacquirers.webhook_secret');

    $expectedSignature = hash_hmac('sha256', $payload, $secret);

    return hash_equals($expectedSignature, $signature);
}
```

## Monitoramento e Observabilidade

### 1. Logs Estruturados
```php
Log::info("PIX transaction created", [
    'transaction_id' => $pixTransaction->transaction_id,
    'user_id' => $user->id,
    'subacquirer' => $user->subacquirer->code,
    'amount' => $pixTransaction->amount,
]);
```

### 2. Métricas Importantes
- Taxa de sucesso/falha por subadquirente
- Tempo médio de resposta das APIs
- Taxa de processamento de webhooks
- Volume de transações por hora

### 3. Alertas
- Falhas consecutivas na comunicação com subadquirente
- Queue com muitos jobs pendentes
- Taxa de erro acima do threshold
- Latência alta nas APIs

### 4. APM (Application Performance Monitoring)
Integração recomendada:
- New Relic
- Datadog
- Sentry (para erros)

## Disaster Recovery

### 1. Backup
- Backup diário do banco de dados
- Retenção de 30 dias
- Backup de logs (7 dias)

### 2. Retry Logic
Jobs têm retry automático:
```php
class ProcessPixWebhook implements ShouldQueue {
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min
}
```

### 3. Circuit Breaker
Para proteger contra falhas em subadquirentes:
```php
if ($subacquirer->consecutive_failures > 5) {
    // Pausar temporariamente as requisições
    // Alertar equipe de operações
    throw new SubacquirerUnavailableException();
}
```

## Testes

### 1. Unit Tests
- Services de subadquirentes
- Models
- Normalização de status

### 2. Integration Tests
- Controllers
- Jobs
- Fluxo completo de transações

### 3. Feature Tests
- API endpoints
- Validações
- Cenários de erro

```bash
php artisan test
```

## Extensibilidade

### Adicionando Nova Subadquirente

1. **Criar Service:**
```php
class SubadqCService extends BaseSubacquirerService implements SubacquirerInterface {
    // Implementar interface
}
```

2. **Atualizar Factory:**
```php
return match ($subacquirer->code) {
    'subadq_a' => new SubadqAService($subacquirer),
    'subadq_b' => new SubadqBService($subacquirer),
    'subadq_c' => new SubadqCService($subacquirer), // Novo
    default => throw new \Exception("Subacquirer not supported"),
};
```

3. **Adicionar no banco:**
```php
Subacquirer::create([
    'name' => 'SubadqC',
    'code' => 'subadq_c',
    'base_url' => 'https://api.subadqc.com',
    'is_active' => true,
]);
```

### Adicionando Novos Métodos de Pagamento

O sistema pode ser estendido para outros métodos:
- Boleto
- Cartão de crédito
- Transferência bancária

Basta criar novos controllers, models e implementar nos services.

## Conclusão

A arquitetura foi projetada para ser:
- **Extensível:** Fácil adição de novas subadquirentes
- **Escalável:** Suporta alta carga com queue e cache
- **Manutenível:** Código organizado com padrões bem definidos
- **Resiliente:** Retry logic, circuit breaker, logs estruturados
- **Testável:** Separação de concerns facilita testes
