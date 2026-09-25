<?php
// update_db_adyen.php
require_once __DIR__ . '/api/db.php'; // Usa o arquivo base de DB do sistema

// O arquivo db.php já expõe a variável $conn (mysqli)
if (!$conn) {
    die("Erro na conexão com o banco de dados.");
}

$sql = "ALTER TABLE `pix_tabela_codigos` 
        ADD COLUMN IF NOT EXISTS `pix_original` TEXT NULL,
        ADD COLUMN IF NOT EXISTS `qr_url` VARCHAR(255) NULL,
        ADD COLUMN IF NOT EXISTS `qr_identificador` VARCHAR(255) NULL,
        ADD COLUMN IF NOT EXISTS `txid` VARCHAR(100) NULL,
        ADD COLUMN IF NOT EXISTS `payment_id` VARCHAR(100) NULL,
        ADD COLUMN IF NOT EXISTS `gateway` VARCHAR(50) NULL;";
        
if (mysqli_query($conn, $sql)) {
    echo "Colunas adicionadas com sucesso na tabela pix_tabela_codigos!";
} else {
    echo "Erro ao atualizar o banco: " . mysqli_error($conn);
}
?>
