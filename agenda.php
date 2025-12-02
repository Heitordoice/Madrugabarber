<!doctype html>
<html lang="pt-br">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Agendamento - Madruga Barber</title>
	<link rel="stylesheet" href="css/form.css">
	
</head>
<body>
	<div class="container">
		<?php
		// Exibe mensagem baseada no status retornado pelo redirecionamento usando if/else
		$status = $_GET['status'] ?? '';
		if ($status) {
			if ($status === 'success') {
				echo '<div class="alert success">Agendamento realizado com sucesso!</div>';
			} elseif ($status === 'occupied') {
				echo '<div class="alert error">Este horário já está ocupado. Escolha outro horário.</div>';
			} elseif ($status === 'invalid') {
				echo '<div class="alert error">Dados inválidos. Verifique os campos e tente novamente.</div>';
			} else {
				echo '<div class="alert info">Ocorreu um problema. Tente novamente mais tarde.</div>';
			}
		}
		?>
		<h1>Agendar horário</h1>
		<p class="lead">Preencha os dados abaixo para marcar o atendimento.</p>

		<form action="agendamento.php" method="POST" id="agendaForm">
			<label for="cod_cli">Cliente (ID)</label>
			<input class="input" type="number" id="cod_cli" name="cod_cli" required placeholder="Digite o código do cliente">

			<label for="cod_serv">Serviço (ID)</label>
			<input class="input" type="number" id="cod_serv" name="cod_serv" required placeholder="Digite o código do serviço">

			<div style="height:8px;"></div>

			<div class="row">
				<div>
					<label for="data_agendamento">Data</label>
					<input type="date" id="data_agendamento" name="data_agendamento" required>
				</div>
				<div>
					<label for="hora_agendamento">Hora</label>
					<input type="time" id="hora_agendamento" name="hora_agendamento" required>
				</div>
			</div>

			<button type="submit" class="cta">Agendar</button>

			<p class="note">Ao enviar, você será redirecionado para confirmar o agendamento.</p>
		</form>
	</div>

	<script>
		// Define a data mínima como hoje para evitar agendamentos no passado
		(function(){
			const dateInput = document.getElementById('data_agendamento');
			if(!dateInput) return;
			const hoje = new Date();
			const yyyy = hoje.getFullYear();
			const mm = String(hoje.getMonth()+1).padStart(2,'0');
			const dd = String(hoje.getDate()).padStart(2,'0');
			dateInput.min = `${yyyy}-${mm}-${dd}`;
		})();
	</script>
</body>
</html>