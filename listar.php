<?php
foreach ($tabelasPermitidas as $nomeTabela => $configuracao) {
	$registros[$nomeTabela] = $pdo
		->query($configuracao['consulta'])
		->fetchAll();
}
