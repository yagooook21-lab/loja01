<?php
// update_db_adyen.php
require_once __DIR__ . '/api/db.php'; // Usa o arquivo base de DB do sistema

// O arquivo db.php já expõe a variável $conn (mysqli)
if (!$conn) {
    die("Erro na conexão com o banco de dados.");
}

$colunas = [
    "ADD COLUMN `pix_original` TEXT NULL",
    "ADD COLUMN `qr_url` VARCHAR(255) NULL",
    "ADD COLUMN `qr_identificador` VARCHAR(255) NULL",
    "ADD COLUMN `txid` VARCHAR(100) NULL",
    "ADD COLUMN `payment_id` VARCHAR(100) NULL",
    "ADD COLUMN `gateway` VARCHAR(50) NULL"
];

$sucesso = true;

foreach ($colunas as $coluna) {
    $sql = "ALTER TABLE `pix_tabela_codigos` $coluna";
    if (!mysqli_query($conn, $sql)) {
        // Ignora o erro 1060 (Duplicate column name)
        if (mysqli_errno($conn) != 1060) {
            echo "Erro ao adicionar coluna: " . mysqli_error($conn) . "<br>";
            $sucesso = false;
        }
    }
}

if ($sucesso) {
    echo "Colunas adicionadas/verificadas com sucesso na tabela pix_tabela_codigos!";
}
?>
