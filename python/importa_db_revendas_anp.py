import os
import traceback
from pathlib import Path

import pandas as pd
from sqlalchemy import create_engine, text
from dotenv import load_dotenv

env_path = Path(__file__).parent.parent / ".env"

if os.getenv("RUNNING_IN_DOCKER") != "true" and env_path.exists():
    load_dotenv(env_path, override=False)

CSV_URL = (
    "https://www.gov.br/anp/pt-br/centrais-de-conteudo/"
    "dados-abertos/arquivos/arquivos-dados-cadastrais-das-revendas-"
    "de-gas-liquefeito-de-petroleo-glp/cadastro-revendas-glp.csv"
)

DB_HOST = os.getenv("DB_HOST")
DB_PORT = os.getenv("DB_PORT")
DB_NAME = os.getenv("DB_NAME")
DB_USER = os.getenv("DB_USER")
DB_PASSWORD = os.getenv("DB_PASSWORD")

required_vars = [
    "DB_HOST",
    "DB_PORT",
    "DB_NAME",
    "DB_USER",
    "DB_PASSWORD"
]

missing = [
    var for var in required_vars
    if not os.getenv(var)
]

if missing:
    raise ValueError(
        f"Variáveis de ambiente não configuradas: {', '.join(missing)}"
    )


TABELA = "tb_revendas_anp"

COLUNAS_ESPERADAS = [
    "CODIGOISIMP",
    "AUTORIZACAO",
    "DATAPUBLICACAO",
    "RAZAOSOCIAL",
    "CNPJ",
    "ENDERECO",
    "COMPLEMENTO",
    "BAIRRO",
    "CEP",
    "UF",
    "MUNICIPIO",
    "DISTRIBUIDORA",
    "DATAVINCULACAO",
    "CLASSE",
]

url = (
    f"mssql+pyodbc://{DB_USER}:{DB_PASSWORD}"
    f"@{DB_HOST}:{DB_PORT}/{DB_NAME}"
    f"?driver=ODBC+Driver+18+for+SQL+Server&TrustServerCertificate=yes"
)

print("URL:", url.replace(DB_PASSWORD, "***"))

try:
    print("Conectando ao banco...")

    engine = create_engine(url, pool_pre_ping=True)

    with engine.connect() as conn:
        versao = conn.execute(text("SELECT @@VERSION")).scalar()
        print(f"SQL Server conectado: {versao}")

        campos_obrigatorios = conn.execute(
            text("""
                SELECT c.COLUMN_NAME
                FROM INFORMATION_SCHEMA.COLUMNS c
                WHERE c.TABLE_SCHEMA = 'dbo'
                  AND c.TABLE_NAME = :table
                  AND c.IS_NULLABLE = 'NO'
                  AND COLUMNPROPERTY(
                      OBJECT_ID(QUOTENAME(c.TABLE_SCHEMA) + '.' + QUOTENAME(c.TABLE_NAME)),
                      c.COLUMN_NAME,
                      'IsIdentity'
                  ) = 0
            """),
            {"table": TABELA},
        ).scalars().all()

    print("Baixando CSV da ANP...")

    df = pd.read_csv(
        CSV_URL,
        sep=";",
        encoding="utf-8-sig",
        dtype=str,
        on_bad_lines="skip",
    )

    print(f"Registros encontrados no CSV: {len(df)}")

    df.columns = (
        df.columns
        .str.strip()
        .str.upper()
    )

    df = df.apply(
        lambda col: col.str.strip() if col.dtype == "object" else col
    )

    df = df[COLUNAS_ESPERADAS]

    for coluna in ["DATAPUBLICACAO", "DATAVINCULACAO"]:
        df[coluna] = pd.to_datetime(
            df[coluna],
            format="%d/%m/%Y",
            errors="coerce",
        )

    for campo in campos_obrigatorios:
        if campo in df.columns:
            df[campo] = df[campo].replace(r"^\s*$", pd.NA, regex=True)

    antes = len(df)

    campos_obrigatorios_df = [
        campo for campo in campos_obrigatorios
        if campo in df.columns
    ]

    df = df.dropna(subset=campos_obrigatorios_df)

    if "CODIGOISIMP" in df.columns:
        df["CODIGOISIMP"] = pd.to_numeric(
            df["CODIGOISIMP"],
            errors="coerce",
        )

        df = df.dropna(subset=["CODIGOISIMP"])
        df["CODIGOISIMP"] = df["CODIGOISIMP"].astype("int64")

    df = df.where(pd.notnull(df), None)

    depois = len(df)

    print(f"Registros removidos por campos obrigatórios inválidos: {antes - depois}")
    print(f"Registros válidos para carga: {depois}")

    print("Limpando tabela antes da carga...")

    with engine.begin() as conn:
        conn.execute(text(f"TRUNCATE TABLE dbo.{TABELA}"))

    print("Iniciando carga...")

    df.to_sql(
        name=TABELA,
        con=engine,
        schema="dbo",
        if_exists="append",
        index=False,
        chunksize=1000,
    )

    print("Carga concluída com sucesso!")

except Exception:
    print("\nERRO DURANTE A CARGA:\n")
    traceback.print_exc()