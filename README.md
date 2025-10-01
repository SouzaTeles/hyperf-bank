# Hyperf Bank - Sistema de Saques PIX

Plataforma de conta digital que permite ao usuário realizar saques PIX do saldo disponível, com suporte a saques imediatos e agendados.

## Índice

- [Tecnologias Utilizadas](#tecnologias-utilizadas)
- [Arquitetura](#arquitetura)
- [Banco de Dados](#banco-de-dados)
- [Instalação e Execução](#instalação-e-execução)
- [API Endpoints](#api-endpoints)
- [Regras de Negócio](#regras-de-negócio)
- [Sistema de Emails](#sistema-de-emails)
- [Processamento de Saques Agendados](#processamento-de-saques-agendados)
- [Testes](#testes)
- [Decisões Técnicas](#decisões-técnicas)

---

## Tecnologias Utilizadas

### Core
- **PHP 8.3** - Recursos modernos (enums, readonly, attributes)
- **Hyperf 3** - Framework assíncrono baseado em Swoole
- **MySQL 8** - Banco de dados relacional
- **Docker & Docker Compose** - Containerização

### Email
- **Symfony Mailer** - Envio de emails
- **MJML** - Framework para templates de email responsivos
- **Mailhog** - Servidor SMTP de testes
- **Node.js** - Necessário para compilar templates MJML

### Qualidade
- **PHPUnit** - Testes automatizados
- **PHP CS Fixer** - Padronização de código
- **PHPStan** - Análise estática

### Justificativas


**MJML:**

MJML é um framework de markup que compila para HTML otimizado para emails. Embora adicione a dependência do Node.js ao projeto, traz benefícios significativos:

- **Responsividade Automática**: Garante compatibilidade com todos os clientes de email (Gmail, Outlook, Apple Mail, etc.) sem necessidade de CSS inline manual
- **Componentes Reutilizáveis**: Sistema de componentes que simplifica a criação de layouts complexos
- **Manutenibilidade**: Código MJML é muito mais limpo e legível que HTML de email tradicional
- **Grid System**: Sistema de grid simplificado que funciona em todos os clientes
- **Fallbacks Automáticos**: Gera automaticamente fallbacks para clientes antigos
- **Validação**: Valida o markup durante a compilação, evitando emails quebrados

Exemplo de comparação:
```mjml
<!-- MJML (simples e legível) -->
<mj-section>
  <mj-column>
    <mj-text>Olá, {{name}}!</mj-text>
  </mj-column>
</mj-section>

<!-- Compila para ~50 linhas de HTML com tabelas e CSS inline -->
```

A adição do Node.js ao Dockerfile é um trade-off aceitável considerando a qualidade e produtividade ganhas na criação de emails profissionais. Geralmente quando se cria um e-mail com HTML e CSS corre-se o risco de que o email não seja renderizado corretamente em todos os clientes de email, [já que cada cliente tem sua própria forma de renderização
](https://www.caniemail.com).

---

## Arquitetura

### Padrões Aplicados

- **Service Layer Pattern** - Lógica de negócio isolada
- **Repository Pattern** - Abstração via Eloquent ORM
- **DTO (Data Transfer Objects)** - Validação centralizada
- **Single Responsibility** - Cada classe tem uma responsabilidade
- **Dependency Injection** - Gerenciamento via container

### Estrutura de Diretórios

```
app/
├── Command/              # Comandos CLI (cron)
├── Controller/           # Controllers HTTP
├── Exception/            # Exceções customizadas
├── Mail/                 # Classes de email e templates
│   └── templates/        # Templates MJML
├── Model/                # Eloquent Models
├── Request/              # Validação de requests
└── Service/              # Lógica de negócio
```

---

## Banco de Dados

### Tabelas

#### **account**
```sql
- id: VARCHAR(36) PRIMARY KEY (UUID)
- name: VARCHAR(255)
- balance: DECIMAL(15,2)
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

#### **account_withdraw**
```sql
- id: VARCHAR(36) PRIMARY KEY (UUID)
- account_id: VARCHAR(36) FK
- method: VARCHAR(50) (ex: 'pix')
- amount: DECIMAL(15,2)
- scheduled: BOOLEAN
- scheduled_for: DATETIME NULL
- done: BOOLEAN
- error: BOOLEAN
- error_reason: VARCHAR(255) NULL
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

#### **account_withdraw_pix**
```sql
- account_withdraw_id: VARCHAR(36) PK/FK
- type: VARCHAR(50) (ex: 'email')
- key: VARCHAR(255)
```

### Relacionamentos
- `account` 1:N `account_withdraw`
- `account_withdraw` 1:1 `account_withdraw_pix`

---

## Instalação e Execução

### Pré-requisitos
- Docker
- Docker Compose

### Iniciar o Projeto

```bash
# 1. Clonar o repositório
git clone <repo-url>
cd hyperf-skeleton

# 2. Copiar arquivo de ambiente
cp .env.example .env

# 3. Subir containers
docker compose up -d

# 4. Aguardar inicialização (30 segundos)

# 5. Rodar migrations
docker compose exec hyperf-pix php bin/hyperf.php migrate

# 6. (Opcional) Popular dados de teste
docker compose exec hyperf-pix php bin/hyperf.php db:seed
```

### Serviços

| Serviço | URL | Descrição |
|---------|-----|-----------|
| API | http://localhost:9501 | API REST |
| Mailhog | http://localhost:8025 | Interface de emails |
| MySQL | localhost:3306 | Banco de dados |

### Comandos Úteis

```bash
make bash          # Acessar container
make migrate       # Rodar migrations
make test          # Rodar testes
make logs          # Ver logs
make cron-process  # Processar manualmente saques agendados
```

---

## API Endpoints

### **POST /account/{accountId}/balance/withdraw**

Realiza um saque PIX imediato ou agendado.

**Request:**
```json
{
  "method": "pix",
  "pix": {
    "type": "email",
    "key": "fulano@email.com"
  },
  "amount": 150.75,
  "schedule": null  // ou "2025-12-31 15:00"
}
```

**Response 200:**
```json
{
  "account_id": "uuid",
  "withdraw_id": "uuid",
  "amount": 150.75,
  "new_balance": 849.25,
  "scheduled": false
}
```

**Response 400 - Saldo Insuficiente:**
```json
{
  "message": "Insufficient balance",
  "balance": 100.00,
  "requested": 150.75
}
```

**Response 400 - Validação:**
```json
{
  "message": "Validation failed",
  "errors": {
    "schedule": ["A data de agendamento deve ser no futuro."]
  }
}
```

### Exemplos de Uso

Após popular o banco com `php bin/hyperf.php db:seed`, você terá 3 contas disponíveis:

- **João Silva**: `550e8400-e29b-41d4-a716-446655440001` (R$ 1.000,00)
- **Maria Santos**: `550e8400-e29b-41d4-a716-446655440002` (R$ 2.500,50)
- **Pedro Costa**: `550e8400-e29b-41d4-a716-446655440003` (R$ 500,00)

**Saque Imediato:**
```bash
curl -X POST http://localhost:9501/account/550e8400-e29b-41d4-a716-446655440001/balance/withdraw \
  -H "Content-Type: application/json" \
  -d '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "joao.silva@email.com"
    },
    "amount": 100.00
  }'
```

**Saque Agendado:**
```bash
curl -X POST http://localhost:9501/account/550e8400-e29b-41d4-a716-446655440001/balance/withdraw \
  -H "Content-Type: application/json" \
  -d '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "joao.silva@email.com"
    },
    "amount": 50.00,
    "schedule": "2025-12-31 15:00"
  }'
```

**Verificar emails enviados:** http://localhost:8025

---

## Regras de Negócio

### Saques Imediatos
- Debita saldo imediatamente
- Envia email de confirmação
- Não permite saldo negativo
- Não permite valor maior que saldo disponível

### Saques Agendados
- NÃO debita saldo no momento da criação, [conforme funcionamento do pix agendado](https://www.serasa.com.br/blog/pix-agendado/#:~:text=Como%20o%20dinheiro%20s%C3%B3%20sai%20na%20data%20agendada%2C%20voc%C3%AA%20precisa%20garantir%20que%20haver%C3%A1%20saldo%20suficiente)
- Envia email de agendamento
- Processado via cron na data/hora agendada
- Não permite agendar para o passado
- Não permite agendar para mais de 7 dias
- Se não houver saldo no momento do processamento, marca como erro e notifica

### Validações PIX
- Atualmente apenas chaves do tipo `email`
- Arquitetura preparada para outros tipos (CPF, telefone, aleatória)

---

## Sistema de Emails

### Tipos de Email

**1. Agendamento de Saque** (cor verde)
- Enviado ao criar um saque agendado
- Informa data/hora do agendamento
- Avisa que saldo será debitado apenas quando processar

**2. Confirmação de Saque** (cor azul)
- Enviado quando saque é processado com sucesso
- Informa valor debitado e data/hora do processamento

**3. Erro no Processamento** (cor vermelha)
- Enviado quando saque agendado falha
- Informa motivo do erro (ex: saldo insuficiente)
- Orienta usuário sobre próximos passos

### Mensagens de Erro Tratadas

```php
InsufficientBalanceException → 
  "Saldo insuficiente. Certifique-se de que sua conta possui saldo disponível."

Erro Genérico → 
  "Não foi possível processar seu saque. Entre em contato com o suporte."
```

Acesse http://localhost:8025 para visualizar os emails enviados.

---

## Processamento de Saques Agendados

### Comando CLI

```bash
# Processar manualmente
docker compose exec hyperf-pix php bin/hyperf.php withdraw:process-scheduled

# Ou via Makefile
make cron-process
```

### Configuração Cron em Produção

```bash
# Processar a cada minuto
* * * * * cd /opt/www && php bin/hyperf.php withdraw:process-scheduled >> /opt/www/runtime/logs/cron.log 2>&1
```

### Fluxo de Processamento

1. Busca saques com `scheduled = true`, `done = false`, `error = false` e `scheduled_for <= now()`
2. Para cada saque: valida saldo, debita, marca como `done`, envia email de confirmação
3. Em caso de erro: marca `error = true`, envia email de erro, registra em logs

### Docker (Cron Automático)

O container já está configurado para rodar o cron automaticamente via entrypoint que inicia tanto o cron quanto o Hyperf. Sendo assim, é possivel fazer um teste manual de agendamento e aguardar sua execução.

---

## Testes

### Estrutura de Testes

O projeto possui 2 tipos de testes:

- **Testes Unitários** (`test/Unit/`) - Testam classes isoladas com mocks
- **Testes de Integração** (`test/Cases/`) - Testam fluxos completos com banco de dados

### Executar Testes

```bash
# Todos os testes
make test

# Apenas testes unitários (rápidos, sem banco)
make test-unit

# Apenas testes de integração (com banco)
make test-integration

# Teste específico
make test-filter filter=testProcessScheduledWithdrawWithSufficientBalance

# Cobertura de código
make test-coverage
```

### Testes Unitários

**Service Layer:**
- `WithdrawServiceTest` - Testa lógica de execução de saques
  - Saque com saldo suficiente
  - Exceção quando saldo insuficiente
  - Não permite saldo negativo
  - Atualiza status do saque
  - Saque com saldo exato

**Exceções:**
- `InsufficientBalanceExceptionTest` - Testa exceção customizada
  - Armazena saldo atual e solicitado
  - Funciona com valores zero e grandes

**Models:**
- `AccountTest` - Testa model Account
  - Configuração de tabela, primary key, timestamps
  - Fillable attributes e casts
  
- `AccountWithdrawTest` - Testa model AccountWithdraw
  - Configuração de campos e casts
  - Constantes e valores booleanos

**Validações:**
- `WithdrawRequestTest` - Testa regras de validação
  - Formato de data e validações de campo
  - Método aceita PIX/pix (case insensitive)
  - Amount >= 1
  - Schedule futuro e <= 7 dias

### Testes de Integração

**Fluxo Completo:**
- Saque imediato com saldo suficiente/insuficiente
- Validação de agendamento (passado/futuro/limite 7 dias)
- Processamento de saques agendados
- Processamento com saldo insuficiente
- Não processa saques futuros
- Não reprocessa saques já feitos
- Processamento de múltiplos saques

---

## Decisões Técnicas

### 1. Service Layer Pattern

Lógica de negócio isolada e testável, controllers finos focados em validação e resposta HTTP.

```php
WithdrawService::createWithdraw()           // Saque imediato/agendado
WithdrawService::executeWithdraw()          // Núcleo da execução
WithdrawService::processScheduledWithdraws() // Cron
```

### 2. Exceções Customizadas

Permite tratamento específico de erros e mensagens amigáveis ao usuário.

```php
throw new InsufficientBalanceException(
    'Insufficient balance',
    $current,
    $requested
);
```

### 3. Separação de Erros de Processamento vs Email

Email não deve impactar transação de saque. Try-catch em camadas com logs separados:
- Erros de processamento causam rollback e notificação
- Erros de email são apenas logados, não impedem o saque

### 4. Transações Atômicas

Garante consistência do saldo através de transações com rollback automático em caso de erro.

---

## Performance e Escalabilidade

### Otimizações

- **Connection Pooling** - Hyperf mantém pool de conexões com banco
- **Coroutines** - Operações I/O não bloqueantes
- **Eager Loading** - Evita N+1 queries
- **Índices** - Primary keys e foreign keys indexadas

### Escalabilidade Horizontal

- Stateless: sem estado na aplicação
- UUIDs: sem conflitos entre instâncias
- Transações atômicas: sem race conditions
- Logs estruturados: facilita agregação

---

## Observabilidade

### Logs

```bash
make logs          # Logs gerais
make logs-withdraw # Logs de saques
make logs-email    # Logs de emails
make logs-error    # Logs de erros
```

Logs estruturados com contexto:

```php
$this->logger->info('Withdraw created', [
    'withdraw_id' => $id,
    'account_id' => $accountId,
    'amount' => $amount,
    'scheduled' => $isScheduled
]);
```

---

## Segurança

- Validação de input via Hyperf Validation
- UUIDs: não expõe IDs sequenciais
- Transações atômicas: previne race conditions
- Mensagens de erro genéricas: não vaza informações sensíveis
- Environment variables para configurações

---

## Próximas Melhorias

- Autenticação JWT
- Rate limiting
- Suporte a outros tipos de chave PIX (CPF, telefone, aleatória)
- Retry logic para emails
- O chave primaria de account pode ser um int no banco (para otimizar a performance) e possuir UUID como outra coluna (ou ainda utilizar o hash do inteiro como UUID)


---

## Autor

Desenvolvido como teste técnico demonstrando conhecimentos em PHP moderno, arquitetura de software, Hyperf Framework, design patterns e testes automatizados.
