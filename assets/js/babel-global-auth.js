/**
 * Global Auth Script para Babel Directory
 * Maneja el modal y el token de Google Sign-In en cualquier página.
 */

// Apertura y cierre del Modal
function openBabelAuthModal() {
    const modal = document.getElementById('babel-auth-modal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.style.visibility = 'visible';
        modal.style.opacity = '1';
        modal.style.pointerEvents = 'auto';
        
        const container = modal.querySelector('.babel-modal-container');
        if (container) {
            container.style.transform = 'translateY(0)';
        }
        
        // Prevenir scroll de fondo
        document.body.style.overflow = 'hidden';
        
        // Renderizar el botón explícitamente vía JS si Google falló en renderizarlo vía HTML
        if (typeof google !== 'undefined' && google.accounts && google.accounts.id) {
            const btnContainer = document.querySelector('.g_id_signin');
            const confElement = document.getElementById('g_id_onload_global');
            
            // Si el botón no tiene hijos (el iframe de Google), lo forzamos a dibujar
            if (btnContainer && btnContainer.children.length === 0 && confElement) {
                const clientId = confElement.getAttribute('data-client_id');
                google.accounts.id.initialize({
                    client_id: clientId,
                    callback: handleGlobalBabelGoogleLogin
                });
                google.accounts.id.renderButton(
                    btnContainer,
                    { theme: "outline", size: "large", width: 350, text: "continue_with" }
                );
            }
        }
    }
}

function closeBabelAuthModal() {
    const modal = document.getElementById('babel-auth-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.visibility = 'hidden';
        modal.style.opacity = '0';
        modal.style.pointerEvents = 'none';
        
        const container = modal.querySelector('.babel-modal-container');
        if (container) {
            container.style.transform = 'translateY(20px)';
        }
        
        document.body.style.overflow = '';
    }
}

// Cerrar modal si hacen click fuera del contenedor
document.addEventListener('click', function(e) {
    const modal = document.getElementById('babel-auth-modal');
    if (modal && e.target === modal) {
        closeBabelAuthModal();
    }
});

// Callback global de Google Sign-In
function handleGlobalBabelGoogleLogin(response) {
    if (!response || !response.credential) {
        alert('Ocurrió un error con Google. Intenta de nuevo.');
        return;
    }

    // Mostrar UI de carga
    const btnContainer = document.getElementById('babel-global-google-btn-container');
    const loading = document.getElementById('babel-global-login-loading');
    
    if (btnContainer) btnContainer.style.display = 'none';
    if (loading) loading.style.display = 'block';

    const nonce = typeof babel_vars !== 'undefined' ? babel_vars.google_login_nonce : '';
    const ajaxUrl = typeof babel_vars !== 'undefined' ? babel_vars.ajaxUrl : '/wp-admin/admin-ajax.php';

    // Preparar formData
    const formData = new FormData();
    formData.append('action', 'babel_google_login');
    formData.append('security', nonce);
    formData.append('credential', response.credential);

    // Fetch API
    fetch(ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Recargar la página automáticamente para que Divi actualice el menú superior
            // y las cookies de sesión se apliquen.
            window.location.reload();
        } else {
            alert('Error al iniciar sesión: ' + (data.data?.message || 'Token inválido.'));
            if (btnContainer) btnContainer.style.display = 'block';
            if (loading) loading.style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Error Google Auth:', error);
        alert('Ocurrió un error de red. Intenta nuevamente.');
        if (btnContainer) btnContainer.style.display = 'block';
        if (loading) loading.style.display = 'none';
    });
}

// MSAL Microsoft Login
let msalInstance = null;

function initMicrosoftAuth() {
    const msBtn = document.getElementById('babel-microsoft-login-btn');
    if (!msBtn) return;
    
    const clientId = msBtn.getAttribute('data-client_id');
    if (!clientId || typeof msal === 'undefined') return;

    const msalConfig = {
        auth: {
            clientId: clientId,
            authority: 'https://login.microsoftonline.com/common',
            navigateToLoginRequestUrl: false
        },
        cache: {
            cacheLocation: "sessionStorage",
            storeAuthStateInCookie: false,
        }
    };

    msalInstance = new msal.PublicClientApplication(msalConfig);
    
    msBtn.addEventListener('click', function() {
        const btnGoogle = document.getElementById('babel-global-google-btn-container');
        const loading = document.getElementById('babel-global-login-loading');
        
        if (btnGoogle) btnGoogle.style.display = 'none';
        msBtn.style.display = 'none';
        if (loading) loading.style.display = 'block';

        msalInstance.loginPopup({
            scopes: ["user.read", "openid", "profile", "email"],
            prompt: "select_account"
        }).then(response => {
            handleGlobalBabelMicrosoftLogin(response.accessToken);
        }).catch(error => {
            console.error(error);
            if (btnGoogle) btnGoogle.style.display = 'flex';
            msBtn.style.display = 'flex';
            if (loading) loading.style.display = 'none';
        });
    });
}

function handleGlobalBabelMicrosoftLogin(idToken) {
    if (!idToken) {
        alert('Ocurrió un error con Microsoft. Intenta de nuevo.');
        return;
    }

    const nonce = typeof babel_vars !== 'undefined' ? babel_vars.microsoft_login_nonce : '';
    const ajaxUrl = typeof babel_vars !== 'undefined' ? babel_vars.ajaxUrl : '/wp-admin/admin-ajax.php';

    const formData = new FormData();
    formData.append('action', 'babel_microsoft_login');
    formData.append('security', nonce);
    formData.append('id_token', idToken);

    fetch(ajaxUrl, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Error al iniciar sesión: ' + (data.data?.message || 'Token inválido.'));
            window.location.reload(); // Recargar para restaurar estado visual si falló
        }
    })
    .catch(error => {
        console.error('Error MS Auth:', error);
        alert('Ocurrió un error de red.');
        window.location.reload();
    });
}

// Auto-abrir el modal si el usuario no está logueado
document.addEventListener('DOMContentLoaded', function() {
    initMicrosoftAuth();
    
    const modal = document.getElementById('babel-auth-modal');
    // Si el modal existe, significa que is_user_logged_in() devolvió false en PHP
    if (modal) {
        // Esperamos 1.5 segundos para no bloquear el renderizado inicial y "romper las pelotas"
        setTimeout(function() {
            openBabelAuthModal();
        }, 1500);
    }
});
