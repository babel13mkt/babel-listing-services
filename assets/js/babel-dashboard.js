document.addEventListener('DOMContentLoaded', () => {
    
    const triggers = document.querySelectorAll('.babel-trigger-upgrade');
    const modal = document.getElementById('babel-pricing-modal');
    const closeBtns = document.querySelectorAll('.babel-close-modal');
    const buyBtns = document.querySelectorAll('.babel-buy-btn');

    let currentPostId = null;

    // Abrir Modal
    triggers.forEach(btn => {
        btn.addEventListener('click', (e) => {
            currentPostId = btn.getAttribute('data-post-id');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden'; // Evitar scroll
        });
    });

    // Cerrar Modal
    closeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        });
    });

    // Comprar (AJAX a WooCommerce)
    buyBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!currentPostId) return;

            const sku = btn.getAttribute('data-sku');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = `<div class="inline-block w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></div> Procesando...`;
            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'babel_upgrade_plan');
            formData.append('nonce', babel_dash_vars.nonce);
            formData.append('plan_sku', sku);
            formData.append('post_id', currentPostId);

            fetch(babel_dash_vars.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.checkout_url) {
                    window.location.href = data.data.checkout_url;
                } else {
                    alert('Error: ' + (data.data?.message || 'No se pudo procesar la solicitud.'));
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
    });

});
