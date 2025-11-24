# Projeto de Gestão de Ofícios e Diárias

- Crie uma pasta chamada 'gestao_oficios_diarias' dentro da pasta htdocs do xampp

# Em seguida no postgres crie o usuário admin e a tabela
`CREATE USER admin_user WITH PASSWORD 'Digiteumasenha';`

- Para criar a tabela e o admin ser o administrador dela

```
CREATE DATABASE gestao_oficios_diarias
OWNER admin_user;
```

# Rodar o schema e municipios_pb
`psql -U usuario -d gestao_oficios_diarias -p sua_porta -f sql/schema_postgres.sql`
`psql -U usuario -d gestao_oficios_diarias -p sua_porta -f sql/municipios_pb.sql`

# Gerar o arquivo db.php

- Esse arquivo terá os paramêtros de conexão do banco

# Para rodar o banco 
`psql -U usuario -h seu_host -p sua_porta`

# Para verificar conexão
`netstat -ano | findstr sua_porta `

# Para conectar ao banco

`\c gestao_oficios_diarias`

# Login

- Foi criado o arquivo auth para autenticar os usuários/verificar se o usuário está logado e o logout para sair do sistema

# Dashboard
- É a "home" para o usuário visualizar as opções de acessar ofícios, acessar diárias, acessar funcionários e configurações
