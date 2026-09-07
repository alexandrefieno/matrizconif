# Instalação local com XAMPP

## Ambiente de referência

- Apache 2.4.58;
- PHP 8.2.12;
- MariaDB 10.4.32;
- phpMyAdmin 5.2.1;
- Composer;
- Windows 64 bits.

O phpMyAdmin 5.2.1 é suficiente para o desenvolvimento. A atualização para 5.2.3 não é requisito do simulador.

## 1. Obter o projeto

Clone ou extraia o repositório em:

```text
C:\xampp\htdocs\matrizconif
```

Use o ramo de desenvolvimento indicado no projeto enquanto a versão ainda não estiver incorporada ao ramo principal.

## 2. Instalar as dependências

Abra um terminal na pasta do projeto e execute:

```powershell
composer install
```

Confirme que o PDO para MySQL está habilitado:

```powershell
php -m | findstr /I "PDO pdo_mysql"
```

O resultado deve incluir `PDO` e `pdo_mysql`.

## 3. Criar a configuração local

Copie `.env.xampp.example` para `.env`. No XAMPP padrão, o acesso local costuma usar o usuário `root` sem senha. Se o seu MariaDB tiver senha, preencha `DB_PASSWORD`.

Nunca envie o arquivo `.env` para o GitHub.

## 4. Criar o banco

1. Acesse `http://localhost/phpmyadmin/`.
2. Abra a aba **Importar**.
3. Selecione `database/schema.sql`.
4. Execute a importação.

O próprio script cria o banco `matrizconif` com `utf8mb4`.

## 5. Definir `public/` como raiz

Edite:

```text
C:\xampp\apache\conf\extra\httpd-vhosts.conf
```

Acrescente:

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "C:/xampp/htdocs"

    <Directory "C:/xampp/htdocs">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost *:80>
    ServerName matrizconif.local
    DocumentRoot "C:/xampp/htdocs/matrizconif/public"

    <Directory "C:/xampp/htdocs/matrizconif/public">
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Confirme em `C:\xampp\apache\conf\httpd.conf` que estas linhas estão habilitadas, sem `#` no início:

```apache
LoadModule rewrite_module modules/mod_rewrite.so
Include conf/extra/httpd-vhosts.conf
```

Como administrador, edite:

```text
C:\Windows\System32\drivers\etc\hosts
```

Acrescente:

```text
127.0.0.1 matrizconif.local
```

Reinicie o Apache e acesse `http://matrizconif.local/`.

### Teste provisório

Antes de configurar o VirtualHost, a página pode ser aberta em:

```text
http://localhost/matrizconif/public/
```

O VirtualHost continua sendo a configuração recomendada porque impede acesso web direto a `config/`, `database/`, `storage/` e ao código interno.

## 6. Uploads

A aplicação receberá uma planilha por operação. Não haverá teto adicional fixado pelo simulador; valerão os limites efetivos do PHP, especialmente:

- `upload_max_filesize`;
- `post_max_size`;
- `memory_limit`;
- `max_execution_time`.

O painel administrativo exibirá esses limites. Cada arquivo passará por três etapas: recebimento, conferência com resumo e incorporação confirmada à base. Nenhum arquivo será incorporado automaticamente.
