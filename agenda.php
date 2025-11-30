<?php
session_start();
// exigir que o usuário esteja logado
if (empty($_SESSION['usuario']['id'])) {
    header('Location: index.php');
    exit;
}
/**
 * agenda_cabeleireiro.php
 * Calendário semanal simples para salão/cabeleireiro
 *
 * Uso:
 *  - Salve como agenda_cabeleireiro.php
 *  - Rode em servidor com PHP
 *  - Agendamentos são salvos em $_SESSION['bookings'] (apenas exemplo)
 *
 * Observações:
 *  - Personalize $business_hours e $services conforme necessário.
 *  - O passo de geração de slots é $slot_step_minutes (padrão 30).
 */

/* --------- CONFIGURAÇÃO --------- */

// Fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Dias da semana exibidos (keys: 1=segunda ... 7=domingo).
// Aqui usamos Terça(2) a Sábado(6) conforme sugestão anterior.
$display_days = [2,3,4,5,6]; // 2=ter,3=qua,4=qui,5=sex,6=sab

// Horários de funcionamento por dia (24h): ['open'=>'HH:MM','close'=>'HH:MM']
// Coloque 'closed' => true para dia fechado.
$business_hours = [
    2 => ['open'=>'09:00','close'=>'18:00'], // Tue
    3 => ['open'=>'09:00','close'=>'18:00'], // Wed
    4 => ['open'=>'09:00','close'=>'18:00'], // Thu
    5 => ['open'=>'09:00','close'=>'19:00'], // Fri
    6 => ['open'=>'08:00','close'=>'17:00'], // Sat
];

// Serviços: nome => duração em minutos
$services = [
    'Corte feminino' => 45,
    'Corte masculino' => 30,
    'Escova' => 40,
    'Hidratação' => 60,
    'Coloração' => 120,
    'Luzes' => 180,
    'Progressiva' => 150,
    'Barba' => 30,
    'Sobrancelha' => 20,
];

// Geração de slots: passo em minutos (ex: 30)
$slot_step_minutes = 30;

// Regras de cancelamento/pagamento podem ser implementadas externamente.

/* --------- FUNÇÕES ÚTEIS --------- */

/**
 * Converte "HH:MM" em timestamp (usando data base passada)
 */
function time_to_timestamp($dateYmd, $timeHM) {
    return strtotime($dateYmd . ' ' . $timeHM);
}

/**
 * Checa overlap entre dois intervalos [start,end)
 */
function intervals_overlap($a_start, $a_end, $b_start, $b_end) {
    return ($a_start < $b_end) && ($b_start < $a_end);
}

/**
 * Retorna array de slots para um dia (timestamps start,end)
 */
function generate_day_slots($dateYmd, $open, $close, $step_minutes) {
    $slots = [];
    $t = time_to_timestamp($dateYmd, $open);
    $endDay = time_to_timestamp($dateYmd, $close);
    while ($t < $endDay) {
        $slotStart = $t;
        $slotEnd = $t + $step_minutes * 60;
        if ($slotEnd > $endDay) $slotEnd = $endDay;
        $slots[] = ['start'=>$slotStart,'end'=>$slotEnd];
        $t += $step_minutes * 60;
    }
    return $slots;
}

/**
 * Checa se um slot (start,end) fica livre dado array de bookings
 * bookings elements: ['date'=>'YYYY-mm-dd','start'=>timestamp,'end'=>timestamp,...]
 */
function slot_is_free($start, $end, $bookings) {
    foreach ($bookings as $b) {
        if (intervals_overlap($start, $end, $b['start'], $b['end'])) return false;
    }
    return true;
}

/* --------- MANIPULAÇÃO DOS AGENDAMENTOS (simples em sessão) --------- */
if (!isset($_SESSION['bookings'])) $_SESSION['bookings'] = [];

// permitir limpar todos os agendamentos (POST) antes de processar outros POSTs
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']==='clear_all') {
    $_SESSION['bookings'] = [];
    header('Location: agenda.php');
    exit;
}

$userName = $_SESSION['usuario']['nome'] ?? '';

