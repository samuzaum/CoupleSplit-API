# CoupleSplit

Sistema de gestão financeira para casais — divida despesas, acompanhe dívidas e registre pagamentos entre parceiros.

---

## Requisitos

- PHP 8.2+
- Composer
- MySQL ou SQLite
- Node.js (para assets)

---

## Instalação

```bash
git clone https://github.com/samuzaum/CoupleSplit-API.git
cd CoupleSplit-API/couplesplit

composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate
```

Configure o banco no `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=couplesplit
DB_USERNAME=root
DB_PASSWORD=
```

```bash
php artisan migrate
php artisan serve
```

Acesse: `http://localhost:8000`

---

## Como usar

### 1. Criar conta

Acesse `/register` e crie sua conta com nome, e-mail e senha.

---

### 2. Criar ou entrar em um casal

Após o login você será redirecionado ao dashboard. Se ainda não faz parte de um casal, duas opções aparecem:

**Criar um casal**
- Acesse `/couples/create`
- Dê um nome ao casal (ex: "Samuel & Evelyn")
- Um token de 6 caracteres é gerado automaticamente

**Entrar em um casal existente**
- Acesse `/couple/join`
- Insira o token compartilhado pelo seu parceiro

**Convidar por e-mail**
- No painel do casal, use a opção de convite por e-mail
- O parceiro recebe um link único para entrar

---

### 3. Cadastrar cartões (opcional)

Acesse `/cards` → **Novo cartão**

- **Débito**: o valor é contabilizado na data da compra
- **Crédito**: informe o dia de fechamento da fatura — o sistema calcula automaticamente em qual mês a despesa será cobrada

---

### 4. Registrar despesas

Acesse `/expenses/create` e preencha:

| Campo | Descrição |
|---|---|
| Descrição | Nome da despesa (ex: "Mercado") |
| Valor | Valor total pago |
| Data | Data em que ocorreu |
| Parcelas | 1 = à vista, 2+ = parcelado (máx 48x) |
| Cartão | Opcional — define a data de faturamento |
| Compartilhada | Marcado = divide com o parceiro, desmarcado = pessoal |

Despesas compartilhadas dividem o valor automaticamente entre os dois e geram os registros de débito/crédito.

---

### 5. Acompanhar o saldo — Dashboard

Acesse `/dashboard` para ver:

- **Saldo líquido** — quem deve quanto para quem no momento
- **Dívidas em aberto** — suas despesas pendentes de pagamento
- **Créditos a receber** — o que seu parceiro ainda te deve
- **Despesas recentes** — últimas 5 despesas do casal

---

### 6. Registrar pagamentos

Quando você quitar uma dívida com o parceiro:

- Acesse `/payments/create`
- Veja suas dívidas em aberto listadas
- Informe o valor pago
- O sistema consome os débitos em ordem cronológica (mais antigos primeiro)
- Se pagar mais do que deve, o excedente vira crédito a seu favor

**Liquidar tudo de uma vez:**
No dashboard, use o botão de liquidar para quitar todas as dívidas abertas automaticamente.

---

### 7. Ver histórico de despesas

| Rota | O que mostra |
|---|---|
| `/expenses` | Todas as despesas do casal |
| `/expenses/couple` | Apenas despesas compartilhadas |
| `/expenses/personal` | Apenas suas despesas pessoais |

---

## API REST

A API usa autenticação via token (Laravel Sanctum). Para gerar um token, use o fluxo de login via Sanctum.

Base URL: `http://localhost:8000/api`

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/dashboard` | Saldo, dívidas e despesas recentes |
| POST | `/dashboard/settle` | Liquidar todas as dívidas de uma vez |
| GET | `/expenses` | Listar despesas do casal |
| POST | `/expenses` | Criar nova despesa |
| POST | `/payments` | Registrar pagamento |

**Exemplo — criar despesa:**
```json
POST /api/expenses
Authorization: Bearer {token}

{
  "description": "Mercado",
  "amount": 250.00,
  "expense_date": "2026-05-07",
  "is_shared": true,
  "installments": 1
}
```

**Exemplo — registrar pagamento:**
```json
POST /api/payments
Authorization: Bearer {token}

{
  "amount": 125.00
}
```

---

## Estrutura resumida

```
app/
├── Http/Controllers/
│   ├── Api/               # Controllers da API REST
│   ├── ExpenseController  # Despesas
│   ├── PaymentController  # Pagamentos
│   ├── DashboardController
│   ├── CoupleController
│   └── CardController
├── Models/
│   ├── Expense / ExpenseSplit / ExpenseInstallment
│   ├── Balance            # Ledger de débito/crédito
│   ├── Payment / PaymentSplit
│   ├── Couple / CoupleInvitation
│   └── Card
└── Services/
    ├── ExpenseService     # Balances, parcelas, billing date
    └── PaymentService     # Processamento de pagamentos
```
