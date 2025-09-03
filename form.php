<?php
    include __DIR__."/header.php";
?>
<form action="login" method="post" class="form">
    <input type="text" name="nome" placeholder="Nome" class="cadastro"><br>
    <input type="email" name="email" placeholder="Email" class="cadastro"><br>
    <input type="password" name="senha" placeholder="Senha" class="cadastro"><br>
    <input type="password" name="confirma-senha" placeholder="Confirme sua senha" class="cadastro"><br>
    <input type="submit" value="Cadastrar">
</form>
<?php
    include __DIR__."/footer.php";
?>