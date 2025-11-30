<?php
// exigir sessão e autenticação mínima
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['usuario']['id'])) {
  header('Location: index.php');
  exit;
}
$userName = $_SESSION['usuario']['nome'] ?? 'Usuário';
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Agenda</title>
<style>:root{font-family:Inter,system-ui,Segoe UI,Roboto,Arial}body{margin:0;background:#f6f7fb;color:#222;padding:18px}.container{max-width:1100px;margin:0 auto;background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,.06)}.flex{display:flex;gap:16px}.col{flex:1}.sidebar{flex-basis:320px}.card{background:#fff;padding:12px;border-radius:8px;border:1px solid #efefef;margin-bottom:12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #eee;padding:6px;text-align:center;vertical-align:top}.time{font-size:13px;color:#666;font-weight:600}.slot{padding:6px;border-radius:6px;margin-bottom:6px;font-size:13px}.free{background:#f2f8ff;color:#0a47a1;border:1px dashed #cfe0ff;cursor:pointer}.busy{background:#ffecec;color:#8a1f1f;border:1px solid #ffd1d1}label{display:block;margin-bottom:4px;font-weight:600}input,select{width:100%;padding:8px;border-radius:6px;border:1px solid #ddd}button{background:#b34fa8;color:#fff;padding:10px;border:none;border-radius:6px;cursor:pointer}@media(max-width:900px){.flex{flex-direction:column}}</style>
</head>
<body>
<div class="container">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;"><h1 style="margin:0;font-size:18px">Agenda Semanal</h1><div style="font-size:14px">Logado como <strong><?php echo htmlspecialchars($userName); ?></strong> | <a href="logout.php">Logout</a></div></div>
  <div class="flex">
    <div class="col card">
      <h3>Semana de <?php echo date('d/m/Y',strtotime($days[$display_days[0]])); ?> a <?php echo date('d/m/Y',strtotime($days[end($display_days)])); ?></h3>
      <table aria-label="Agenda semanal"><thead><tr><th class="time">Horário</th><?php foreach($display_days as $d): ?><th><?php echo strftime('%A',strtotime($days[$d])); ?><br><small><?php echo date('d/m',strtotime($days[$d])); ?></small></th><?php endforeach; ?></tr></thead><tbody>
      <?php for($m=$minOpen;$m<$maxClose;$m+=$slot_step_minutes): $hour=str_pad(intdiv($m,60),2,'0',STR_PAD_LEFT).':'.str_pad($m%60,2,'0',STR_PAD_LEFT); ?>
        <tr><td class="time"><?php echo $hour; ?></td>
          <?php foreach($display_days as $d): $date=$days[$d]; if(!isset($business_hours[$d])){ echo "<td><div style='color:#aaa'>Fechado</div></td>"; continue; } $open_ts=ts($date,$business_hours[$d]['open']); $close_ts=ts($date,$business_hours[$d]['close']); $slotStart=ts($date,$hour); $slotEnd=$slotStart+$slot_step_minutes*60; if($slotStart<$open_ts||$slotStart>=$close_ts){ echo "<td></td>"; continue; } $dayBookings=$by_date[$date]??[]; $free=true; foreach($dayBookings as $b) if(overlap($slotStart,$slotEnd,$b['start'],$b['end'])){ $free=false; break; } $cls=$free?'free':'busy'; $display=null; foreach($dayBookings as $b) if($b['start']===$slotStart){ $display=$b; break; } if($display){ $s=date('H:i',$display['start']); $e=date('H:i',$display['end']); echo "<td><div class='slot $cls'><strong>".htmlspecialchars($display['service'])."</strong><br><small>".htmlspecialchars($display['client'])."</small><br><small>{$s} - {$e}</small></div></td>"; } else { $t=date('H:i',$slotStart); echo "<td><div class='slot $cls' data-date='{$date}' data-time='{$t}' onclick='selectSlot(this)'>".($free?"Livre • {$t}":"Ocupado")."</div></td>"; } endforeach; ?></tr>
      <?php endfor; ?></tbody></table>
      <div style="margin-top:12px;color:#666;font-size:13px">Clique em um horário <strong>livre</strong> para preencher o formulário.</div>
    </div>
    <aside class="sidebar">
      <div class="card"><h4>Serviços</h4><ul><?php foreach($services as $n=>$dur) echo "<li><strong>".htmlspecialchars($n)."</strong> — {$dur} min</li>"; ?></ul></div>
      <div class="card"><h4>Novo Agendamento</h4>
        <div class="messages"><?php foreach($messages as $m) echo "<p class='".($m['type']=='success'?'success':'error')."'>".htmlspecialchars($m['text'])."</p>"; ?></div>
        <form method="post" id="bookingForm"><input type="hidden" name="action" value="book">
          <label>Nome</label><input type="text" name="client_name" id="client_name" required>
          <label>Telefone</label><input type="text" name="phone" required placeholder="(xx) 9xxxx-xxxx">
          <label>Serviço</label><select name="service" required><option value="">-- selecione --</option><?php foreach($services as $n=>$d) echo "<option value='".htmlspecialchars($n)."'>".htmlspecialchars($n)." — {$d} min</option>"; ?></select>
          <label>Data</label><input type="date" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
          <label>Horário</label><input type="time" id="time" name="time" required step="<?php echo $slot_step_minutes*60; ?>">
          <div style="margin-top:8px"><button type="submit">Agendar</button></div>
        </form>
      </div>
      <div class="card"><h4>Agendamentos desta semana</h4><?php $week=array_filter($bookings,function($b) use($days){return in_array($b['date'],$days);}); if(empty($week)) echo "<div style='color:#666'>Nenhum agendamento nesta semana.</div>"; else{ echo "<ul style='padding-left:18px;margin:0'>"; foreach($week as $b) echo "<li style='margin-bottom:6px'><strong>".htmlspecialchars($b['service'])."</strong><br><small>".htmlspecialchars($b['client'])." — {$b['date']} • ".date('d/m H:i',$b['start'])."–".date('H:i',$b['end'])."</small></li>"; echo "</ul>"; } ?></div>
      <div class="card"><h4>Opções</h4><form method="post" onsubmit="return confirm('Deseja limpar todos os agendamentos (apenas sessão atual)?');"><input type="hidden" name="action" value="clear_all"><button type="submit" style="background:#f05f5f">Limpar agendamentos (sessão)</button></form></div>
    </aside>
  </div>
</div>
<script>
function selectSlot(el){ if(!el.classList.contains('free')) return; document.getElementById('date').value=el.getAttribute('data-date'); document.getElementById('time').value=el.getAttribute('data-time'); document.getElementById('client_name').focus(); }
</script>
</body>
</html>
