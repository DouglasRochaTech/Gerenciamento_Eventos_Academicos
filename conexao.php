<?php
$host = 'localhost';
$banco = 'escola';
$usuario = 'root';
$senha = '';

$dsn = "mysql:host=$host;port=3307;dbname=$banco;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $usuario, $senha);

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    echo "Conexão realizada com sucesso!";
} catch (PDOException $e) {
    error_log($e->getMessage());
    exit("Não foi possível conectar ao banco de dados.");
}
?>