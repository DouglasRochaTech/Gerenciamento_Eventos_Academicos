<?php
// A conexão é carregada neste bloco para que falhas possam ser mostradas na página.
$mensagem = '';
$registros = [
    'eventos' => [],
    'participantes' => [],
    'inscricoes' => []
];

// Esta lista define as tabelas, chaves e campos que podem ser usados nas consultas.
$tabelasPermitidas = [
    'eventos' => [
        'chavePrimaria' => 'ID',
        'colunas' => [
            'ID' => ['titulo' => 'ID'],
            'nome' => ['titulo' => 'Nome', 'editavel' => true, 'tipo' => 'text'],
            'horario' => ['titulo' => 'Horário', 'editavel' => true, 'tipo' => 'datetime-local']
        ],
        'consulta' => 'SELECT ID, nome, horario FROM eventos ORDER BY ID'
    ],
    'participantes' => [
        'chavePrimaria' => 'CPF',
        'colunas' => [
            'CPF' => ['titulo' => 'CPF'],
            'nome' => ['titulo' => 'Nome', 'editavel' => true, 'tipo' => 'text'],
            'email' => ['titulo' => 'E-mail', 'editavel' => true, 'tipo' => 'email'],
            'tipo' => ['titulo' => 'Tipo', 'editavel' => true, 'tipo' => 'text']
        ],
        'consulta' => 'SELECT CPF, nome, email, tipo FROM participantes ORDER BY CPF'
    ],
    'inscricoes' => [
        'chavePrimaria' => 'ID',
        'colunas' => [
            'ID' => ['titulo' => 'ID'],
            'fkParticipante' => ['titulo' => 'CPF do participante', 'editavel' => true, 'tipo' => 'text'],
            'fkEvento' => ['titulo' => 'ID do evento', 'editavel' => true, 'tipo' => 'number']
        ],
        'consulta' => 'SELECT ID, fkParticipante, fkEvento FROM inscricoes ORDER BY ID'
    ]
];

