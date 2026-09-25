// js/pix_polling.js
// Coloque este código na página onde o QR Code do PIX é exibido (checkout.php ou payment.php)

/**
 * Função para verificar o status do PIX via AJAX
 * @param {number} idDoPedido - O ID do pedido na tabela pix_tabela_codigos
 * @param {string} urlRedirecionamento - Para onde o cliente vai após o pagamento
 */
function iniciarPollingPix(idDoPedido, urlRedirecionamento = 'success.php') {
    const intervalo = setInterval(() => {
        fetch(`api/verificador_pix_fb.php?id=${idDoPedido}`)
            .then(response => response.json())
            .then(data => {
                if (data.sucesso && data.resultados.length > 0) {
                    const status = data.resultados[0].status;
                    
                    if (status === 'PAGO') {
                        clearInterval(intervalo);
                        // Efeito visual opcional
                        alert('Pagamento Confirmado! Redirecionando...'); 
                        // Redireciona o cliente
                        window.location.href = urlRedirecionamento;
                    }
                }
            })
            .catch(error => console.error("Erro ao checar PIX:", error));
    }, 5000); // 5000 = Verifica a cada 5 segundos
}

// EXEMPLO DE USO (Você deve chamar isso quando a página carrega):
// iniciarPollingPix(123, 'success.php'); // Substitua 123 pelo ID real do pedido sendo pago
