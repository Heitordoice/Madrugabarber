<?php
    include __DIR__.'/connection.php';
    if(!isset($_POST['email_func'],$_POST['senha_func'])){
        header('Location:index.php');
        die();
    }
    $email = filter_input(INPUT_POST, 'email_func', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha_func'];

    try {
        // Seleciona também o cod_func para setar na sessão
        $consulta = $conn->prepare("SELECT cod_func, nome_func, senha_func FROM funcionarios WHERE email_func = :email_func LIMIT 1;");
        $consulta->bindParam(':email_func', $email);
        $consulta->execute();

        if ($consulta->rowCount() !== 1) {
            header('Location:index.php');
            die();
        }

        $data = $consulta->fetch(PDO::FETCH_OBJ);

        // Se as senhas foram armazenadas com hash, use password_verify. Caso contrário, aceita string igual.
        $password_ok = false;
        if (isset($data->senha_func) && password_verify($senha, $data->senha_func)) {
            $password_ok = true;
        } elseif ($senha === $data->senha_func) {
            $password_ok = true; // fallback caso senha esteja em texto plano no BD
        }

        if (!$password_ok) {
            header('Location:index.php');
            die();
        }

        session_start();
        $_SESSION['usuario']['id'] = $data->cod_func;
        $_SESSION['usuario']['nome'] = $data->nome_func;
        header('Location:sobre_nos.php');
        die();
    } catch (PDOException $e) {
        // Não exibir erro completo em produção
        header('Location:index.php');
        die();
    }
?>