<?php
$nomeTabela = $_POST['tabela'] ?? '';
if (!is_string($nomeTabela) || !isset($tabelasPermitidas[$nomeTabela])) {
	throw new InvalidArgumentException('Tabela inválida.');
}

$configuracao = $tabelasPermitidas[$nomeTabela];
$chave = $_POST['chave'] ?? '';
if (!is_string($chave) || $chave === '') {
	throw new InvalidArgumentException('A chave do registro é obrigatória.');
}

$campo = $_POST['campo'] ?? '';
if (
	!is_string($campo)
	|| !isset($configuracao['colunas'][$campo])
	|| empty($configuracao['colunas'][$campo]['editavel'])
) {
	throw new InvalidArgumentException('Campo inválido ou não editável.');
}

$valor = $_POST['valor'] ?? '';
if (!is_string($valor) || trim($valor) === '') {
	throw new InvalidArgumentException('O valor do campo é obrigatório.');
}

if ($campo === 'horario') {
	$valor = str_replace('T', ' ', $valor);
	if (strlen($valor) === 16) {
		$valor .= ':00';
	}
}

$chavePrimaria = $configuracao['chavePrimaria'];
$consulta = $pdo->prepare(
	"UPDATE $nomeTabela SET $campo = :valor WHERE $chavePrimaria = :chave"
);
$consulta->execute(['valor' => $valor, 'chave' => $chave]);

header('Location: index.php?resultado=atualizado');
exit;
