# CleriVerse

Aplicação com várias ferramentas para cálculos Astronómicos, desenhada para correr num servidor cPanel com PHP e MariaDB.

## Ferramentas disponíveis

| Ferramenta | Descrição |
|---|---|
| ☿ Fases de Mercúrio | Visualização das fases do planeta Mercúrio para qualquer mês |

## Tecnologias

- **PHP ≥ 7.4** (compatível com cPanel)
- **MariaDB / MySQL** (opcional – a aplicação funciona sem BD)
- **Composer** para gestão de dependências
- **[andrmoel/astronomy-bundle](https://github.com/andrmoel/astronomy-bundle)** – cálculos VSOP87 (algoritmos de Jean Meeus)

## Instalação (servidor cPanel)

### 1. Fazer upload dos ficheiros

Carregue todos os ficheiros (exceto `/vendor/`) para a pasta pública do seu domínio (normalmente `public_html/` ou um subdomínio).

### 2. Instalar dependências via Composer

Aceda ao **Terminal** do cPanel (ou SSH) e execute:

```bash
cd ~/public_html   # ou o caminho onde carregou os ficheiros
composer install --ignore-platform-req=php --no-dev --optimize-autoloader
```

> **Nota:** O `--ignore-platform-req=php` é necessário porque a biblioteca astronómica declara compatibilidade até PHP 7, mas funciona corretamente com PHP 8.

### 3. Criar a base de dados (opcional)

Para activar a cache de cálculos, crie uma base de dados MariaDB no cPanel e execute o schema:

```bash
mysql -u SEU_UTILIZADOR -p NOME_DA_BD < database/schema.sql
```

Configure as credenciais no ficheiro `config/database.php` ou via variáveis de ambiente:

```
DB_HOST=localhost
DB_NAME=cleriverse
DB_USER=cpanel_user
DB_PASS=password_segura
```

### 4. Verificar .htaccess

O ficheiro `.htaccess` já está configurado com:
- Reescrita de URLs para `index.php`
- Bloqueio de acesso a ficheiros sensíveis (`vendor/`, `config/`, SQL, etc.)
- Cabeçalhos de segurança
- Compressão gzip e cache de assets

## Desenvolvimento local

```bash
# Instalar dependências
composer install --ignore-platform-req=php

# Iniciar servidor de desenvolvimento
php -S localhost:8080

# Aceder em http://localhost:8080
```

## Estrutura do projeto

```
CleriVerse/
├── assets/
│   ├── css/style.css          # Estilos (tema espaço sideral)
│   └── js/mercury.js          # Interactividade do calendário
├── config/
│   └── database.php           # Configuração MariaDB
├── database/
│   └── schema.sql             # Schema MariaDB
├── pages/
│   └── mercury.php            # Ferramenta de fases de Mercúrio
├── src/
│   └── Astronomy/
│       └── MercuryCalculator.php  # Cálculos VSOP87
├── templates/
│   ├── header.php
│   └── footer.php
├── vendor/                    # Gerido pelo Composer (não incluído no git)
├── .htaccess                  # Configuração Apache/cPanel
├── composer.json
└── index.php                  # Router principal
```

## Créditos

- Algoritmos astronómicos: Jean Meeus, *Astronomical Algorithms*, 2ª ed.
- Tabelas VSOP87: Bretagnon & Francou (1988)