// Esta função escapa valores antes de inseri-los no HTML.
function h($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// O horário de MySQL é convertido para o formato do campo datetime-local.
function horarioParaCampo($valor)
{
    return str_replace(' ', 'T', substr((string) $valor, 0, 16));
}

try {
    // Esta conexão usa o banco eventosAcademicos sem alterar conexao.php.
    require 'conexao_eventos.php';

    // Os formulários enviam uma ação POST para esta própria página.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $acao = $_POST['acao'] ?? '';
        $nomeTabela = $_POST['tabela'] ?? '';

        if (!is_string($acao)) {
            throw new InvalidArgumentException('Ação inválida.');
        }

        // Somente as três tabelas declaradas acima podem receber alterações.
        if (!is_string($nomeTabela) || !isset($tabelasPermitidas[$nomeTabela])) {
            throw new InvalidArgumentException('Tabela inválida.');
        }

        $configuracao = $tabelasPermitidas[$nomeTabela];

        // Cada cadastro usa somente os campos definidos na tabela correspondente.
        if ($acao === 'cadastrar') {
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

                // O formulário usa datetime-local, enquanto o MySQL usa espaço entre data e hora.
                $horario = str_replace('T', ' ', $horario);
                if (strlen($horario) === 16) {
                    $horario .= ':00';
                }

                $consulta = $pdo->prepare(
                    'INSERT INTO eventos (nome, horario) VALUES (:nome, :horario)'
                );
                $consulta->execute([
                    'nome' => $nome,
                    'horario' => $horario
                ]);
            } elseif ($nomeTabela === 'participantes') {
                $cpf = $_POST['CPF'] ?? '';
                $nome = $_POST['nome'] ?? '';
                $email = $_POST['email'] ?? '';
                $tipo = $_POST['tipo'] ?? '';

                if (
                    !is_string($cpf)
                    || !is_string($nome)
                    || !is_string($email)
                    || !is_string($tipo)
                ) {
                    throw new InvalidArgumentException('Informe dados válidos para o participante.');
                }

                $cpf = trim($cpf);
                $nome = trim($nome);
                $email = trim($email);
                $tipo = trim($tipo);

                // O CPF segue o formato de 11 números usado pela tabela do banco.
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
                    'INSERT INTO participantes (CPF, nome, email, tipo)
                     VALUES (:cpf, :nome, :email, :tipo)'
                );
                $consulta->execute([
                    'cpf' => $cpf,
                    'nome' => $nome,
                    'email' => $email,
                    'tipo' => $tipo
                ]);
            } elseif ($nomeTabela === 'inscricoes') {
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
                    'INSERT INTO inscricoes (fkParticipante, fkEvento)
                     VALUES (:cpf, :idEvento)'
                );
                $consulta->execute([
                    'cpf' => $cpf,
                    'idEvento' => $idEvento
                ]);
            }

            // O redirecionamento evita repetir o cadastro ao atualizar a página.
            header('Location: index.php?resultado=cadastrado');
            exit;
        }

        $chavePrimaria = $configuracao['chavePrimaria'];
        $chave = $_POST['chave'] ?? '';

        // A chave localiza o registro e nunca é recebida como campo editável.
        if (!is_string($chave) || $chave === '') {
            throw new InvalidArgumentException('A chave do registro é obrigatória.');
        }

        if ($acao === 'atualizar') {
            $campo = $_POST['campo'] ?? '';

            // A lista de colunas permite editar somente campos não primários.
            if (
                !is_string($campo)
                || !isset($configuracao['colunas'][$campo])
                || empty($configuracao['colunas'][$campo]['editavel'])
            ) {
                throw new InvalidArgumentException('Campo inválido ou não editável.');
            }

            $valor = $_POST['valor'] ?? '';

            // Os campos do esquema são obrigatórios e não aceitam valores vazios.
            if (!is_string($valor) || trim($valor) === '') {
                throw new InvalidArgumentException('O valor do campo é obrigatório.');
            }

            // O MySQL espera um espaço entre a data e o horário.
            if ($campo === 'horario') {
                $valor = str_replace('T', ' ', $valor);
                if (strlen($valor) === 16) {
                    $valor .= ':00';
                }
            }

            // Os identificadores vêm exclusivamente da lista fixa de tabelas e colunas.
            $consulta = $pdo->prepare(
                "UPDATE $nomeTabela SET $campo = :valor WHERE $chavePrimaria = :chave"
            );
            $consulta->execute([
                'valor' => $valor,
                'chave' => $chave
            ]);

            // O redirecionamento evita repetir a atualização ao recarregar a página.
            header('Location: index.php?resultado=atualizado');
            exit;
        }

        if ($acao === 'excluir') {
            // A chave primária limita a exclusão a uma única linha.
            $consulta = $pdo->prepare(
                "DELETE FROM $nomeTabela WHERE $chavePrimaria = :chave"
            );
            $consulta->execute(['chave' => $chave]);

            // O redirecionamento evita repetir a exclusão ao recarregar a página.
            header('Location: index.php?resultado=excluido');
            exit;
        }

        throw new InvalidArgumentException('Ação inválida.');
    }

    // Mensagens fixas confirmam ações concluídas sem confiar em texto da URL.
    $resultados = [
        'cadastrado' => 'Cadastro realizado com sucesso.',
        'atualizado' => 'Campo atualizado com sucesso.',
        'excluido' => 'Registro excluído com sucesso.'
    ];
    $resultado = $_GET['resultado'] ?? '';
    if (is_string($resultado) && isset($resultados[$resultado])) {
        $mensagem = $resultados[$resultado];
    }

} catch (InvalidArgumentException $erro) {
    // Erros de validação são apresentados ao usuário sem executar a operação.
    $mensagem = $erro->getMessage();
} catch (PDOException $erro) {
    // Os detalhes do banco ficam no log, enquanto a página mostra uma mensagem simples.
    error_log($erro->getMessage());

    // Chaves estrangeiras impedem excluir registros ainda usados por inscrições.
    if ($erro->getCode() === '23000') {
        $mensagem = 'A operação viola uma relação entre registros. Verifique as inscrições vinculadas e os valores informados.';
    } else {
        $mensagem = 'Não foi possível acessar ou alterar os dados do banco.';
    }
}