/* Recebe submissão de agendamento */
$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']==='book') {
    $client_name = trim($_POST['client_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $service = $_POST['service'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';

    // Validações básicas
    if ($client_name==='' || $phone==='' || $service==='' || $date==='' || $time==='') {
        $messages[] = ['type'=>'error','text'=>'Preencha todos os campos.'];
    } elseif (!isset($services[$service])) {
        $messages[] = ['type'=>'error','text'=>'Serviço inválido.'];
    } else {
        // calcular intervalo do serviço
        $duration = $services[$service];
        $start = time_to_timestamp($date, $time);
        $end = $start + $duration * 60;

        // checar se horário dentro do expediente
        $weekday = (int)date('N', $start); // 1-7
        if (!isset($business_hours[$weekday])) {
            $messages[] = ['type'=>'error','text'=>'Dia não atende (fechado).'];
        } else {
            $open_ts = time_to_timestamp($date, $business_hours[$weekday]['open']);
            $close_ts = time_to_timestamp($date, $business_hours[$weekday]['close']);
            if ($start < $open_ts || $end > $close_ts) {
                $messages[] = ['type'=>'error','text'=>'O horário solicitado está fora do expediente.'];
            } else {
                // checar conflito
                $conflict = false;
                foreach ($_SESSION['bookings'] as $b) {
                    if ($b['date'] === $date) {
                        if (intervals_overlap($start, $end, $b['start'], $b['end'])) {
                            $conflict = true; break;
                        }
                    }
                }
                if ($conflict) {
                    $messages[] = ['type'=>'error','text'=>'Horário já ocupado. Escolha outro horário.'];
                } else {
                    // salvar
                    $_SESSION['bookings'][] = [
                        'id' => uniqid('bk_'),
                        'client' => $client_name,
                        'phone' => $phone,
                        'service' => $service,
                        'date' => $date,
                        'start' => $start,
                        'end' => $end,
                        'created' => time(),
                    ];
                    $messages[] = ['type'=>'success','text'=>"Agendamento confirmado para {$date} às {$time} ({$service})."];
                }
            }
        }
    }
}

/* --------- RENDER: calcular semana atual (segunda como início) --------- */
$today = time();
$weekStart = strtotime('monday this week', $today); // segundo
// porém queremos mostrar terça-sábado como colunas; calculamos datas
$days = [];
foreach ($display_days as $d) {
    // $d é 1..7 -> precisamos obter data daquele dia nesta semana
    $date = date('Y-m-d', strtotime("this week +".($d-1)." days", $weekStart));
    $days[$d] = $date;
}

/* bookings úteis para exibir */
$bookings = $_SESSION['bookings'];

/* --------- HTML / CSS / JS output --------- */
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Agenda - Cabeleireiro</title>
<style>
    :root{font-family:Inter,system-ui,Segoe UI,Roboto,Arial;--accent:#b34fa8;--muted:#666;}
    body{margin:0;background:#f6f7fb;color:#222;padding:18px;}
    .container{max-width:1100px;margin:0 auto;background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,.06);}
    h1{margin:0 0 14px;font-size:22px;}
    .flex{display:flex;gap:16px;align-items:flex-start;}
    .col{flex:1;}
    .sidebar{flex-basis:320px;}
    .card{background:#fff;padding:12px;border-radius:8px;border:1px solid #efefef;margin-bottom:12px;}
    table.calendar{width:100%;border-collapse:collapse;}
    table.calendar th, table.calendar td{border:1px solid #eee;padding:6px;text-align:center;vertical-align:top;}
    table.calendar th{background:#fafafa;font-weight:600;}
    .time{font-size:13px;color:var(--muted);font-weight:600;}
    .slot{padding:6px;border-radius:6px;margin-bottom:6px;font-size:13px;}
    .slot.free{background:#f2f8ff;color:#0a47a1;border:1px dashed #cfe0ff;cursor:pointer;}
    .slot.busy{background:#ffecec;color:#8a1f1f;border:1px solid #ffd1d1;}
    .service-list li{margin-bottom:6px;}
    label{display:block;margin-bottom:4px;font-weight:600;}
    input[type=text], select, input[type=date], input[type=time]{width:100%;padding:8px;border-radius:6px;border:1px solid #ddd;}
    button{background:var(--accent);color:#fff;padding:10px 12px;border:none;border-radius:6px;cursor:pointer;}
    .messages p{margin:6px 0;padding:8px;border-radius:6px;}
    .messages .success{background:#e6fff1;color:#04623b;border:1px solid #b9f2d0;}
    .messages .error{background:#fff0f0;color:#8a1f1f;border:1px solid #ffd1d1;}
    @media(max-width:900px){
        .flex{flex-direction:column;}
        .sidebar{order:2;}
    }
</style>
</head>
<body>
<div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
        <h1 style="margin:0">Agenda Semanal — Cabeleireiro</h1>
        <div style="font-size:14px;color:#444">
            Logado como <strong><?php echo htmlspecialchars($userName); ?></strong>
            &nbsp;|&nbsp; <a href="logout.php">Logout</a>
        </div>
    </div>
    <div class="flex">
        <!-- CALENDÁRIO -->
        <div class="col card" style="min-width:0;">
            <h3>Semana de <?php echo date('d/m/Y', strtotime($days[$display_days[0]])); ?> a <?php echo date('d/m/Y', strtotime($days[end($display_days)])); ?></h3>

            <table class="calendar" aria-label="Agenda semanal">
                <thead>
                    <tr>
                        <th class="time">Horário</th>
                        <?php foreach ($display_days as $d): ?>
                            <th><?php echo strftime('%A', strtotime($days[$d])); ?> <br><small><?php echo date('d/m', strtotime($days[$d])); ?></small></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <!-- precisamos decidir faixa horária máxima para renderizar linhas: achar mínimo open e máximo close -->
                    <?php
                    // calcular minOpen e maxClose (em minutos do dia)
                    $minOpen = 24*60; $maxClose = 0;
                    foreach ($display_days as $d) {
                        if (isset($business_hours[$d])) {
                            [$oh, $oc] = [$business_hours[$d]['open'], $business_hours[$d]['close']];
                            $minOpen = min($minOpen, intval(substr($oh,0,2))*60 + intval(substr($oh,3,2)));
                            $maxClose = max($maxClose, intval(substr($oc,0,2))*60 + intval(substr($oc,3,2)));
                        }
                    }
                    // gerar linhas por slot_step_minutes
                    for ($m=$minOpen; $m < $maxClose; $m += $slot_step_minutes):
                        $hour = str_pad(intdiv($m,60),2,'0',STR_PAD_LEFT).':'.str_pad($m%60,2,'0',STR_PAD_LEFT);
                    ?>
                    <tr>
                        <td class="time"><?php echo $hour; ?></td>
                        <?php foreach ($display_days as $d):
                            $date = $days[$d];
                            if (!isset($business_hours[$d])) {
                                // dia fechado
                                echo "<td><div style='color:#aaa;'>Fechado</div></td>";
                                continue;
                            }
                            $open = $business_hours[$d]['open'];
                            $close = $business_hours[$d]['close'];
                            $slotStart = time_to_timestamp($date, $hour);
                            $slotEnd = $slotStart + $slot_step_minutes*60;
                            $open_ts = time_to_timestamp($date, $open);
                            $close_ts = time_to_timestamp($date, $close);

                            if ($slotStart < $open_ts || $slotStart >= $close_ts) {
                                echo "<td></td>";
                                continue;
                            }

                            // verificar se o slot fica livre ou ocupado (qualquer booking que intersecte)
                            $isFree = slot_is_free($slotStart, $slotStart + $slot_step_minutes*60, array_filter($bookings, function($b) use($date){
                                return $b['date']===$date;
                            }));
                            // Para exibição: se o slot coincide com início de um booking, mostre busy e o serviço
                            $slotContent = '';
                            $slotClass = $isFree ? 'free' : 'busy';
                            // procurar booking que comece nesse slot
                            $displayBooking = null;
                            foreach ($bookings as $b) {
                                if ($b['date'] === $date && $b['start'] === $slotStart) {
                                    $displayBooking = $b; break;
                                }
                            }
                            if ($displayBooking) {
                                $startFmt = date('H:i', $displayBooking['start']);
                                $endFmt = date('H:i', $displayBooking['end']);
                                $slotContent = "<div class='slot $slotClass'> <strong>{$displayBooking['service']}</strong><br><small>{$displayBooking['client']}</small><br><small>{$startFmt} - {$endFmt}</small></div>";
                            } else {
                                // mostrar botão para reservar neste horário (assumindo serviço escolhido na sidebar)
                                $timeStr = date('H:i', $slotStart);
                                $slotContent = "<div class='slot $slotClass' data-date='{$date}' data-time='{$timeStr}' onclick='selectSlot(this)'>".($isFree ? "Livre • {$timeStr}" : "Ocupado")."</div>";
                            }
                            echo "<td>{$slotContent}</td>";
                        endforeach; ?>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

            <div style="margin-top:12px;font-size:13px;color:var(--muted)">
                Clique em um horário <strong>livre</strong> para preenchê-lo no formulário de agendamento.
            </div>
        </div>

        <!-- SIDEBAR: Serviços + Formulário -->
        <aside class="sidebar">
            <div class="card">
                <h4>Serviços</h4>
                <ul class="service-list">
                    <?php foreach ($services as $name=>$dur): ?>
                        <li><strong><?php echo $name; ?></strong> — <?php echo $dur; ?> min</li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="card">
                <h4>Novo Agendamento</h4>

                <div class="messages">
                    <?php foreach ($messages as $m): ?>
                        <p class="<?php echo $m['type']=='success'?'success':'error'; ?>"><?php echo htmlspecialchars($m['text']); ?></p>
                    <?php endforeach; ?>
                </div>

                <form method="post" id="bookingForm">
                    <input type="hidden" name="action" value="book">
                    <label for="client_name">Nome</label>
                    <input type="text" id="client_name" name="client_name" required>

                    <label for="phone">Telefone</label>
                    <input type="text" id="phone" name="phone" required placeholder="(xx) 9xxxx-xxxx">

                    <label for="service">Serviço</label>
                    <select id="service" name="service" required>
                        <option value="">-- selecione --</option>
                        <?php foreach ($services as $name=>$dur): ?>
                            <option value="<?php echo htmlspecialchars($name); ?>"><?php echo htmlspecialchars($name); ?> — <?php echo $dur; ?> min</option>
                        <?php endforeach; ?>
                    </select>

                    <label for="date">Data</label>
                    <input type="date" id="date" name="date" required min="<?php echo date('Y-m-d'); ?>">

                    <label for="time">Horário</label>
                    <input type="time" id="time" name="time" required step="<?php echo $slot_step_minutes*60; ?>">

                    <div style="margin-top:8px;">
                        <button type="submit">Agendar</button>
                    </div>
                </form>
            </div>

            <div class="card">
                <h4>Agendamentos desta semana</h4>
                <?php
                $weekBookings = array_filter($bookings, function($b) use($days){
                    return in_array($b['date'], $days);
                });
                if (empty($weekBookings)) {
                    echo "<div style='color:#666'>Nenhum agendamento nesta semana.</div>";
                } else {
                    echo "<ul style='padding-left:18px;margin:0'>";
                    foreach ($weekBookings as $b) {
                        $start = date('d/m H:i', $b['start']);
                        $end = date('H:i', $b['end']);
                        echo "<li style='margin-bottom:6px'><strong>{$b['service']}</strong><br><small>{$b['client']} — {$b['date']} • {$start}–{$end}</small></li>";
                    }
                    echo "</ul>";
                }
                ?>
            </div>

            <div class="card">
                <h4>Opções</h4>
                <form method="post" onsubmit="return confirm('Deseja limpar todos os agendamentos (apenas sessão atual)?');">
                    <input type="hidden" name="action" value="clear_all">
                    <button type="submit" style="background:#f05f5f">Limpar agendamentos (sessão)</button>
                </form>
            </div>
        </aside>
    </div>
</div>

<script>
/* scripts mínimos para UX */
function selectSlot(el){
    // só preenche quando o slot estiver livre (classe free)
    if (!el.classList.contains('free')) return;
    var date = el.getAttribute('data-date');
    var time = el.getAttribute('data-time');
    // preencher formulário
    document.getElementById('date').value = date;
    document.getElementById('time').value = time;
    // opcional: focar nome
    document.getElementById('client_name').focus();
}

/* tratar 'clear_all' via POST: sem JS necessário, mas implementamos em PHP abaixo */
</script>

<?php
// lidar com ação clear_all (após renderizar HTML não dá, então checar antes saída; contudo já checamos apenas no início)
// implementado agora: se usuário clicou limpar, faça reset e redirect para evitar repost
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']==='clear_all') {
    $_SESSION['bookings'] = [];
    header("Location: ".$_SERVER['REQUEST_URI']);
    exit;
}
?>
</body>
</html>
