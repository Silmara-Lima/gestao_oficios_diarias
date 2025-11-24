-- Criar o banco (caso ainda não exista)
-- Execute fora do psql se necessário:
-- CREATE DATABASE gestor_oficios_diarias;

-- ===============================
-- TABELA DE USUÁRIOS (LOGIN)
-- ===============================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,   -- armazenada com password_hash()
    nome VARCHAR(100)
);

-- Usuário inicial (senha: admin → será criptografada depois)
INSERT INTO users (username, senha, nome)
VALUES ('admin', '$2y$10$e0NR4ddLY8ZVbkP4j6QfQO8VboIpXS/4h05D2qE0HR47ecJxW1dIa', 'Administrador');
-- A senha acima é "admin" já com password_hash()

-- ===============================
-- FUNCIONÁRIOS
-- ===============================
CREATE TABLE funcionarios (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    matricula VARCHAR(10) UNIQUE,
    cargo VARCHAR(100), -- Novo campo para o cargo
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- ===============================
-- CONFIGURAÇÕES (CABECALHO/RODAPE)
-- ===============================
CREATE TABLE configuracoes (
    id SERIAL PRIMARY KEY,
    cabecalho TEXT,
    rodape TEXT
);

INSERT INTO configuracoes (cabecalho, rodape)
VALUES ('', '');

-- ===============================
-- OFÍCIOS
-- ===============================
CREATE TABLE oficios (    
    id SERIAL PRIMARY KEY,    
    
    ano INT NOT NULL,
    numero_sequencial INT NOT NULL,
    numero_completo VARCHAR(15) UNIQUE,   
    
    assunto VARCHAR(255) NOT NULL,
    destinatario VARCHAR(100) NOT NULL,
    corpo TEXT,    
    
    pronome_tratamento VARCHAR(50), 
    saudacao VARCHAR(50),    
    
    data_emissao DATE NOT NULL DEFAULT CURRENT_DATE,    
    
    funcionario_id INT NOT NULL,    
    
    criado_por_user_id INT,     
    
    UNIQUE (ano, numero_sequencial),    
    
    CONSTRAINT fk_funcionario
        FOREIGN KEY (funcionario_id) 
        REFERENCES funcionarios (id) 
        ON DELETE RESTRICT
);

-- ===============================
-- DIÁRIAS
-- ===============================
CREATE TABLE diarias (
    id SERIAL PRIMARY KEY,
    numero INTEGER NOT NULL,
    funcionario_id INTEGER NOT NULL REFERENCES funcionarios(id) ON DELETE CASCADE,
    destino VARCHAR(150),
    data_inicio DATE,
    data_fim DATE,
    objetivo TEXT
);

CREATE UNIQUE INDEX idx_diarias_numero ON diarias(numero);

-- ===============================
-- MUNICÍPIOS (carregado pelo municipios_pb.sql)
-- ===============================
CREATE TABLE municipios (
    id SERIAL PRIMARY KEY,
    ibge INTEGER NOT NULL,
    ibge_2 INTEGER NOT NULL,
    municipio VARCHAR(120) NOT NULL,
    pop INTEGER,
    regiao INTEGER,
    grs INTEGER,
    macro INTEGER
);
