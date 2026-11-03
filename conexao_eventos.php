<?php
// Esta conexão é exclusiva do banco de eventos e não altera a conexão da busca de alunos.
$host = 'localhost';
$porta = 3307;
$banco = 'eventosAcademicos';
$usuario = 'root';
$senha = '';

// O charset garante a leitura correta de nomes e outros textos em português.
$dsn = "mysql:host=$host;port=$porta;dbname=$banco;charset=utf8mb4";

// Exceções permitem que a página PHP apresente erros de acesso ao banco.
$pdo = new PDO($dsn, $usuario, $senha, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);
