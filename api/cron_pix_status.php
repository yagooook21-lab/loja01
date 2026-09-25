<?php
// api/cron_pix_status.php
require_once 'pix_parser.php';

// Configuração de banco de dados
$host = 'localhost';
$db   = 'banco_unificado'; 
$user = 'root'; 
$pass = ''; 
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Credenciais (Preencher com as de produção)
$adyen_api_key = "SUA_API_KEY_ADYEN";
$dlocal_api_key = "SUA_API_KEY_DLOCAL";

// Buscar PIXs que ainda estão aguardando pagamento
$stmt = $pdo->query("SELECT id, codigo FROM pix_tabela_codigos WHERE status_pagamento = 'RESERVADO'");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($pedidos as $pedido) {
    // Passa pelo Parser para identificar a URL
    $dadosPix = analisarPayloadPix($pedido['codigo']);
    
    // Atualiza a tabela com os dados extraídos, se ainda não tiverem sido salvos
    if (!empty($dadosPix['gateway'])) {
        $updateMeta = $pdo->prepare("UPDATE pix_tabela_codigos SET pix_original=?, qr_url=?, qr_identificador=?, gateway=? WHERE id=?");
        $updateMeta->execute([$dadosPix['pix_original'], $dadosPix['qr_url'], $dadosPix['qr_identificador'], $dadosPix['gateway'], $pedido['id']]);
    }

    $statusFinal = null;

    if ($dadosPix['gateway'] == 'ADYEN') {
        // Exemplo simplificado de CURL para a Adyen API
        // A url exata de verificação depende de como você criou o PIX (checkout api)
        // Aqui checamos via endpoint de pagamentos
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://checkout-test.adyen.com/v68/payments/" . $dadosPix['qr_identificador']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-API-key: $adyen_api_key"]);
        $resposta = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        if (isset($resposta['status']) && $resposta['status'] == 'Authorised') {
            $statusFinal = 'PAGO';
        }
    } 
    elseif ($dadosPix['gateway'] == 'DLOCAL') {
        // Exemplo simplificado de CURL para DLOCAL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.dlocal.com/payments/" . $dadosPix['qr_identificador']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $dlocal_api_key"]);
        $resposta = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        if (isset($resposta['status']) && $resposta['status'] == 'PAID') {
            $statusFinal = 'PAGO';
        }
    }

    // Se a API informou que foi pago, corrige o banco
    if ($statusFinal === 'PAGO') {
        $stmtUpdate = $pdo->prepare("UPDATE pix_tabela_codigos SET status_pagamento = 'PAGO', pago_em = NOW() WHERE id = ?");
        $stmtUpdate->execute([$pedido['id']]);
        echo "Pedido ID {$pedido['id']} marcado como PAGO.\n";
    }
}
echo "Cron executado com sucesso.";
?>
