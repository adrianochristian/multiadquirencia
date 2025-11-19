# Guia de Uso - Postman Collection

Este guia explica como importar e usar a collection do Postman para testar a API de Multiadquirência.

## 📦 Arquivos Disponíveis

- **`Multiadquirencia.postman_collection.json`** - Collection com todas as requisições
- **`Multiadquirencia.postman_environment.json`** - Environment com variáveis configuradas

## 🚀 Como Importar no Postman

### Passo 1: Importar a Collection

1. Abra o Postman
2. Clique em **"Import"** (canto superior esquerdo)
3. Selecione o arquivo `Multiadquirencia.postman_collection.json`
4. Clique em **"Import"**

### Passo 2: Importar o Environment

1. No Postman, clique no ícone de **engrenagem** (⚙️) no canto superior direito
2. Clique em **"Import"**
3. Selecione o arquivo `Multiadquirencia.postman_environment.json`
4. Clique em **"Import"**

### Passo 3: Ativar o Environment

1. No dropdown de environments (canto superior direito)
2. Selecione **"Multiadquirência - Local"**

## 📋 Estrutura da Collection

A collection está organizada em:

```
Sistema Multiadquirência - Completo
│
├── PIX
│   ├── Cenários de Sucesso
│   │   ├── Criar PIX - Usuário 1 (SubadqA)
│   │   ├── Criar PIX - Usuário 3 (SubadqB)
│   │   ├── Criar PIX - Valor Mínimo (0.01)
│   │   ├── Criar PIX - Valor Alto
│   │   └── Criar PIX - Campos Opcionais Vazios
│   │
│   └── Cenários de Erro
│       ├── Erro - Valor Negativo
│       ├── Erro - Valor Zero
│       ├── Erro - Usuário Inexistente
│       ├── Erro - Campos Obrigatórios Faltando
│       └── Erro - Tipo de Dado Inválido
│
├── Saque (Withdrawal)
│   ├── Cenários de Sucesso
│   │   ├── Criar Saque - Usuário 1 (SubadqA)
│   │   ├── Criar Saque - Usuário 3 (SubadqB)
│   │   ├── Criar Saque - Conta Corrente
│   │   ├── Criar Saque - Conta Poupança
│   │   └── Criar Saque - Sem account_type
│   │
│   └── Cenários de Erro
│       ├── Erro - Campos Obrigatórios Faltando
│       ├── Erro - Valor Inválido (zero)
│       ├── Erro - account_type Inválido
│       └── Erro - Usuário Inexistente
│
└── Testes de Carga
    ├── PIX - Requisições Múltiplas
    └── Saque - Requisições Múltiplas
```

## 🎯 Como Testar

### Teste Básico - PIX

1. **Inicie os serviços:**
   ```bash
   # Terminal 1
   php artisan serve

   # Terminal 2
   php artisan queue:work
   ```

2. **No Postman:**
   - Abra: `PIX > Cenários de Sucesso > Criar PIX - Usuário 1 (SubadqA)`
   - Clique em **"Send"**
   - Verifique a resposta (status 201)

3. **Aguarde 2 segundos**

4. **Verifique os logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Teste Básico - Saque

1. **No Postman:**
   - Abra: `Saque (Withdrawal) > Cenários de Sucesso > Criar Saque - Usuário 1 (SubadqA)`
   - Clique em **"Send"**
   - Verifique a resposta (status 201)

2. **Aguarde 3 segundos**

3. **Verifique os logs** para ver o webhook sendo processado

### Teste de Erros

Execute os requests da pasta **"Cenários de Erro"** para testar validações:

- Todos devem retornar status **422** (Unprocessable Entity)
- Verificar mensagens de erro adequadas

## 🧪 Testes Automatizados

A collection inclui **testes automatizados** que validam:

✅ Status code correto
✅ Estrutura da resposta
✅ Presença de campos obrigatórios
✅ Tipos de dados

Para ver os resultados:
1. Execute um request
2. Clique na aba **"Test Results"**
3. Veja quais testes passaram/falharam

## 🔄 Collection Runner (Teste de Carga)

Para simular múltiplas requisições:

### Método 1: Via Collection Runner

1. Clique nos **"..."** da collection
2. Selecione **"Run collection"**
3. Configure:
   - **Iterations:** 10 (quantas vezes executar)
   - **Delay:** 100ms (intervalo entre requests)
4. Selecione apenas as pastas/requests que deseja testar
5. Clique em **"Run Multiadquirência..."**

### Método 2: Via Pasta de Testes de Carga

1. Abra: `Testes de Carga > PIX - Requisições Múltiplas`
2. Clique em **"Run"**
3. Configure iterations e delay
4. Execute

Isso simulará carga no sistema (útil para testar filas).

## 📊 Variáveis de Environment

As seguintes variáveis estão configuradas:

