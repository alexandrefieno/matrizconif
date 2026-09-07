# Matriz CONIF — Simulador Orçamentário

Aplicação web pública e auditável para importar dados agregados da Plataforma Nilo Peçanha, executar as fases metodológicas da Matriz CONIF e publicar distribuições e cenários por ano-base, com foco no IFSULDEMINAS — Campus Pouso Alegre.

## Princípios

- Nenhuma planilha histórica é considerada fonte confiável automaticamente.
- Todo conjunto importado registra origem, ano-base, responsável, data, hash e estado de validação.
- A importação ocorre com um arquivo por vez, resumo prévio e confirmação administrativa.
- Dados pessoais de estudantes não devem ser importados.
- Resultados publicados e simulações devem ser identificados de forma inequívoca.
- Todas as unidades entram no denominador institucional; Pouso Alegre é a unidade prioritária da interface.

## Ambiente local confirmado

O ambiente de referência usa XAMPP, Apache 2.4.58, PHP 8.2.12, MariaDB 10.4.32, phpMyAdmin 5.2.1 e Composer. Consulte [Instalação local com XAMPP](docs/INSTALL_XAMPP.md).

> Estrutura PHP/MySQL em construção. O ramo atual contém a fundação arquitetural; autenticação, importação e motor de cálculo serão implementados em etapas verificáveis.
