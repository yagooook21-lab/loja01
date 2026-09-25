<?php
// api/verificador_pix_fb.php
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

if (!$conn) {
    exit(json_encode(["error" => "Erro DB", "msg" => "Nao conectou ao banco"]));
}

// 1. Busca todos os PIXs que estão aguardando pagamento
$sql = "SELECT id, codigo FROM pix_tabela_codigos WHERE status_pagamento IN ('RESERVADO', 'DISPONIVEL')";

// Se foi passado um ID específico via AJAX, otimiza para buscar só ele
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql .= " AND id = $id";
}

$result = mysqli_query($conn, $sql);
$pedidos = [];
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $pedidos[] = $row;
    }
}

$resultados = [];

foreach ($pedidos as $pedido) {
    $codigoPix = $pedido['codigo'];
    $urlExtraida = null;

    // 2. Extrair a URL do meio do código PIX Copia e Cola
    if (preg_match('/(qrcode\.dlocal\.com.*?|pix\.adyen\.com.*?)(5204|5303)/i', $codigoPix, $matches)) {
        $urlExtraida = 'https://' . $matches[1];
    }

    if ($urlExtraida) {
        // 3. Checa a URL secreta usando cURL
        $ch = curl_init($urlExtraida);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $foiPago = false;

        // Se deu erro (ex: 404, 410, 400) ou a resposta estiver vazia
        if ($httpCode >= 400 || $httpCode == 0 || empty($response)) {
            $foiPago = true;
        } 
        elseif (strpos(strtolower($response), 'not found') !== false || strpos(strtolower($response), 'error') !== false) {
            $foiPago = true;
        }

        if ($foiPago) {
            // 4. Marca como pago no Banco de Dados
            $pedidoId = $pedido['id'];
            $stmtUpdate = "UPDATE pix_tabela_codigos SET status_pagamento = 'PAGO', pago_em = NOW() WHERE id = $pedidoId";
            mysqli_query($conn, $stmtUpdate);
            
            $resultados[] = ["id" => $pedido['id'], "status" => "PAGO", "url_testada" => $urlExtraida];
        } else {
            $resultados[] = ["id" => $pedido['id'], "status" => "AGUARDANDO", "http_code" => $httpCode];
        }
    } else {
        $resultados[] = ["id" => $pedido['id'], "status" => "URL_NAO_ENCONTRADA"];
    }
}

echo json_encode([
    "sucesso" => true,
    "checados" => count($pedidos),
    "resultados" => $resultados
]);
?>
