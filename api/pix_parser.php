<?php
// api/pix_parser.php

function analisarPayloadPix($pixCopiaECola) {
    $resultado = [
        'pix_original' => $pixCopiaECola,
        'gateway' => null,
        'qr_url' => null,
        'qr_identificador' => null,
    ];

    // Verifica se é Dlocal
    if (strpos($pixCopiaECola, 'qrcode.dlocal.com') !== false) {
        $resultado['gateway'] = 'DLOCAL';
        // Extrai a URL usando Regex
        preg_match('/qrcode\.dlocal\.com[^\s\d]{0,2}\/([a-zA-Z0-9\/\-]+)/', $pixCopiaECola, $matches);
        if(isset($matches[0])){
            $resultado['qr_url'] = 'https://' . $matches[0];
            $partes = explode('/', $matches[0]);
            $resultado['qr_identificador'] = end($partes);
        }
    } 
    // Verifica se é Adyen
    elseif (strpos($pixCopiaECola, 'pix.adyen.com') !== false) {
        $resultado['gateway'] = 'ADYEN';
        // Extrai a URL Adyen
        preg_match('/pix\.adyen\.com[^\s\d]{0,2}\/([a-zA-Z0-9\/\-]+)/', $pixCopiaECola, $matches);
        if(isset($matches[0])){
            $resultado['qr_url'] = 'https://' . $matches[0];
            $partes = explode('/', $matches[0]);
            $resultado['qr_identificador'] = end($partes);
        }
    }

    return $resultado;
}
?>
