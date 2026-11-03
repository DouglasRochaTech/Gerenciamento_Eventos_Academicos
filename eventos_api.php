<?php
// Todas as respostas deste arquivo são JSON, inclusive as mensagens de erro.
header('Content-Type: application/json; charset=utf-8');

// Envia uma resposta JSON e encerra o processamento da requisição.
function responderJson($status, $dados)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit;
}

// A lista fixa impede que nomes de tabela ou coluna enviados pelo navegador
// sejam usados diretamente como identificadores SQL.
$tabelasPermitidas = [
    'eventos' => [
        'chavePrimaria' => 'ID',
        'camposEditaveis' => ['nome', 'horario'],
        'consulta' => 'SELECT ID, nome, horario FROM eventos'
    ],
    'participantes' => [
        'chavePrimaria' => 'CPF',
        'camposEditaveis' => ['nome', 'email', 'tipo'],
        'consulta' => 'SELECT CPF, nome, email, tipo FROM participantes'
    ],
    'inscricoes' => [
        'chavePrimaria' => 'ID',
        'camposEditaveis' => ['fkParticipante', 'fkEvento'],
        'consulta' => 'SELECT ID, fkParticipante, fkEvento FROM inscricoes'
    ]
];

try {
    // O endpoint usa uma conexão separada para não interferir em buscar_aluno.php.
    require 'conexao_eventos.php';

    // A leitura inicial retorna os registros das três tabelas em uma só requisição.
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $resultado = [];

        foreach ($tabelasPermitidas as $nomeTabela => $configuracao) {
            $resultado[$nomeTabela] = $pdo
                ->query($configuracao['consulta'])
                ->fetchAll();
        }

        responderJson(200, $resultado);
    }

    // Alterações são aceitas somente por POST com corpo JSON.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: GET, POST');
        responderJson(405, ['erro' => 'Método não permitido.']);
    }

    // A validação verifica que o corpo recebido seja um objeto JSON válido.
    $corpo = json_decode(file_get_contents('php://input'), true);

    if (!is_array($corpo) || json_last_error() !== JSON_ERROR_NONE) {
        responderJson(400, ['erro' => 'A requisição deve conter um objeto JSON válido.']);
    }

    // Somente os nomes de tabela previamente definidos são aceitos.
    $nomeTabela = $corpo['tabela'] ?? '';
    if (!is_string($nomeTabela) || !isset($tabelasPermitidas[$nomeTabela])) {
        responderJson(400, ['erro' => 'Tabela inválida.']);
    }

    $configuracao = $tabelasPermitidas[$nomeTabela];
    $chavePrimaria = $configuracao['chavePrimaria'];
    $chave = $corpo['chave'] ?? null;

    // A chave localiza o registro, mas nunca é tratada como campo editável.
    if (!is_scalar($chave) || (string) $chave === '') {
        responderJson(400, ['erro' => 'A chave do registro é obrigatória.']);
    }

    // A existência é verificada antes para evitar respostas de sucesso sem alteração.
    $consultaExistencia = $pdo->prepare(
        "SELECT 1 FROM $nomeTabela WHERE $chavePrimaria = :chave"
    );
    $consultaExistencia->execute(['chave' => $chave]);

    if ($consultaExistencia->fetchColumn() === false) {
        responderJson(404, ['erro' => 'O registro solicitado não foi encontrado.']);
    }

    $acao = $corpo['acao'] ?? '';

    if ($acao === 'atualizar') {
        // A chave primária e campos não previstos não podem ser alterados.
        $campo = $corpo['campo'] ?? '';
        if (!is_string($campo) || !in_array($campo, $configuracao['camposEditaveis'], true)) {
            responderJson(400, ['erro' => 'Campo inválido ou não editável.']);
        }

        // Valores vazios ou não escalares são rejeitados antes de chegar ao banco.
        $valor = $corpo['valor'] ?? null;
        if (!is_scalar($valor) || (string) $valor === '') {
            responderJson(400, ['erro' => 'O valor do campo é obrigatório.']);
        }

        // Os nomes usados na consulta são seguros por terem sido validados pela lista fixa.
        $atualizacao = $pdo->prepare(
            "UPDATE $nomeTabela SET $campo = :valor WHERE $chavePrimaria = :chave"
        );
        $atualizacao->execute([
            'valor' => $valor,
            'chave' => $chave
        ]);

        responderJson(200, ['mensagem' => 'Registro atualizado com sucesso.']);
    }

    if ($acao === 'excluir') {
        // A exclusão atinge somente a linha identificada pela chave primária.
        $exclusao = $pdo->prepare(
            "DELETE FROM $nomeTabela WHERE $chavePrimaria = :chave"
        );
        $exclusao->execute(['chave' => $chave]);

        responderJson(200, ['mensagem' => 'Registro excluído com sucesso.']);
    }

    // Ações desconhecidas não executam nenhuma consulta de alteração.
    responderJson(400, ['erro' => 'Ação inválida.']);
} catch (PDOException $erro) {
    // Os detalhes técnicos vão para o log do servidor, não para o navegador.
    error_log($erro->getMessage());

    if ($erro->getCode() === '23000') {
        responderJson(409, [
            'erro' => 'A operação viola uma relação entre registros. Verifique as inscrições vinculadas e os valores informados.'
        ]);
    }

    responderJson(500, ['erro' => 'Não foi possível acessar o banco de dados.']);
}
