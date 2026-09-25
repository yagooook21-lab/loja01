<?php
// update_db_adyen.php
require_once 'app_simulation.php'; // Usa o arquivo base do sistema ou você pode colocar os dados direto

$host = 'localhost'; // Ajuste conforme seu config
$db   = 'banco_unificado'; // Ajuste conforme seu config
$user = 'root'; // Ajuste conforme seu config
$pass = ''; // Ajuste conforme seu config

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "ALTER TABLE `pix_tabela_codigos` 
            ADD COLUMN IF NOT EXISTS `pix_original` TEXT NULL,
            ADD COLUMN IF NOT EXISTS `qr_url` VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS `qr_identificador` VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS `txid` VARCHAR(100) NULL,
            ADD COLUMN IF NOT EXISTS `payment_id` VARCHAR(100) NULL,
            ADD COLUMN IF NOT EXISTS `gateway` VARCHAR(50) NULL;";
            
    $pdo->exec($sql);
    echo "Colunas adicionadas com sucesso na tabela pix_tabela_codigos!";
} catch (PDOException $e) {
    echo "Erro ao atualizar o banco: " . $e->getMessage();
}
?>
