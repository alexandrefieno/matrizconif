# Matriz CONIF - Simulador Orcamentario

Aplicacao web publica e auditavel para importar dados agregados da Plataforma Nilo Pecanha, executar as fases metodologicas da Matriz CONIF e publicar distribuicoes e cenarios por ano-base, com foco no IFSULDEMINAS - Campus Pouso Alegre.

## Principios

- Nenhuma planilha historica e considerada fonte confiavel automaticamente.
- Todo conjunto importado registra origem, ano-base, responsavel, data, hash e estado de validacao.
- A importacao ocorre com um arquivo por vez, resumo previo e confirmacao administrativa.
- Dados pessoais de estudantes nao devem ser importados.
- Resultados publicados e simulacoes devem ser identificados de forma inequivoca.
- Todas as unidades entram no denominador institucional; Pouso Alegre e a unidade prioritaria da interface.

## Ambiente local confirmado

O ambiente de referencia usa XAMPP, Apache 2.4.58, PHP 8.2.12, MariaDB 10.4.32, phpMyAdmin 5.2.1 e Composer. Consulte [Instalacao local com XAMPP](docs/INSTALL_XAMPP.md).

## Etapas administrativas disponiveis

1. Acesse `/admin` e entre com o usuario de teste.
2. Acesse `/admin/periods` e cadastre um periodo. Exemplo: ano-base `2024`, orcamento `2026`.
3. Acesse `/admin/imports` e envie uma planilha por vez.
4. Confira o resumo gerado: aba lida, cabecalhos, total de linhas e amostra das primeiras linhas.
5. Abra `Conferir lote`, mapeie as colunas e execute a validacao.
6. Incorpore o lote apenas quando todas as linhas estiverem validas.

## Estado atual

- Autenticacao administrativa criada.
- Alteracao futura de usuario e senha disponivel em `/admin/account`.
- Painel administrativo navegavel criado.
- Cadastro simples de anos-base criado.
- Importacao de planilhas com registro bruto em `import_batches` e `import_rows`.
- Conferencia por lote, mapeamento de colunas, validacao por linha, rejeicao e incorporacao transacional.
- O motor de calculo da Matriz CONIF sera implementado nas proximas etapas.

## Atualizacao da Etapa Administrativa 3

Em uma instalacao que ja utilizava a Etapa 2, execute uma unica vez no phpMyAdmin:

```text
database/migrations/002_stage3_import_validation.sql
```

Lotes antigos que ainda nao haviam sido incorporados retornam ao estado `uploaded` para conferencia efetiva. O botao de incorporacao somente aparece quando todas as linhas estiverem validas.
