<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MadrugasBarber</title>
    <script src="scripts/main.js"></script>
    
    <script src="https://kit.fontawesome.com/yourcode.js" crossorigin="anonymous"></script>
     <link rel="icon" type="image/x-icon" href="imagens/favicon.ico">
     <link rel="stylesheet" href="css/form.css">
     <link rel="stylesheet" href="css/estilo_sobre.css">
</head>
<body>

  <header>  
    <img src="imagens/logo.png" alt="" class="logo-madruga">
    <h1>Madruga's Barber</h1>
  
    <nav class="alt">
      <ul>
        <li><a href="sobre_nos.php">Início</a></li>
        <br>
        <br>
        <li><a href="servicos.php">Serviços</a></li>
      </ul>
    </nav>
    
    <div class="social">

       <a href="https://www.instagram.com/madrugasbarber/" target="_blank" class="instagram">
      
        <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Instagram_icon.png" alt="Instagram">
    </a>
    </div>
  </header>

  <hgroup>
    <h1>Login</h1>
  </hgroup>
  <div class="form">

    <form action="cad-func.php" method="post" >
    <input type="text" name="nome_func" maxlength="100" required placeholder="Nome"><br>
    <input type="email" name="email_func" maxlength="100" required placeholder="Email"><br>
    <input type="text" name="telefone_func" maxlength="11" required placeholder="Telefone"><br>
    <input type="text" name="status_func" maxlenght="8" placeholder="Estado civil (Casado/Solteiro)"><br>
    <input type="text" name="endereço_func" maxlength="200" required placeholder="Endereço"><br>
    <input type="text" name="sexo_func" maxlength="1" placeholder="Sexo (F = Feminino / M = Masculino)"><br>
    <input type="password" name="senha_func" maxlength="100" required placeholder="Senha"><br><br>
    <input type="submit" value="Cadastrar" style="width: 200px; border-radius: 15px;"><br>
    <input type="reset" value="Limpar" style="width: 200px; border-radius: 15px"><br>
    </form>
    <a href="index.php">Voltar</a>

  </div>

  <footer>
        <p>&copy; 2024 Madruga's Barber. Todos os direitos reservados.</p>
    </footer>
</body>
</html>