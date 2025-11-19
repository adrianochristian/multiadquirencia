# Exemplos de Requisições - Sistema de Multiadquirência

Este documento contém exemplos práticos de como usar a API do sistema.

## Pré-requisitos

Certifique-se de que:
1. O servidor está rodando: `php artisan serve`
2. O queue worker está rodando: `php artisan queue:work`
3. Os seeders foram executados: `php artisan db:seed`

## Usuários Disponíveis

Após executar os seeders, você terá 3 usuários:

| ID | Nome | Email | Subadquirente |
|----|------|-------|---------------|
| 1 | Usuário A | usuario_a@example.com | SubadqA |
| 2 | Usuário B | usuario_b@example.com | SubadqA |
| 3 | Usuário C | usuario_c@example.com | SubadqB |

## Exemplos de PIX

### 1. Criar PIX para Usuário A (SubadqA)

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

**Resposta esperada:**
```json
{
  "success": true,
  "data": {
    "transaction_id": "PIX-...",
    "external_id": "PIX123456789",
    "amount": "125.50",
    "status": "PENDING",
    "qr_code": "00020126580014br.gov.bcb.pix...",
    "qr_code_url": "https://exemplo.com/qrcode/123",
    "created_at": "2025-11-19T13:30:00.000000Z"
  }
}
```

### 2. Criar PIX para Usuário C (SubadqB)

```bash
curl -X POST http://localhost:8000/api/pix \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 3,
    "amount": 250.00,
    "description": "Pagamento de serviço",
    "customer_name": "Maria Oliveira",
    "customer_document": "98765432100"
  }'
```

### 3. PIX com valor mínimo

```bash
curl -X POST http://localhost:8000/api/pix \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 2,
    "amount": 0.01,
    "description": "Teste de valor mínimo"
  }'
```

### 4. PIX com erro (valor inválido)

```bash
curl -X POST http://localhost:8000/api/pix \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "amount": -10.00,
    "description": "Teste de erro"
  }'
```

**Resposta esperada (422):**
```json
{
  "success": false,
  "errors": {
    "amount": ["The amount field must be at least 0.01."]
  }
}
```

## Exemplos de Saque

### 1. Criar Saque para Usuário A (SubadqA)

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

**Resposta esperada:**
```json
{
  "success": true,
  "data": {
    "withdrawal_id": "WD-...",
    "external_id": "WD123456789",
    "amount": "500.00",
    "status": "PENDING",
    "requested_at": "2025-11-19T13:30:00.000000Z",
    "created_at": "2025-11-19T13:30:00.000000Z"
  }
}
```

### 2. Criar Saque para Usuário C (SubadqB)

```bash
curl -X POST http://localhost:8000/api/withdraw \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 3,
    "amount": 850.00,
    "bank_code": "260",
    "agency": "0001",
    "account": "1234567-8",
    "account_type": "savings",
    "holder_name": "Maria Oliveira",
    "holder_document": "98765432100"
  }'
```

### 3. Saque para conta corrente

```bash
curl -X POST http://localhost:8000/api/withdraw \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 2,
    "amount": 1500.00,
    "bank_code": "001",
    "agency": "1234",
    "account": "987654-3",
    "account_type": "checking",
    "holder_name": "Pedro Santos",
    "holder_document": "11122233344"
  }'
```

### 4. Saque com erro (campos obrigatórios)

```bash
curl -X POST http://localhost:8000/api/withdraw \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "amount": 100.00
  }'
```

**Resposta esperada (422):**
```json
{
  "success": false,
  "errors": {
    "bank_code": ["The bank code field is required."],
    "agency": ["The agency field is required."],
    "account": ["The account field is required."],
    "holder_name": ["The holder name field is required."],
    "holder_document": ["The holder document field is required."]
  }
}
```

## Testando com Postman

### Importar Collection

Crie uma Collection no Postman com os seguintes requests:

#### 1. Create PIX (SubadqA)
- **Method:** POST
- **URL:** `http://localhost:8000/api/pix`
- **Headers:** `Content-Type: application/json`
- **Body (raw JSON):**
```json
{
  "user_id": 1,
  "amount": 125.50,
  "description": "Pagamento via PIX",
  "customer_name": "João da Silva",
  "customer_document": "12345678900"
}
```

