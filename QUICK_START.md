# 🚀 Quick Start - Multiadquirência

Guia rápido para começar a usar o sistema em **5 minutos**.

## ⚡ Setup Rápido

### 1. Já está tudo instalado? Pule para o passo 2!

```bash
# Se ainda não rodou composer install
composer install
```

### 2. Configure o Banco de Dados

O projeto já está configurado com SQLite. Só precisa rodar as migrations:

```bash
# Rodar migrations
php artisan migrate

# Popular banco com dados de teste
php artisan db:seed
```

✅ **Pronto!** Banco configurado com:
- 2 subadquirentes (SubadqA e SubadqB)
- 3 usuários de teste

---

## 🎯 Testar em 3 Comandos

### Terminal 1: Servidor
```bash
php artisan serve
```

### Terminal 2: Fila (para processar webhooks)
```bash
php artisan queue:work
```

### Terminal 3: Testar API
```bash
# Criar PIX
curl -X POST http://localhost:8000/api/pix \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "amount": 125.50,
    "description": "Teste PIX",
    "customer_name": "João da Silva",
    "customer_document": "12345678900"
  }'
```

**Aguarde 2 segundos e veja o webhook sendo processado nos logs!**

---

## 📱 Usando Postman (Recomendado)

### Passo 1: Importar
1. Abra o Postman
2. Clique em **Import**
3. Arraste o arquivo `Multiadquirencia.postman_collection.json`
4. Repita com `Multiadquirencia.postman_environment.json`

### Passo 2: Configurar
1. No dropdown de environments (canto superior direito)
2. Selecione **"Multiadquirência - Local"**

### Passo 3: Testar
1. Abra: `PIX > Cenários de Sucesso > Criar PIX - Usuário 1`
2. Clique em **Send**
3. ✅ Sucesso! Veja a resposta

**Mais detalhes:** [POSTMAN_GUIDE.md](POSTMAN_GUIDE.md)

---

## 📊 Usuários Disponíveis

Após rodar `php artisan db:seed`:

| ID | Email | Senha | Subadquirente |
|----|-------|-------|---------------|
| 1 | usuario_a@example.com | password | SubadqA |
| 2 | usuario_b@example.com | password | SubadqA |
| 3 | usuario_c@example.com | password | SubadqB |

---

## 🔥 Exemplos Rápidos

### Criar PIX (SubadqA)
```bash
curl -X POST http://localhost:8000/api/pix \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "amount": 100.00}'
```

### Criar PIX (SubadqB)
```bash
curl -X POST http://localhost:8000/api/pix \
  -H "Content-Type: application/json" \
  -d '{"user_id": 3, "amount": 200.00}'
```

### Criar Saque
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

---

## 🔍 Verificar Webhooks

### Ver Logs em Tempo Real
```bash
tail -f storage/logs/laravel.log
```

Você verá logs como:
```
[2025-11-19 13:30:00] INFO: PIX transaction created
[2025-11-19 13:30:02] INFO: Processing PIX webhook
[2025-11-19 13:30:02] INFO: PIX webhook processed successfully
```

### Ver Dados no Banco
```bash
php artisan tinker
```

```php
// Ver última transação PIX
App\Models\PixTransaction::latest()->first();

// Ver último saque
App\Models\Withdrawal::latest()->first();

// Ver PIX por status
App\Models\PixTransaction::where('status', 'CONFIRMED')->get();
```

---

## 🐛 Problemas Comuns

### ❌ "Subacquirer not found"
**Solução:** Rode os seeders
```bash
php artisan db:seed
```

### ❌ "Queue not processing"
**Solução:** Inicie o worker
```bash
php artisan queue:work
```

### ❌ "Connection refused"
**Solução:** Inicie o servidor
```bash
php artisan serve
```

### ❌ "Migration not found"
**Solução:** Rode as migrations
```bash
php artisan migrate
```

---

## 📚 Documentação Completa

Para mais detalhes, consulte:

| Arquivo | Descrição |
|---------|-----------|
| [README.md](README.md) | Documentação completa |
| [EXAMPLES.md](EXAMPLES.md) | Exemplos detalhados |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Arquitetura do sistema |
| [POSTMAN_GUIDE.md](POSTMAN_GUIDE.md) | Guia do Postman |
| [FILES_SUMMARY.md](FILES_SUMMARY.md) | Lista de arquivos |

---

## ✅ Checklist Inicial

Antes de começar, garanta que:

- [ ] PHP 8.2+ instalado
- [ ] Composer instalado
- [ ] Projeto clonado
- [ ] `composer install` executado
- [ ] `.env` configurado (já está!)
- [ ] Migrations rodadas (`php artisan migrate`)
- [ ] Seeders rodados (`php artisan db:seed`)
- [ ] Servidor rodando (`php artisan serve`)
- [ ] Queue worker rodando (`php artisan queue:work`)

---

## 🎓 Próximos Passos

Depois do Quick Start:

1. ✅ **Teste todos os endpoints** via Postman
2. 📖 **Leia a documentação completa** em [README.md](README.md)
3. 🏗️ **Entenda a arquitetura** em [ARCHITECTURE.md](ARCHITECTURE.md)
4. 🧪 **Execute testes de carga** conforme [EXAMPLES.md](EXAMPLES.md)

---

## 💡 Dicas

### Limpar Cache (se necessário)
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Resetar Banco
```bash
php artisan migrate:fresh --seed
```

### Ver Todas as Rotas
```bash
php artisan route:list
```

### Ver Status da Fila
```bash
php artisan queue:failed
```

---

## 🎯 Teste Completo em 30 Segundos

```bash
# 1. Setup (uma vez só)
php artisan migrate && php artisan db:seed

# 2. Inicie serviços (2 terminais)
php artisan serve &
php artisan queue:work &

# 3. Teste PIX
curl -X POST http://localhost:8000/api/pix \
  -H "Content-Type: application/json" \
  -d '{"user_id": 1, "amount": 100.00}'

# 4. Aguarde 2 segundos e veja nos logs!
tail -f storage/logs/laravel.log
```

---

**Pronto para testar!** 🚀

Se tiver dúvidas, consulte a [documentação completa](README.md).
