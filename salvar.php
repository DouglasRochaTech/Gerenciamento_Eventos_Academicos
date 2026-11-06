<?php
// O index.php fornece a conexão e a lista de tabelas permitidas.
$nomeTabela = $_POST['tabela'] ?? '';
if (!is_string($nomeTabela) || !isset($tabelasPermitidas[$nomeTabela])) {
	throw new InvalidArgumentException('Tabela inválida.');
}

if ($nomeTabela === 'eventos') {
	$nome = $_POST['nome'] ?? '';
	$horario = $_POST['horario'] ?? '';
	if (!is_string($nome) || !is_string($horario)) {
		throw new InvalidArgumentException('Informe dados válidos para o evento.');
	}
	$nome = trim($nome);
	if ($nome === '' || $horario === '') {
		throw new InvalidArgumentException('Informe o nome e o horário do evento.');
	}
	$horario = str_replace('T', ' ', $horario);
	if (strlen($horario) === 16) {
		$horario .= ':00';
	}
	$consulta = $pdo->prepare('INSERT INTO eventos (nome, horario) VALUES (:nome, :horario)');
	$consulta->execute(['nome' => $nome, 'horario' => $horario]);
} elseif ($nomeTabela === 'participantes') {
	$cpf = $_POST['CPF'] ?? '';
	$nome = $_POST['nome'] ?? '';
	$email = $_POST['email'] ?? '';
	$tipo = $_POST['tipo'] ?? '';
	if (!is_string($cpf) || !is_string($nome) || !is_string($email) || !is_string($tipo)) {
		throw new InvalidArgumentException('Informe dados válidos para o participante.');
	}
	$cpf = trim($cpf);
	$nome = trim($nome);
	$email = trim($email);
	$tipo = trim($tipo);
	if (!preg_match('/^[0-9]{11}$/', $cpf)) {
		throw new InvalidArgumentException('O CPF deve ter exatamente 11 números.');
	}
	if ($nome === '' || $email === '' || $tipo === '') {
		throw new InvalidArgumentException('Preencha todos os dados do participante.');
	}
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		throw new InvalidArgumentException('Informe um e-mail válido.');
	}
	$consulta = $pdo->prepare(
		'INSERT INTO participantes (CPF, nome, email, tipo) VALUES (:cpf, :nome, :email, :tipo)'
	);
	$consulta->execute(['cpf' => $cpf, 'nome' => $nome, 'email' => $email, 'tipo' => $tipo]);
} else {
	$cpf = $_POST['fkParticipante'] ?? '';
	$idEvento = $_POST['fkEvento'] ?? '';
	if (
		!is_string($cpf)
		|| !is_string($idEvento)
		|| !preg_match('/^[0-9]{11}$/', $cpf)
		|| !ctype_digit($idEvento)
		|| (int) $idEvento < 1
	) {
		throw new InvalidArgumentException('Selecione o participante e o evento.');
	}
	$consulta = $pdo->prepare(
		'INSERT INTO inscricoes (fkParticipante, fkEvento) VALUES (:cpf, :idEvento)'
	);
	$consulta->execute(['cpf' => $cpf, 'idEvento' => $idEvento]);
}

header('Location: index.php?resultado=cadastrado');
exit;