| Variável | Valor | Descrição |
|----------|-------|-----------|
| `base_url` | `http://localhost:8000` | URL base da API |
| `user_id_subadq_a` | `1` | ID do usuário com SubadqA |
| `user_id_subadq_b` | `3` | ID do usuário com SubadqB |
| `last_pix_transaction_id` | (vazio) | Armazena último PIX criado |
| `last_withdrawal_id` | (vazio) | Armazena último saque criado |

### Como Usar Variáveis

Nas requisições, use `{{variavel}}`:

```json
{
  "user_id": {{user_id_subadq_a}},
  "amount": 100.00
}
```

### Variáveis Dinâmicas do Postman

A collection usa variáveis dinâmicas:

- `{{$randomInt}}` - Número aleatório
- `{{$randomPrice}}` - Preço aleatório
- `{{$randomFullName}}` - Nome completo aleatório
- `{{$randomBankAccount}}` - Número de conta aleatório
- `{{$timestamp}}` - Timestamp atual

## 🔍 Inspecionando Respostas

### Console do Postman

Veja logs detalhados:
1. Abra: **View > Show Postman Console** (Alt+Ctrl+C)
2. Execute um request
3. Veja:
   - Request completo enviado
   - Response completo recebido
   - Logs dos scripts

### Salvando Respostas

Para comparar respostas:
1. Execute um request
2. Clique em **"Save Response"**
3. Nomeie e salve
4. Acesse via **"Examples"** do request

## 📝 Exemplos de Payloads

### PIX Mínimo (apenas obrigatórios)

```json
{
  "user_id": 1,
  "amount": 125.50
}
```

### PIX Completo

```json
{
  "user_id": 1,
  "amount": 125.50,
  "description": "Pagamento de serviço",
  "customer_name": "João da Silva",
  "customer_document": "12345678900"
}
```

### Saque Completo

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

## 🐛 Troubleshooting

### Erro: "Could not send request"

**Solução:**
- Verifique se o servidor está rodando: `php artisan serve`
- Verifique a URL em `{{base_url}}`

### Erro: "User does not have a subacquirer configured"

**Solução:**
- Execute os seeders: `php artisan db:seed`
- Verifique se os usuários existem no banco

### Webhook não está sendo processado

**Solução:**
- Verifique se o queue worker está rodando: `php artisan queue:work`
- Aguarde 2-3 segundos após criar a transação
- Verifique os logs: `tail -f storage/logs/laravel.log`

### Todos os requests retornam 500

**Solução:**
- Verifique o arquivo `.env`
- Execute: `php artisan config:clear`
- Verifique os logs de erro

## 📈 Dicas de Uso

### 1. Organize por Pastas

Crie suas próprias pastas para testes específicos:
- Clique com botão direito na collection
- **"Add Folder"**
- Arraste requests para a pasta

### 2. Salve Requests Frequentes

Duplique e modifique requests:
- Clique nos **"..."** do request
- **"Duplicate"**
- Renomeie e modifique conforme necessário

### 3. Use Pre-request Scripts

Para gerar dados dinâmicos:

```javascript
// Gerar CPF aleatório
pm.environment.set("random_cpf",
  Math.floor(Math.random() * 100000000000).toString()
);
```

### 4. Monitore Performance

Ative o **"Postman Interceptor"** para:
- Ver requests reais
- Debugar problemas de rede
- Analisar headers

## 🎓 Recursos Adicionais

### Documentação da Collection

A collection possui descrições detalhadas em cada request:
- Abra um request
- Leia a aba **"Description"**
- Veja exemplos de uso

### Exportar Resultados

Após executar o Collection Runner:
1. Clique em **"Export Results"**
2. Salve o arquivo JSON
3. Use para relatórios ou análise

### Compartilhar Collection

Para compartilhar com a equipe:
1. Clique em **"Share"**
2. Gere link público ou workspace
3. Ou exporte e envie o arquivo JSON

## ✅ Checklist de Teste

Use esta checklist para garantir que testou tudo:

### PIX
- [ ] Criar PIX com SubadqA (Usuário 1)
- [ ] Criar PIX com SubadqB (Usuário 3)
- [ ] Webhook sendo processado após 2s
- [ ] Status mudando de PENDING para CONFIRMED/PAID
- [ ] Validação de valor negativo
- [ ] Validação de campos obrigatórios

### Saque
- [ ] Criar Saque com SubadqA (Usuário 1)
- [ ] Criar Saque com SubadqB (Usuário 3)
- [ ] Webhook sendo processado após 3s
- [ ] Status mudando de PENDING para SUCCESS/DONE
- [ ] Conta corrente (checking)
- [ ] Conta poupança (savings)
- [ ] Validação de campos obrigatórios

### Performance
- [ ] Executar 10+ requisições simultâneas
- [ ] Verificar se filas estão processando
- [ ] Verificar logs sem erros

## 📞 Suporte

Se encontrar problemas:

1. Verifique a documentação: [README.md](README.md)
2. Veja exemplos: [EXAMPLES.md](EXAMPLES.md)
3. Consulte arquitetura: [ARCHITECTURE.md](ARCHITECTURE.md)
4. Verifique logs: `storage/logs/laravel.log`

---

**Bons testes!** 🚀
