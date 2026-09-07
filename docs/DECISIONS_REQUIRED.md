# Registro de decisões de implantação

## Decisões confirmadas

- Ambiente local: XAMPP no Windows.
- Apache: 2.4.58.
- PHP: 8.2.12.
- Banco: MariaDB 10.4.32, administrado pelo phpMyAdmin 5.2.1.
- Dependências: Composer autorizado.
- Raiz web: `public/`.
- Importação: uma planilha por operação.
- Limite: nenhum teto adicional na aplicação; prevalecem e são exibidos os limites do PHP.
- Incorporação: somente depois da leitura, apresentação do resumo e confirmação administrativa.
- Foco público: Campus Pouso Alegre; demais unidades compõem denominador e memória de cálculo.

## Decisões pendentes para produção

- domínio público e certificado HTTPS;
- credenciais próprias do banco, sem uso do usuário `root`;
- rotina e retenção de backups;
- limites definitivos de memória e tempo de execução do servidor;
- identidade do administrador inicial e eventual função de revisor.

## Formatos de importação

A primeira versão aceitará CSV, XLSX e XLS, um arquivo de cada vez. Ainda deverá ser definido, por tipo de fonte, se o arquivo seguirá:

1. modelo padronizado fornecido pelo sistema; ou
2. arquivo variável com assistente de mapeamento de colunas.

Nenhum arquivo será presumido correto apenas por ter sido importado.

## Publicação

Cada cenário terá um dos estados: `rascunho`, `calculado`, `validado`, `publicado` ou `arquivado`. A interface pública exibirá ano-base, ano orçamentário, versão do motor, fontes, data e responsável pela validação.
