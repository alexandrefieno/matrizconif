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
5. Verifique se o lote aparece em "Ultimos lotes importados".

## Estado atual

- Autenticacao administrativa criada.
- Alteracao futura de usuario e senha disponivel em `/admin/account`.
- Painel administrativo navegavel criado.
- Cadastro simples de anos-base criado.
- Importacao inicial de planilhas criada, com registro em `import_batches` e `import_rows`.
- Motor de classificacao, validacao metodologica e incorporacao nas tabelas finais ainda serao implementados nas proximas etapas.
