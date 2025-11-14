<?php
    include __DIR__.'/connection.php';
    if(!isset($_POST['email_func'],$_POST['senha_func'])){
        header('Location:index.php');
        die();
    }
    $email=$_POST['email_func'];
    $senha=$_POST['senha_func'];
    try{
        /* Consultando o banco de dados para efetuar o login e senha
        $consulta=$conn->query("select * from usuario where login_user='".$login."' && senha_user='".$senha."';");*/
        $consulta=$conn->prepare("select nome_func, senha_func from funcionarios where email_func=:email_func && senha_func=:senha_func;");
        $consulta->bindParam(':email_func',$email);
        $consulta->bindParam(':senha_func',$senha);
        $consulta->execute();
        //contando o número de respostas
        $quant=$consulta->rowCount();
        if($quant!=1){
            header('Location:index.php');
            die();
        }
        $data=$consulta->fetch(PDO::FETCH_OBJ);
        //echo $data->nome_user;
        session_start();
        $_SESSION['usuario']['id']=$data->cod_func;
        $_SESSION['usuario']['nome']=$data->nome_func;
        header('Location:sobre_nos.php');
    }catch(PDOException $e){
        echo "Erro:".$e->getMessage();
    }
?>