// As três tabelas são consultadas após o processamento, inclusive quando a ação falha.
if (isset($pdo)) {
    try {
        foreach ($tabelasPermitidas as $nomeTabela => $configuracao) {
            $registros[$nomeTabela] = $pdo
                ->query($configuracao['consulta'])
                ->fetchAll();
        }
    } catch (PDOException $erro) {
        // Uma falha ao carregar os registros também fica registrada no log do servidor.
        error_log($erro->getMessage());
        $mensagem = 'Não foi possível carregar os dados do banco.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de eventos acadêmicos</title>
</head>
<body>
    <h1>Gerenciamento de eventos acadêmicos</h1>

    <!-- A mensagem informa o resultado da operação ou apresenta erros. -->
    <?php if ($mensagem !== ''): ?>
        <p><?= h($mensagem) ?></p>
    <?php endif; ?>

    <!-- Este formulário cadastra um evento; o ID é gerado pelo banco de dados. -->
    <h2>Eventos</h2>
    <form method="post" action="index.php">
        <input type="hidden" name="acao" value="cadastrar">
        <input type="hidden" name="tabela" value="eventos">
        <label>
            Nome:
            <input type="text" name="nome" maxlength="100" required>
        </label>
        <label>
            Horário:
            <input type="datetime-local" name="horario" required>
        </label>
        <button type="submit">Cadastrar evento</button>
    </form>

    <!-- Esta tabela mostra os eventos e permite editar campos ou excluir linhas. -->
    <table border="1">
        <thead>
            <tr>
                <?php foreach ($tabelasPermitidas['eventos']['colunas'] as $coluna): ?>
                    <th><?= h($coluna['titulo']) ?></th>
                <?php endforeach; ?>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($registros['eventos'] === []): ?>
                <tr><td colspan="4">Nenhum registro.</td></tr>
            <?php else: ?>
                <?php foreach ($registros['eventos'] as $registro): ?>
                    <tr>
                        <?php foreach ($tabelasPermitidas['eventos']['colunas'] as $campo => $coluna): ?>
                            <td>
                                <?php if (!empty($coluna['editavel'])): ?>
                                    <form method="post" action="index.php">
                                        <input type="hidden" name="acao" value="atualizar">
                                        <input type="hidden" name="tabela" value="eventos">
                                        <input type="hidden" name="chave" value="<?= h($registro['ID']) ?>">
                                        <input
                                            type="<?= h($coluna['tipo']) ?>"
                                            name="valor"
                                            value="<?= h($campo === 'horario' ? horarioParaCampo($registro[$campo]) : $registro[$campo]) ?>"
                                            required
                                        >
                                        <input type="hidden" name="campo" value="<?= h($campo) ?>">
                                        <button type="submit">Salvar</button>
                                    </form>
                                <?php else: ?>
                                    <?= h($registro[$campo]) ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td>
                            <form method="post" action="index.php">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="tabela" value="eventos">
                                <input type="hidden" name="chave" value="<?= h($registro['ID']) ?>">
                                <button type="submit">Excluir linha</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Este formulário cadastra participante usando o CPF como chave primária. -->
    <h2>Participantes</h2>
    <form method="post" action="index.php">
        <input type="hidden" name="acao" value="cadastrar">
        <input type="hidden" name="tabela" value="participantes">
        <label>
            CPF (11 números):
            <input type="text" name="CPF" maxlength="11" pattern="[0-9]{11}" required>
        </label>
        <label>
            Nome:
            <input type="text" name="nome" maxlength="100" required>
        </label>
        <label>
            E-mail:
            <input type="email" name="email" maxlength="150" required>
        </label>
        <label>
            Tipo:
            <input type="text" name="tipo" maxlength="50" required>
        </label>
        <button type="submit">Cadastrar participante</button>
    </form>

    <!-- Esta tabela mostra os participantes e mantém o CPF somente para leitura. -->
    <table border="1">
        <thead>
            <tr>
                <?php foreach ($tabelasPermitidas['participantes']['colunas'] as $coluna): ?>
                    <th><?= h($coluna['titulo']) ?></th>
                <?php endforeach; ?>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($registros['participantes'] === []): ?>
                <tr><td colspan="5">Nenhum registro.</td></tr>
            <?php else: ?>
                <?php foreach ($registros['participantes'] as $registro): ?>
                    <tr>
                        <?php foreach ($tabelasPermitidas['participantes']['colunas'] as $campo => $coluna): ?>
                            <td>
                                <?php if (!empty($coluna['editavel'])): ?>
                                    <form method="post" action="index.php">
                                        <input type="hidden" name="acao" value="atualizar">
                                        <input type="hidden" name="tabela" value="participantes">
                                        <input type="hidden" name="chave" value="<?= h($registro['CPF']) ?>">
                                        <input
                                            type="<?= h($coluna['tipo']) ?>"
                                            name="valor"
                                            value="<?= h($registro[$campo]) ?>"
                                            required
                                        >
                                        <input type="hidden" name="campo" value="<?= h($campo) ?>">
                                        <button type="submit">Salvar</button>
                                    </form>
                                <?php else: ?>
                                    <?= h($registro[$campo]) ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td>
                            <form method="post" action="index.php">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="tabela" value="participantes">
                                <input type="hidden" name="chave" value="<?= h($registro['CPF']) ?>">
                                <button type="submit">Excluir linha</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Este formulário cadastra inscrição escolhendo participante e evento existentes. -->
    <h2>Inscrições</h2>
    <form method="post" action="index.php">
        <input type="hidden" name="acao" value="cadastrar">
        <input type="hidden" name="tabela" value="inscricoes">
        <label>
            Participante:
            <select name="fkParticipante" required>
                <option value="">Selecione</option>
                <?php foreach ($registros['participantes'] as $participante): ?>
                    <option value="<?= h($participante['CPF']) ?>">
                        <?= h($participante['nome']) ?> (<?= h($participante['CPF']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Evento:
            <select name="fkEvento" required>
                <option value="">Selecione</option>
                <?php foreach ($registros['eventos'] as $evento): ?>
                    <option value="<?= h($evento['ID']) ?>">
                        <?= h($evento['nome']) ?> (ID <?= h($evento['ID']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Cadastrar inscrição</button>
    </form>

    <!-- Esta tabela mostra as inscrições e permite editar cada chave estrangeira. -->
    <table border="1">
        <thead>
            <tr>
                <?php foreach ($tabelasPermitidas['inscricoes']['colunas'] as $coluna): ?>
                    <th><?= h($coluna['titulo']) ?></th>
                <?php endforeach; ?>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($registros['inscricoes'] === []): ?>
                <tr><td colspan="4">Nenhum registro.</td></tr>
            <?php else: ?>
                <?php foreach ($registros['inscricoes'] as $registro): ?>
                    <tr>
                        <?php foreach ($tabelasPermitidas['inscricoes']['colunas'] as $campo => $coluna): ?>
                            <td>
                                <?php if (!empty($coluna['editavel'])): ?>
                                    <form method="post" action="index.php">
                                        <input type="hidden" name="acao" value="atualizar">
                                        <input type="hidden" name="tabela" value="inscricoes">
                                        <input type="hidden" name="chave" value="<?= h($registro['ID']) ?>">
                                        <input
                                            type="<?= h($coluna['tipo']) ?>"
                                            name="valor"
                                            value="<?= h($registro[$campo]) ?>"
                                            required
                                        >
                                        <input type="hidden" name="campo" value="<?= h($campo) ?>">
                                        <button type="submit">Salvar</button>
                                    </form>
                                <?php else: ?>
                                    <?= h($registro[$campo]) ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td>
                            <form method="post" action="index.php">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="tabela" value="inscricoes">
                                <input type="hidden" name="chave" value="<?= h($registro['ID']) ?>">
                                <button type="submit">Excluir linha</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
