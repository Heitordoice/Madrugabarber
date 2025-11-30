<?php
session_start();
// exigir que o usuário esteja logado
<?php
session_start();
if (empty($_SESSION['usuario']['id'])) { header('Location: index.php'); exit; }
date_default_timezone_set('America/Sao_Paulo');

$display_days = [2,3,4,5,6];
$business_hours = [2=>['open'=>'09:00','close'=>'18:00'],3=>['open'=>'09:00','close'=>'18:00'],4=>['open'=>'09:00','close'=>'18:00'],5=>['open'=>'09:00','close'=>'19:00'],6=>['open'=>'08:00','close'=>'17:00']];
$services = ['Corte masculino'=>30,'Escova'=>40,'Hidratação'=>60,'Coloração'=>120,'Luzes'=>180,'Progressiva'=>150,'Barba'=>30,'Sobrancelha'=>20];
$slot_step_minutes = 30;

function ts($d,$t){return strtotime($d.' '.$t);} // timestamp helper
function overlap($a,$b,$c,$d){return ($a<$d)&&($c<$b);} // [a,b) vs [c,d)

if (!isset($_SESSION['bookings'])) $_SESSION['bookings'] = [];
// clear all
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='clear_all') { $_SESSION['bookings']=[]; header('Location: agenda.php'); exit; }

$userName = $_SESSION['usuario']['nome'] ?? '';
$messages = [];
// book
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='book') {
    $client = trim($_POST['client_name'] ?? ''); $phone = trim($_POST['phone'] ?? ''); $service = $_POST['service'] ?? ''; $date = $_POST['date'] ?? ''; $time = $_POST['time'] ?? '';
    if ($client===''||$phone===''||$service===''||$date===''||$time==='') $messages[]=['type'=>'error','text'=>'Preencha todos os campos.'];
    elseif (!isset($services[$service])) $messages[]=['type'=>'error','text'=>'Serviço inválido.'];
    else {
        $dur = $services[$service]; $start = ts($date,$time); $end = $start + $dur*60; $wd = (int)date('N',$start);
        if (!isset($business_hours[$wd])) $messages[]=['type'=>'error','text'=>'Dia fechado.'];
        else {
            $open = ts($date,$business_hours[$wd]['open']); $close = ts($date,$business_hours[$wd]['close']);
            if ($start<$open || $end>$close) $messages[]=['type'=>'error','text'=>'Fora do expediente.'];
            else {
                $conf=false; foreach ($_SESSION['bookings'] as $b) if ($b['date']===$date && overlap($start,$end,$b['start'],$b['end'])) { $conf=true; break; }
                if ($conf) $messages[]=['type'=>'error','text'=>'Horário ocupado.'];
                else { $_SESSION['bookings'][]=['id'=>uniqid('bk_'),'client'=>$client,'phone'=>$phone,'service'=>$service,'date'=>$date,'start'=>$start,'end'=>$end,'created'=>time()]; $messages[]=['type'=>'success','text'=>"Agendamento confirmado para {$date} às {$time} ({$service})."]; }
            }
        }
    }
}

$today = time(); $weekStart = strtotime('monday this week', $today);
$days = []; foreach ($display_days as $d) $days[$d]=date('Y-m-d', strtotime('this week +'.($d-1).' days', $weekStart));
$bookings = $_SESSION['bookings'];
// agrupar por data para buscas rápidas
$by_date = []; foreach ($bookings as $b) $by_date[$b['date']][] = $b;

// calcular faixa horária (min open / max close em minutos)
$minOpen = 24*60; $maxClose = 0; foreach ($display_days as $d) if (isset($business_hours[$d])) { [$oh,$oc]=[$business_hours[$d]['open'],$business_hours[$d]['close']]; $minOpen = min($minOpen,intval(substr($oh,0,2))*60+intval(substr($oh,3,2))); $maxClose = max($maxClose,intval(substr($oc,0,2))*60+intval(substr($oc,3,2))); }

