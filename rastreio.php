<?php
session_start();
require_once __DIR__ . '/api/db.php';

$produto_codigo = trim((string)($_GET['produto'] ?? ''));
if ($produto_codigo === '') {
    header('Location: ./index');
    exit;
}
$produto_safe = mysqli_real_escape_string($conn, $produto_codigo);
$produto_result = mysqli_query($conn, "SELECT * FROM produto WHERE codigo='$produto_safe' LIMIT 1");
$produto = $produto_result ? mysqli_fetch_assoc($produto_result) : null;
if (!$produto) {
    header('Location: ./index');
    exit;
}

$ip = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '');
$pix_result = mysqli_query($conn, "SELECT * FROM pixgerado WHERE ip='$ip' AND produto='$produto_safe' ORDER BY id DESC LIMIT 1");
$pix = $pix_result ? mysqli_fetch_assoc($pix_result) : null;
$cliente_ip = mysqli_real_escape_string($conn, base64_encode($_SERVER['REMOTE_ADDR'] ?? ''));
$cliente_result = mysqli_query($conn, "SELECT * FROM clientes WHERE ip='$cliente_ip' ORDER BY id DESC LIMIT 1");
$cliente = $cliente_result ? mysqli_fetch_assoc($cliente_result) : [];

$config_result = mysqli_query($conn, "SELECT * FROM config LIMIT 1");
$config = $config_result ? mysqli_fetch_assoc($config_result) : [];
$nome_loja = $config['nome'] ?? 'Minha Loja';
$cor = $config['cor'] ?? '#ffe600';
$logo_files = glob(__DIR__ . '/arquivos/logo/*.png');
$logo = !empty($logo_files) ? 'arquivos/logo/' . basename($logo_files[0]) : '';

$gateway_status = strtoupper(trim((string)($pix['mp_status'] ?? $pix['freepay_status'] ?? $pix['pixgo_status'] ?? $pix['carthero_status'] ?? '')));
$paid_statuses = ['APPROVED', 'PAID', 'PAGO', 'COMPLETED', 'RECEIVED', 'CONFIRMED', 'SUCCEEDED', 'SETTLED'];
$is_paid = in_array($gateway_status, $paid_statuses, true) || strtoupper((string)($pix['status'] ?? '')) === 'PAGO' || (string)($_GET['confirmado'] ?? '') === '1';
$status_label = $is_paid ? 'Pagamento Confirmado' : 'Aguardando Pagamento';
$created_at = !empty($pix['data_criacao']) ? strtotime($pix['data_criacao']) : time();

