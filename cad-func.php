<?php
    include __DIR__.'/connection.php';

    try{
        //Verificar se as variáveis $_POST existem
        if(!isset($_POST['nome_func']) || !isset($_POST['email_func']) || !isset($_POST['telefone_func']) || !isset($_POST['senha_func'])){
            header("Location: form-cad.php");
            die();
        }
        //Passar os dados para variáveis
        $nome = $_POST['nome_func'];
        $email = $_POST['email_func'];
        $telefone = $_POST['telefone_func'];
        $senha = $_POST['senha_func'];
        
        //Criar a query para o insert
        $stmt=$conn->prepare("insert into funcionarios(nome_func, email_func, telefone_func, senha_func)
        values(?,?,?,?);");
        //Passar o parâmetro dos valores
        $stmt->bindParam(1,$nome);
        $stmt->bindParam(2,$email);
        $stmt->bindParam(3,$telefone);
        $stmt->bindParam(4,$senha);
        //Executando o insert
        $stmt->execute();
        header("Location: form-cad.php");
        die();
    }catch(PDOexception $e){
        echo "ERROR: ".$e->getMessage();
    }
?>