#### 2. Create PIX (SubadqB)
- **Method:** POST
- **URL:** `http://localhost:8000/api/pix`
- **Body (raw JSON):**
```json
{
  "user_id": 3,
  "amount": 250.00,
  "description": "Pagamento de serviço",
  "customer_name": "Maria Oliveira",
  "customer_document": "98765432100"
}
```

#### 3. Create Withdrawal (SubadqA)
- **Method:** POST
- **URL:** `http://localhost:8000/api/withdraw`
- **Body (raw JSON):**
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

#### 4. Create Withdrawal (SubadqB)
- **Method:** POST
- **URL:** `http://localhost:8000/api/withdraw`
- **Body (raw JSON):**
```json
{
  "user_id": 3,
  "amount": 850.00,
  "bank_code": "260",
  "agency": "0001",
  "account": "1234567-8",
  "account_type": "savings",
  "holder_name": "Maria Oliveira",
  "holder_document": "98765432100"
}
```

## Verificando Webhooks

Após criar uma transação PIX ou saque, aguarde alguns segundos (2s para PIX, 3s para saque) e verifique os logs:

```bash
tail -f storage/logs/laravel.log
```

Você verá logs como:

```
[2025-11-19 13:30:00] local.INFO: PIX transaction created {"transaction_id":"PIX-123","user_id":1,"subacquirer":"subadq_a"}
[2025-11-19 13:30:02] local.INFO: Processing PIX webhook {"pix_id":1,"payload":{...}}
[2025-11-19 13:30:02] local.INFO: PIX webhook processed successfully {"pix_id":1,"status":"CONFIRMED"}
```

## Verificando Banco de Dados

Para verificar as transações no banco:

```bash
php artisan tinker
```

```php
// Ver todas as transações PIX
App\Models\PixTransaction::with('user', 'subacquirer')->get();

// Ver última transação PIX
App\Models\PixTransaction::with('user', 'subacquirer')->latest()->first();

// Ver todos os saques
App\Models\Withdrawal::with('user', 'subacquirer')->get();

// Ver último saque
App\Models\Withdrawal::with('user', 'subacquirer')->latest()->first();

// Ver transações por usuário
$user = App\Models\User::find(1);
$user->pixTransactions;
$user->withdrawals;

// Ver transações por status
App\Models\PixTransaction::where('status', 'CONFIRMED')->get();
App\Models\Withdrawal::where('status', 'SUCCESS')->get();
```

## Teste de Carga

Para simular múltiplas requisições simultâneas:

### Usando Apache Bench (ab)

```bash
# Instalar ab (se necessário)
# sudo apt-get install apache2-utils  # Ubuntu/Debian
# brew install apache2                 # macOS

# Criar arquivo com payload
echo '{
  "user_id": 1,
  "amount": 100.00,
  "description": "Teste de carga"
}' > pix_payload.json

# Executar 100 requisições com 10 concorrentes
ab -n 100 -c 10 -p pix_payload.json -T application/json http://localhost:8000/api/pix
```

### Usando Artillery

```bash
# Instalar Artillery
npm install -g artillery

# Criar arquivo de teste artillery.yml
cat > artillery.yml << 'EOF'
config:
  target: 'http://localhost:8000'
  phases:
    - duration: 60
      arrivalRate: 5
      name: "Sustained load"
scenarios:
  - name: "Create PIX"
    flow:
      - post:
          url: "/api/pix"
          json:
            user_id: 1
            amount: 100.00
            description: "Teste de carga"
EOF

# Executar teste
artillery run artillery.yml
```

## Troubleshooting

### Queue não está processando

Verifique se o worker está rodando:
```bash
php artisan queue:work
```

### Erro de conexão com banco de dados

Verifique o `.env` e execute:
```bash
php artisan config:clear
php artisan migrate
```

### Logs não aparecem

Verifique permissões:
```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

### Clear cache

Se houver problemas de cache:
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

## Próximos Passos

Após testar a aplicação:

1. Verificar se os webhooks estão sendo processados corretamente
2. Confirmar que as transações estão mudando de status
3. Analisar os logs para entender o fluxo completo
4. Testar cenários de erro
5. Verificar performance com múltiplas requisições simultâneas
