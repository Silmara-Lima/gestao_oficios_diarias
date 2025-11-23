# Projeto de Gestão de Ofícios e Diárias

- Crie uma pasta chamada 'gestao_oficios_diarias' dentro da pasta htdocs do xampp

# Em seguida no postgre crie o usuário admin e a tabela

`CREATE USER admin_user WITH PASSWORD 'Digiteumasenha';`

- Para criar a tabela e o admin ser o administrador dela

```
CREATE DATABASE gestao_oficios_diarias
OWNER admin_user;
```

# Rodar o schema e municipios_pb

`psql -U postgres -d gestao_oficios_diarias -p 5434 -f sql/schema_postgres.sql`
`psql -U postgres -d gestao_oficios_diarias -p 5434 -f sql/municipios_pb.sql`

# Gerar o arquivo db.php

- Esse arquivo terá os paramêtros de conexão do banco

# Para rodar o banco

`psql -U postgres -h localhost -p 5434`

# Para verificar conexão

`netstat -ano | findstr 5434 `

# Para conectar ao banco

`\c gestao_oficios_diarias`

# Login

- Foi criado o arquivo auth para autenticar os usuários/verificar se o usuário está logado e o logout para sair do sistema

# Dashboard

- É a "home" para o usuário visualizar as opções de acessar ofícios, acessar diárias, acessar funcionários e configurações

# CRUD de funcionários

- O cadastro e edição de funcionários poderá ser feita nessa sessão, digitando nome e matrícula.
- Há validações AJAX para evitar registros inválidos.

# CRUD de ofícios

- O número de ofícios é sequencial
- O usuário pode escolher a data de emissão
- Há um select de funcionários, mas também pode adicionar um novo caso o funcionário não esteja na lista.
- Ao adicionar o funcionário, o usuário conseguirá visualizar esse registro na parte de funcionários
- Há uma parte de pré-visualização para que o usuário confira os dados registrados do ofício
- Ao final da página estará lista os ofícios, no qual o usuário poderá ver, editar os excluir.
- Caso o ofício seja excluído, o número sequencial retorna para o número excluído.

# CRUD de diárias

- O número de ofícios é sequencial
- O usuário deve preencher a data de inicio e fim da solicitação
- Há um select de municípios, para que seja informado o destino da viagem
- Há um select de funcionários, mas também pode adicionar um novo caso o funcionário não esteja na lista.
- Ao adicionar o funcionário, o usuário conseguirá visualizar esse registro na parte de funcionários
- Há uma parte de pré-visualização para que o usuário confira os dados registrados do ofício
- Ao final da página estará lista os ofícios, no qual o usuário poderá ver, editar os excluir.
- Caso o ofício seja excluído, o número sequencial retorna para o número excluído.

