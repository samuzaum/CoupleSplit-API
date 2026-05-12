<div align="center">

# 💑 CoupleSplit

**Gestão financeira inteligente para casais**

Divida despesas, acompanhe dívidas, defina metas e mantenha as finanças do casal sempre organizadas.

[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-3-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)

</div>

---

## Sobre o projeto

O CoupleSplit nasceu de uma necessidade real: casais que dividem despesas precisam de uma forma simples e transparente de saber quem deve quanto — sem planilhas, sem discussões.

O sistema registra despesas compartilhadas e pessoais, calcula automaticamente o saldo líquido entre parceiros, gerencia parcelas de cartão de crédito e ainda oferece ferramentas de planejamento como metas de economia, orçamentos por categoria e simulador de compras.

---

## Funcionalidades

### Financeiro
- **Saldo em tempo real** — o dashboard mostra exatamente quem deve quanto para quem
- **Despesas compartilhadas** — divisão configurável (50/50, proporcional à renda ou personalizada)
- **Despesas pessoais** — cada um acompanha seus próprios gastos
- **Pagamentos com amortização** — valores pagos são abatidos nas dívidas mais antigas primeiro; excedente vira crédito
- **Liquidação total** — quite todas as dívidas em aberto com um clique

### Cartões e Parcelas
- **Cartões de débito e crédito** — cadastre seus cartões com dia de fechamento de fatura
- **Parcelamento automático** — divida uma compra em até 48x; o sistema distribui cada parcela no mês correto
- **Painel de parcelas abertas** — visualize tudo que ainda está em aberto por mês de vencimento

### Planejamento
- **Orçamentos por categoria** — defina limites mensais e receba alertas ao se aproximar ou estourar
- **Metas de economia** — crie objetivos com valor-alvo, prazo e progresso visual
- **Simulador de compras** — simule o impacto de uma compra parcelada nos próximos meses com base na renda do casal
- **Despesas recorrentes** — marque uma despesa como recorrente e ela é gerada automaticamente todo mês

### Visualização
- **Calendário financeiro** — veja despesas e vencimentos organizados por dia
- **Resumo mensal** — totais por categoria, por pagador e comparativo com o mês anterior
- **Gráficos** — gastos mensais (barras) e por categoria (donut)
- **Log de atividades** — histórico de todas as ações do casal

### Sistema
- **Dark mode** — com detecção automática do tema do sistema operacional
- **Notificações persistentes** — alertas de fechamento de fatura e orçamentos estourados
- **Exportação CSV** — exporte despesas e pagamentos filtrados
- **API REST** — endpoints autenticados via Laravel Sanctum para integração externa
- **Categorias personalizadas** — além das categorias padrão, crie as suas

---

## Tecnologias

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.2, Laravel 12 |
| Frontend | Blade, Tailwind CSS v3, Alpine.js |
| Banco de dados | MySQL / SQLite |
| Autenticação | Laravel Breeze + Sanctum |
| Build | Vite |
| Gráficos | Chart.js |

---

## Pré-requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL ou SQLite

---

## Instalação

```bash
# 1. Clone o repositório
git clone https://github.com/samuzaum/CoupleSplit-API.git
cd CoupleSplit-API/couplesplit

# 2. Instale as dependências
composer install
npm install && npm run build

# 3. Configure o ambiente
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
# 4. Execute as migrations
php artisan migrate

# 5. Inicie o servidor
php artisan serve
```

Acesse: [http://localhost:8000](http://localhost:8000)

---

## Como usar

### 1. Criar conta e casal

Registre-se em `/register`. Após o login, você pode:

- **Criar um casal** — dê um nome e compartilhe o token gerado com seu parceiro
- **Entrar num casal** — insira o token recebido em `/couple/join`
- **Convidar por e-mail** — envie um link de convite diretamente pelo painel do casal

### 2. Registrar despesas

Em `/expenses/create`, informe descrição, valor, data e:

- **Compartilhada** → divide com o parceiro (ratio configurável)
- **Pessoal** → só sua
- **Com cartão de crédito** → informe as parcelas; o sistema calcula o mês de faturamento de cada uma

### 3. Acompanhar o saldo

O dashboard mostra em tempo real:
- Saldo líquido (quem deve quanto)
- Dívidas em aberto detalhadas
- Despesas recentes
- Progresso dos orçamentos mensais

### 4. Registrar pagamentos

Em `/payments/create`, informe o valor pago. O sistema amortiza automaticamente as dívidas mais antigas. Se pagar a mais, o excedente vira crédito.

---

## API REST

Base URL: `http://localhost:8000/api`

Autenticação via Bearer Token (Laravel Sanctum).

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/dashboard` | Saldo, dívidas e despesas recentes |
| POST | `/dashboard/settle` | Liquidar todas as dívidas |
| GET | `/expenses` | Listar despesas |
| POST | `/expenses` | Criar despesa |
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
  "split_ratio": 0.5,
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

## Estrutura do projeto

```
app/
├── Http/Controllers/
│   ├── Api/                    # Controllers da API REST
│   ├── ExpenseController       # CRUD de despesas + filtros
│   ├── PaymentController       # Registro e histórico de pagamentos
│   ├── DashboardController     # Saldo e resumo do casal
│   ├── CardController          # Cartões de crédito/débito
│   ├── GoalController          # Metas de economia
│   ├── BudgetController        # Orçamentos mensais por categoria
│   ├── InstallmentController   # Parcelas em aberto
│   ├── CalendarController      # Calendário financeiro
│   ├── SummaryController       # Resumo mensal com gráficos
│   ├── CalculatorController    # Simulador de compras parceladas
│   ├── NotificationController  # Alertas persistentes
│   └── CoupleController        # Gestão do casal e convites
│
├── Models/
│   ├── Expense / ExpenseSplit / ExpenseInstallment
│   ├── Balance                 # Ledger de débito/crédito
│   ├── Payment / PaymentItem
│   ├── Couple / CoupleInvitation
│   ├── Card / Budget / Goal
│   └── ActivityLog
│
└── Services/
    ├── ExpenseService          # Balances, parcelas e billing date
    ├── PaymentService          # Amortização de dívidas
    └── NotificationService     # Geração e dismissal de alertas

resources/views/
├── dashboard.blade.php
├── expenses/
├── payments/
├── cards/
├── goals/
├── installments/
├── calendar/
├── summary/
├── calculator/
├── notifications/
└── layouts/ + components/
```

---

## Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

---

<div align="center">
  Desenvolvido por <a href="https://github.com/samuzaum">Samuel</a>
</div>
