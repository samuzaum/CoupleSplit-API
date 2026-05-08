# Como rodar o CoupleSplit no seu computador

Siga os passos abaixo. Você **não precisa saber programar** para isso.

---

## Requisitos

- Windows 10 ou 11
- Conexão com a internet (só na primeira vez)

---

## Passo 1 — Baixe o projeto

Se você recebeu o projeto como um arquivo `.zip`, extraia ele em alguma pasta do seu computador (ex: `C:\CoupleSplit`).

Se for pelo GitHub, clique em **Code → Download ZIP** e extraia.

---

## Passo 2 — Rode o instalador

Dentro da pasta `setup`, dê **dois cliques** no arquivo `instalar.bat`.

O que ele faz automaticamente:
1. Verifica se o PHP está instalado — se não estiver, **baixa e instala o Laragon** (ambiente necessário para rodar o sistema)
2. Instala todas as dependências do projeto
3. Configura o banco de dados
4. Sobe o servidor

> ⚠️ **Na primeira vez**, se o Laragon for instalado, você precisará **fechar e abrir o `instalar.bat` novamente** após a instalação do Laragon.

---

## Passo 3 — Acesse o sistema

Após o instalador terminar, abra seu navegador e acesse:

```
http://localhost:8000
```

Crie sua conta e comece a usar!

---

## Para rodar nas próximas vezes

Basta abrir o `instalar.bat` novamente — ele vai pular as etapas já feitas e subir o servidor direto.

---

## Problemas comuns

**"Banco de dados não encontrado"**
→ Abra o Laragon (ícone na bandeja do sistema) e certifique-se de que o MySQL está rodando (botão verde).

**A página não abre no navegador**
→ Verifique se o terminal do `instalar.bat` ainda está aberto. Ele precisa ficar aberto enquanto você usa o sistema.

**Erro na instalação do Laragon**
→ Baixe manualmente em [laragon.org](https://laragon.org/download) e instale. Depois rode o `instalar.bat` novamente.
