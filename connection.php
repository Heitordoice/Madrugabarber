
<?php
    const host = "localhost";
    const port ="3306";
    const user = "root";
    const senha = "root";
    const banco = "madrugas";

    try{
        $conn = new PDO("mysql:host=".host.";port=".port.";dbname=".banco, user, senha);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }catch(PDOexpection $e){
        echo "ERROR: ".$e->getMessage();
    }
?> 
