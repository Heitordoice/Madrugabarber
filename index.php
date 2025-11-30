<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MadrugasBarber</title>
    <script src="scripts/main.js"></script>
    
   
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
        <?php
        // mostrar link para agenda apenas se usuário logado
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!empty($_SESSION['usuario']['id'])): ?>
          <li><a href="agenda.php">Agenda</a></li>
        <?php endif; ?>
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
    <?php if (!empty($_SESSION['usuario']['id'])): ?>
      <p>Bem-vindo, <strong><?php echo htmlspecialchars($_SESSION['usuario']['nome']); ?></strong></p>
      <p><a href="agenda.php">Ir para Agenda</a> | <a href="logout.php">Logout</a></p>
    <?php else: ?>
      <form action="logar.php" method="post" >
        <input type="email" name="email_func" maxlength="100" required placeholder="Email"><br>
        <input type="password" name="senha_func" required maxlength="100" placeholder="Senha"><br><br>
        <input type="submit" value="Login" style="width: 200px; border-radius: 15px;">
      </form>
      <a href="form-cad.php">Cadastrar</a>
    <?php endif; ?>
  </div>

  <footer class="forms">
        <p>&copy; 2024 Madruga's Barber. Todos os direitos reservados.</p>
    </footer>
</body>
</html>