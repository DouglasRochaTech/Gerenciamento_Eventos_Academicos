CREATE DATABASE eventosAcademicos
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE eventosAcademicos;

CREATE TABLE participantes (
CPF VARCHAR(11) PRIMARY KEY,
nome VARCHAR(100) NOT NULL,
email VARCHAR(150) NOT NULL,
tipo VARCHAR(50) NOT NULL
);

CREATE TABLE eventos (
ID INT AUTO_INCREMENT PRIMARY KEY,
nome VARCHAR(100) NOT NULL,
horario DATETIME NOT NULL
);

CREATE TABLE inscricoes
(
    ID INT AUTO_INCREMENT PRIMARY KEY,
	fkParticipante VARCHAR(11) NOT NULL,
    fkEvento INT NOT NULL,
    
    FOREIGN KEY(fkParticipante) REFERENCES participantes (CPF),
    FOREIGN KEY(fkEvento) REFERENCES eventos (ID)
);

INSERT INTO participantes (CPF, nome, email, tipo)
VALUES
('12341234123', 'Joana', 'Joana@gmail.com', 'Professor'),
('43214321321', 'João', 'Joao@hotmail.com', 'Estudante'),
('14142323423', 'José', 'Jose@gmail.com', 'Estudante');

INSERT INTO eventos (nome, horario)
VALUES
('Oficina_BD', '2026-10-10 8:00:00'),
('Palestra_IA', '2026-10-10 12:00:00'),
('Palestra_Marketing', '2026-10-10 14:00:00'),
('Oficina_Python', '2026-10-10 16:00:00');

INSERT INTO  inscricoes (fkParticipante, fkEvento)
VALUES
('12341234123', 1),
('43214321321', 2),
('14142323423', 3),
('12341234123', 4);