$data_entrega = new DateTime();
$data_entrega->setTimestamp($created_at);
$dias_uteis = 0;
while ($dias_uteis < 5) {
    $data_entrega->modify('+1 day');
    if ((int)$data_entrega->format('N') <= 5) $dias_uteis++;
}
$dias_semana_pt = ['', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado', 'domingo'];
$meses_pt = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
$delivery_date = $dias_semana_pt[(int)$data_entrega->format('N')] . ', ' . $data_entrega->format('j') . ' de ' . $meses_pt[(int)$data_entrega->format('n')];
$nome_produto = $produto['nome'] ?? 'Produto';
$valor_texto = trim((string)($pix['valor'] ?? $produto['valor'] ?? 0));
if (strpos($valor_texto, ',') !== false) {
    $valor_texto = str_replace('.', '', $valor_texto);
    $valor_texto = str_replace(',', '.', $valor_texto);
}
$valor = (float)$valor_texto;
$valor_formatado = number_format($valor, 2, ',', '.');
$destino = trim((string)($cliente['cidade'] ?? ''));
if (!empty($cliente['estado'])) $destino .= ($destino ? ', ' : '') . $cliente['estado'];
if ($destino === '') $destino = 'Seu endereço de entrega';
$etapas = $is_paid ? 1 : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Acompanhar pedido - <?php echo htmlspecialchars($nome_loja); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{box-sizing:border-box}body{margin:0;background:#ebebeb;color:#333;font-family:Arial,Helvetica,sans-serif}.header{background:<?php echo htmlspecialchars($cor); ?>;border-bottom:1px solid rgba(0,0,0,.1)}.header-inner{max-width:1180px;margin:auto;min-height:64px;padding:12px 24px;display:flex;justify-content:space-between;align-items:center}.logo{max-width:180px;max-height:40px;object-fit:contain}.brand{font-weight:600;font-size:20px}.secure{font-size:13px;color:#555}.wrap{max-width:1180px;margin:0 auto;padding:32px 16px 60px;display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:24px}.card{background:#fff;border:1px solid #ddd;border-radius:8px;box-shadow:0 1px 2px rgba(0,0,0,.06);overflow:hidden}.status-head{padding:30px 32px 16px;display:flex;gap:18px}.status-icon{width:58px;height:58px;border-radius:50%;background:#e8f5e9;color:#00a650;display:flex;align-items:center;justify-content:center;font-size:28px;flex:none}.status-title{margin:0;color:#00a650;font-size:28px;line-height:1.2}.status-label{font-weight:600;margin:8px 0;color:#333}.guarantee{color:#666;font-size:14px;margin-top:18px}.progress-area{padding:12px 32px 30px}.bar{height:8px;border-radius:20px;background:#f1f1f1;overflow:hidden}.bar span{display:block;height:100%;width:50%;background:#00a650;border-radius:20px}.steps{display:flex;justify-content:space-between;gap:10px;margin-top:12px;color:#666;font-size:11px;font-weight:bold;text-transform:uppercase}.steps span:first-child,.steps span:nth-child(2){color:#333}.history{padding:28px 32px}.history h2,.side h2{font-size:20px;margin:0 0 26px}.event{position:relative;padding:0 0 28px 38px;border-left:2px solid #eee;margin-left:8px}.event:last-child{padding-bottom:0;border-left-color:transparent}.dot{position:absolute;left:-9px;top:2px;width:16px;height:16px;border-radius:50%;background:#ddd;border:3px solid #fff;box-shadow:0 0 0 1px #ddd}.event:first-of-type .dot{background:#00a650;box-shadow:0 0 0 1px #00a650}.event h3{font-size:17px;margin:0 0 6px}.event p{font-size:14px;color:#666;margin:0;line-height:1.4}.event time{display:inline-block;margin-top:9px;color:#999;background:#f8f8f8;padding:5px 8px;border-radius:4px;font-size:11px;font-weight:bold}.side{padding:24px}.product{display:flex;gap:14px;padding-bottom:20px;border-bottom:1px solid #eee}.product-icon{width:56px;height:56px;border-radius:7px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;color:#aaa;font-size:24px}.product-name{font-size:14px;font-weight:600}.product-meta{font-size:12px;color:#666;margin-top:5px}.total{display:flex;justify-content:space-between;align-items:center;margin-top:20px}.total strong{font-size:23px}.delivery{margin-top:24px;padding-top:20px;border-top:1px solid #eee;font-size:14px;line-height:1.5}.delivery b{display:block;margin-bottom:5px}.footer{padding:24px 16px;background:#fff;border-top:1px solid #ddd;text-align:center;color:#777;font-size:12px}.footer a{color:#666;margin:0 8px;text-decoration:none}@media(max-width:800px){.wrap{grid-template-columns:1fr;padding:20px 10px 40px}.status-head{padding:24px 20px 12px}.progress-area,.history{padding-left:20px;padding-right:20px}.status-title{font-size:23px}.header-inner{padding:10px 16px}.secure{font-size:11px}.steps{font-size:9px}}
</style>
</head>
<body>
<header class="header"><div class="header-inner"><div><?php if ($logo): ?><img class="logo" src="<?php echo htmlspecialchars($logo); ?>" alt="<?php echo htmlspecialchars($nome_loja); ?>"><?php else: ?><span class="brand"><?php echo htmlspecialchars($nome_loja); ?></span><?php endif; ?></div><div class="secure"><i class="fa-solid fa-shield-halved"></i> Compra segura</div></div></header>
<main class="wrap">
<section class="card">
<div class="status-head"><div class="status-icon"><i class="fa-solid <?php echo $is_paid ? 'fa-truck' : 'fa-clock'; ?>"></i></div><div><h1 class="status-title"><?php echo $is_paid ? 'Chega dia ' . htmlspecialchars($delivery_date) : 'Aguardando pagamento'; ?></h1><p class="status-label">Status: <?php echo htmlspecialchars($status_label); ?></p><p class="guarantee"><i class="fa-solid fa-shield-halved" style="color:#00a650"></i> Compra Garantida</p></div></div>
<div class="progress-area"><div class="bar"><span></span></div><div class="steps"><span>Pedido realizado</span><span>Pagamento</span><span>Enviado</span><span>Entregue</span></div></div>
<div class="history"><h2><i class="fa-solid fa-clock-rotate-left" style="color:#3483fa"></i> Histórico de movimentação</h2>
<div class="event"><span class="dot"></span><h3><?php echo $is_paid ? 'Preparando envio' : 'Pedido solicitado'; ?></h3><p><?php echo $is_paid ? 'O vendedor está preparando o seu pacote.' : 'O pedido foi registrado em nosso sistema.'; ?></p><time><?php echo date('d/m/Y', $created_at); ?></time></div>
<?php if ($is_paid): ?><div class="event"><span class="dot"></span><h3>Pedido solicitado</h3><p>O pedido foi registrado em nosso sistema.</p><time><?php echo date('d/m/Y', $created_at); ?></time></div><?php endif; ?>
</div></section>
<aside class="card side"><h2>Produtos</h2><div class="product"><div class="product-icon"><i class="fa-solid fa-box"></i></div><div><div class="product-name"><?php echo htmlspecialchars($nome_produto); ?></div><div class="product-meta">1 unidade</div></div></div><div class="total"><span style="font-size:13px;font-weight:bold;color:#777;text-transform:uppercase">Total</span><strong>R$ <?php echo $valor_formatado; ?></strong></div><div class="delivery"><b>Informações de entrega</b><span><?php echo htmlspecialchars($destino); ?></span><br><span>Previsão: <?php echo htmlspecialchars($delivery_date); ?></span></div></aside>
</main>
<footer class="footer"><a href="politica-de-privacidade">Política de Privacidade</a><a href="termos-de-uso">Termos de Uso</a><a href="trocas-e-devolucoes">Trocas e Devoluções</a><p>Copyright © <?php echo date('Y'); ?> <?php echo htmlspecialchars($nome_loja); ?>. Todos os direitos reservados.</p></footer>
</body></html>
