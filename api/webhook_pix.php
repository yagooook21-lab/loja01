<?php
// api/webhook_pix.php
$payload = file_get_contents('php://input');
$dados = json_decode($payload, true);

if (!$dados) {
    http_response_code(400);
    exit("Payload Invalido");
}

// Configuração de banco de dados
$host = 'localhost';
$db   = 'banco_unificado'; 
$user = 'root'; 
$pass = ''; 
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$gateway = '';
$qr_identificador = '';
$status = 'PENDENTE';

// 1. Identificar se é Webhook da ADYEN
if (isset($dados['notificationItems'][0]['NotificationRequestItem'])) {
    $item = $dados['notificationItems'][0]['NotificationRequestItem'];
    $gateway = 'ADYEN';
    $qr_identificador = $item['pspReference']; // A Adyen geralmente usa pspReference
    
    if ($item['eventCode'] === 'AUTHORISATION' && $item['success'] === 'true') {
        $status = 'PAGO';
    }
} 
// 2. Identificar se é Webhook da DLOCAL
elseif (isset($dados['status']) && isset($dados['id'])) {
    $gateway = 'DLOCAL';
    $qr_identificador = $dados['id'];
    
    if ($dados['status'] === 'PAID') {
        $status = 'PAGO';
    }
}

// 3. Se foi pago, atualizar a tabela
if ($status === 'PAGO' && !empty($qr_identificador)) {
    // Atualiza onde o qr_identificador ou payment_id batem com o que foi recebido
    $sql = "UPDATE pix_tabela_codigos 
            SET status_pagamento = 'PAGO', 
                pago_em = NOW()
            WHERE qr_identificador = :id OR payment_id = :id";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $qr_identificador]);
}

// Retornar 200 OK (Importante para Webhooks)
echo json_encode(["status" => "ok"]);
?>
