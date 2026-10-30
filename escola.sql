CREATE DATABASE escola
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE escola;

CREATE TABLE alunos (
id INT AUTO_INCREMENT PRIMARY KEY,
nome VARCHAR(100) NOT NULL,
email VARCHAR(150) NOT NULL,
curso VARCHAR(100) NOT NULL
);

INSERT INTO alunos (nome, email, curso)
VALUES
('Ana Souza', 'ana@exemplo.com', 'Desenvolvimento de Sistemas'),
('Bruno Lima', 'bruno@exemplo.com', 'Ciência de Dados'),
('Carla Alves', 'carla@exemplo.com', 'Desenvolvimento de Sistemas');