# Revendas ANP

Sistema Full Stack para consulta de revendas de GLP (Gás Liquefeito de Petróleo) cadastradas na Agência Nacional do Petróleo (ANP), utilizando dados públicos disponibilizados pela própria agência.

---

## Tecnologias Utilizadas

### Backend

- PHP 8
- SQL Server (Azure SQL Edge)
- Docker
- Docker Compose

### ETL (Carga de Dados)

- Python 3.11
- Pandas
- SQLAlchemy
- PyODBC

### Banco de Dados

- Microsoft SQL Server
- Scripts SQL para criação automática do banco e tabelas

---

## Arquitetura

```text
Dados Abertos ANP
        │
        ▼
Python ETL
(importa_db_revendas_anp.py)
        │
        ▼
SQL Server
        │
        ▼
PHP API
(revendas_anp_selecionar.php)
        │
        ▼
Interface Web
(index.php)
```

### Fluxo da Aplicação

1. O SQL Server é iniciado.
2. O script SQL cria o banco e as tabelas.
3. O container Python realiza a carga dos dados da ANP.
4. Os dados são armazenados no SQL Server.
5. A API PHP disponibiliza os dados em JSON.
6. O `index.php` consome a API e exibe os dados para o usuário.

---

## Estrutura do Projeto

```text
revendas-anp/
├── database/
│   └── tb_revendas_anp.sql
│
├── php/
│   ├── index.php
│   └── revendas_anp_selecionar.php
│
├── python/
│   ├── importa_db_revendas_anp.py
│   ├── requirements.txt
│   └── Dockerfile
│
├── .env
├── docker-compose.yml
├── Dockerfile
└── README.md
```

---

## Fonte dos Dados

Os dados são obtidos diretamente do portal de Dados Abertos da ANP.

**Portal:**

https://www.gov.br/anp/pt-br/centrais-de-conteudo/dados-abertos

**Arquivo utilizado:**

- Cadastro de Revendas de GLP
---

## Configuração

### Arquivo `.env`

```env
MSSQL_SA_PASSWORD=RevendasPassword!123
MSSQL_DATABASE=REVENDAS

DB_HOST=localhost
DB_PORT=1433
DB_NAME=REVENDAS
DB_USER=sa
DB_PASSWORD=RevendasPassword!123
---

## Executando o Projeto

### Construir e iniciar os containers

```bash
docker compose up -d --build
```

### Verificar status

```bash
docker ps
```

### Ver logs

```bash
docker logs revendas-mssql
docker logs revendas-db-init
docker logs revendas-carga-anp
docker logs revendas-php
```
---

## Containers

### revendas-mssql

Responsável pelo armazenamento dos dados.

**Funções:**

- Hospeda o banco `REVENDAS`
- Armazena as revendas da ANP

### revendas-db-init

Executa o script SQL de criação da estrutura do banco.

**Funções:**

- Criação do banco `REVENDAS`
- Criação da tabela `tb_revendas_anp`

### revendas-carga-anp

Executa o processo ETL.

**Funções:**

- Download do CSV oficial da ANP
- Tratamento dos dados
- Validação dos registros
- Inserção dos dados no SQL Server

### revendas-php

Servidor web da aplicação.

**Funções:**

- Disponibilizar API JSON
- Servir a interface web
---

## Interface Web

Arquivo principal:

```text
php/index.php
```

### Acesso

```text
http://localhost:8080
```

### Funcionalidades

- Visualização das revendas
- Busca textual
- Ordenação de colunas
- Paginação automática
- Integração com DataTables
- Consumo dos dados via AJAX
---

## API

### Endpoint JSON

Arquivo:

```text
php/revendas_anp_selecionar.php
```

URL:

```text
http://localhost:8080/revendas_anp_selecionar.php
```

### Exemplo de Retorno

```json
[
  {
    "CODIGOISIMP": 1158082,
    "AUTORIZACAO": "GLP/RJ0183656",
    "RAZAOSOCIAL": "A A DA SILVA REVENDEDORA DE GÁS ME",
    "UF": "RJ",
    "MUNICIPIO": "MESQUITA"
  }
]
```
---

## Acesso ao Banco

### Entrar no SQL Server

```bash docker exec -it revendas-mssql /opt/mssql-tools/bin/sqlcmd \ -S localhost \ -U sa \ -P RevendasPassword!123 \ -C
```

### Selecionar banco

```sql
USE REVENDAS;
GO
```
---

## Consultas Úteis

### Quantidade de registros

```sql
SELECT COUNT(*)
FROM dbo.tb_revendas_anp;
GO
```

### Visualizar registros

```sql
SELECT TOP 10 *
FROM dbo.tb_revendas_anp;
GO
```

### Pesquisar por município

```sql
SELECT *
FROM dbo.tb_revendas_anp
WHERE MUNICIPIO = 'BELO HORIZONTE';
GO
```

### Pesquisar por UF

```sql
SELECT *
FROM dbo.tb_revendas_anp
WHERE UF = 'MG';
GO
```
---

## Atualização dos Dados

Para recarregar os dados da ANP:

```bash
docker compose down -v
docker compose up -d --build
```

O processo executará novamente:

1. Criação do banco
2. Criação da tabela
3. Download do CSV atualizado
4. Importação dos dados
---

## Melhorias Futuras

- Dashboard com estatísticas
- Atualização automática periódica dos dados
- Cache de consultas
- Testes automatizados

