<?php
include __DIR__.'/connection.php';

if (!isset($_POST['cod_cli'], $_POST['cod_serv'], $_POST['data_agendamento'], $_POST['hora_agendamento'])) {
    header("Location: agenda.php?status=invalid", true, 303);
    exit;
}

$cod_cli = filter_input(INPUT_POST, 'cod_cli', FILTER_VALIDATE_INT);
$cod_serv = filter_input(INPUT_POST, 'cod_serv', FILTER_VALIDATE_INT);
$data = filter_input(INPUT_POST, 'data_agendamento', FILTER_SANITIZE_STRING);
$hora = filter_input(INPUT_POST, 'hora_agendamento', FILTER_SANITIZE_STRING);

if ($cod_cli === false || $cod_serv === false || empty($data) || empty($hora)) {
    header("Location: agenda.php?status=invalid", true, 303);
    exit;
}

try {
    // Verifica se o horário já está ocupado
    $sql_check = "SELECT 1 FROM agendamentos WHERE data_agendamento = :data AND hora_agendamento = :hora LIMIT 1;";
    $stmt = $conn->prepare($sql_check);
    $stmt->bindParam(':data', $data);
    $stmt->bindParam(':hora', $hora);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        header("Location: agenda.php?status=occupied", true, 303);
        exit;
    }

    // Inserir agendamento
    $sql_insert = "INSERT INTO agendamentos (cod_cli, cod_serv, data_agendamento, hora_agendamento) VALUES (:cod_cli, :cod_serv, :data, :hora);";
    $stmt = $conn->prepare($sql_insert);
    $stmt->bindParam(':cod_cli', $cod_cli, PDO::PARAM_INT);
    $stmt->bindParam(':cod_serv', $cod_serv, PDO::PARAM_INT);
    $stmt->bindParam(':data', $data);
    $stmt->bindParam(':hora', $hora);

    if ($stmt->execute()) {
        header("Location: agenda.php?status=success", true, 303);
        exit;
    } else {
        header("Location: agenda.php?status=error", true, 303);
        exit;
    }
} catch (PDOException $e) {
    // Não expor erros sensíveis em produção
    header("Location: agenda.php?status=error", true, 303);
    exit;
}

echo "<br><a href='agenda.php'>Voltar</a>";
?>