<?php
require 'conexao.php';

$curso = $_GET['curso'] ?? '';

$cursosPermitidos = [
    'Desenvolvimento de Sistemas',
    'Ciência de Dados'
];

if (!in_array($curso, $cursosPermitidos, true)) {
    exit('Curso inválido.');
}

$stmt = $pdo->prepare(
    "SELECT id, nome, email
     FROM alunos
     WHERE curso = :curso"
);

$stmt->execute(['curso' => $curso]);

$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($valor)
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Resultado da consulta</title>
</head>
<body>
    <h1>Alunos de <?= h($curso) ?></h1>

    <?php if ($alunos): ?>
        <table border="1" cellpadding="8">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alunos as $aluno): ?>
                    <tr>
                        <td><?= h($aluno['id']) ?></td>
                        <td><?= h($aluno['nome']) ?></td>
                        <td><?= h($aluno['email']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nenhum aluno encontrado para este curso.</p>
    <?php endif; ?>

    <p><a href="index.html">Voltar à pesquisa</a></p>
</body>
</html>