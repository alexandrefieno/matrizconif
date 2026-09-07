# Decisões necessárias antes da implantação

## Infraestrutura

- Versão de PHP disponível no servidor.
- MySQL ou MariaDB e respectiva versão.
- Possibilidade de definir `public/` como raiz do site.
- Limites de upload, memória e tempo de execução.
- Domínio, HTTPS e rotina de backup.

## Acesso

Recomendação inicial:

- um administrador principal;
- possibilidade futura de revisor;
- comunidade sem cadastro;
- simulação pública temporária, sem persistência;
- somente administrador salva e publica.

## Importação

Definir se o sistema aceitará:

1. modelos padronizados publicados pelo próprio sistema; ou
2. arquivos oficiais variáveis, com assistente de mapeamento de colunas.

Recomendação: iniciar com modelos padronizados CSV/XLSX e acrescentar mapeamento assistido na segunda versão.

## Publicação

Cada cenário deve ter um dos estados: `rascunho`, `calculado`, `validado`, `publicado` ou `arquivado`. A interface pública deve exibir ano-base, ano orçamentário, versão do motor, fontes, data e responsável pela validação.
