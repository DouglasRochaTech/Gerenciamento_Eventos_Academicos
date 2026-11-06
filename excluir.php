<?php
$nomeTabela = $_POST['tabela'] ?? '';
if (!is_string($nomeTabela) || !isset($tabelasPermitidas[$nomeTabela])) {
	throw new InvalidArgumentException('Tabela inválida.');
}

$chave = $_POST['chave'] ?? '';
if (!is_string($chave) || $chave === '') {
	throw new InvalidArgumentException('A chave do registro é obrigatória.');
}

$chavePrimaria = $tabelasPermitidas[$nomeTabela]['chavePrimaria'];
$consulta = $pdo->prepare("DELETE FROM $nomeTabela WHERE $chavePrimaria = :chave");
$consulta->execute(['chave' => $chave]);

header('Location: index.php?resultado=excluido');
exit;
