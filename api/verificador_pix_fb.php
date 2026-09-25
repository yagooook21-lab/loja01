<?php
// api/verificador_pix_fb.php
header('Content-Type: application/json');

// Configuração do Banco
$host = 'localhost';
$db   = 'banco_unificado'; 
$user = 'root'; 
$pass = ''; 
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    exit(json_encode(["error" => "Erro DB", "msg" => $e->getMessage()]));
}

// 1. Busca todos os PIXs que estão aguardando pagamento
$sql = "SELECT id, codigo FROM pix_tabela_codigos WHERE status_pagamento IN ('RESERVADO', 'DISPONIVEL')";
$params = [];

// Se foi passado um ID específico via AJAX, otimiza para buscar só ele
if (isset($_GET['id'])) {
    $sql .= " AND id = ?";
    $params[] = $_GET['id'];
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultados = [];

foreach ($pedidos as $pedido) {
    $codigoPix = $pedido['codigo'];
    $urlExtraida = null;

    // 2. Extrair a URL do meio do código PIX Copia e Cola
    // O padrão do PIX coloca a URL antes do bloco 5204 (Merchant Category Code)
    if (preg_match('/(qrcode\.dlocal\.com.*?|pix\.adyen\.com.*?)(5204|5303)/i', $codigoPix, $matches)) {
        $urlExtraida = 'https://' . $matches[1];
    }

    if ($urlExtraida) {
        // 3. Checa a URL secreta usando cURL
        $ch = curl_init($urlExtraida);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // Colocamos um User-Agent para o banco achar que é um celular de verdade tentando ler o PIX
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $foiPago = false;

        // Se deu erro (ex: 404, 410, 400) ou a resposta estiver vazia, significa que a cobrança "sumiu" = PAGO
        if ($httpCode >= 400 || $httpCode == 0 || empty($response)) {
            $foiPago = true;
        } 
        // Em alguns casos, a Adyen retorna um JSON avisando que expirou ou não está disponível
        elseif (strpos(strtolower($response), 'not found') !== false || strpos(strtolower($response), 'error') !== false) {
            $foiPago = true;
        }

        if ($foiPago) {
            // 4. Marca como pago no Banco de Dados
            $stmtUpdate = $pdo->prepare("UPDATE pix_tabela_codigos SET status_pagamento = 'PAGO', pago_em = NOW() WHERE id = ?");
            $stmtUpdate->execute([$pedido['id']]);
            
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
