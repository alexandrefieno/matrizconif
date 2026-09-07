# Arquitetura inicial

## Objetivo

Aplicação PHP/MySQL institucional no cálculo e focada no **Campus Pouso Alegre** na apresentação:

1. **Portal público**: consulta os resultados publicados de Pouso Alegre, percorre as fases e permite simulação temporária não oficial.
2. **Administração**: importa bases de todas as unidades, valida campos, configura parâmetros, calcula cenários e publica a visão do campus.
3. **Memória institucional**: mantém os demais campi como denominador, comparação e trilha de auditoria.

## Ambiente de referência

- XAMPP no Windows;
- Apache 2.4.58;
- PHP 8.2.12;
- MariaDB 10.4.32;
- phpMyAdmin 5.2.1;
- Composer;
- `public/` configurado como raiz do site.

## Organização

- `backend/`: regras de negócio, cálculo, autenticação, importação e persistência.
- `frontend/`: templates de interface não expostos diretamente.
- `public/`: único diretório publicado pelo servidor web; contém o ponto de entrada e ativos.
- `config/`: inicialização e configuração por variáveis de ambiente.
- `database/`: esquema SQL, migrações e dados iniciais. Não existe segunda pasta de banco no back-end.
- `imports/templates/`: contratos de importação e modelos sem dados reais.
- `storage/`: arquivos importados e logs fora do diretório público.
- `tests/`: testes automatizados.

## Fluxo dos dados

1. O administrador cria um período com `ano_base` e `ano_orcamento`.
2. Uma única planilha é recebida por operação, armazenada fora da área pública e identificada por SHA-256.
3. As linhas entram em área temporária.
4. O sistema mostra resumo de abas, colunas, linhas, unidades, registros de Pouso Alegre, erros e alertas.
5. O administrador confere o resumo e decide rejeitar ou confirmar o arquivo.
6. Somente um lote conferido e confirmado pode alimentar a base oficial do período.
7. O motor calcula todas as unidades para formar o denominador institucional.
8. A simulação registra parâmetros e versão do motor.
9. Apenas cenário validado pode ser publicado.
10. A página pública prioriza Pouso Alegre e mantém as demais unidades na memória de cálculo.

## Segurança

- Nenhum dado individual de estudante.
- PDO com consultas preparadas.
- Senhas com `password_hash`.
- Sessões seguras, CSRF e controle por função.
- Credenciais somente em `.env`, nunca no GitHub.
- Upload unitário, extensão, MIME, hash e armazenamento fora de `public/`.
- Sem teto adicional definido pela aplicação; os limites técnicos do PHP são exibidos ao administrador.

## Fonte metodológica

As fórmulas serão implementadas no back-end com versão própria. Parâmetros anuais, hipóteses e exceções permanecem no banco com fonte e justificativa. Planilhas importadas fornecem dados, não código nem fórmulas executáveis.
