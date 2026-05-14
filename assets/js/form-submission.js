/**
 * Babel Directory — form-submission.js · Hito 23
 * Premium Navigation & AJAX Logic
 */

(function () {
    'use strict';

    const STEPS_TOTAL = 3;
    const STEP_REQUIRED = {
        1: ['bd_nombre', 'bd_descripcion', 'bd_categoria', 'bd_region'],
        2: [], 
        3: [],
    };

    let currentStep = 1;

    const wrapper   = document.getElementById('bd-submission-wrapper');
    const form      = document.getElementById('bd-submission-form');
    const successEl = document.getElementById('bd-form-success');
    const progressFill = document.getElementById('bd-progress-fill');

    if ( ! form ) return;

    function getStep(n) {
        return wrapper.querySelector(`.bd-form-step[data-step="${n}"]`);
    }

    function getDot(n) {
        return wrapper.querySelector(`.bd-step-dot[data-step="${n}"]`);
    }

    function setProgress(step) {
        // En la versión premium la barra es un segmento que se llena
        // Step 1: 0%, Step 2: 50%, Step 3: 100% (o similar)
        const pct = ((step - 1) / (STEPS_TOTAL - 1)) * 100;
        if (progressFill) progressFill.style.width = pct + '%';
    }

    function showStep(next) {
        const currentEl = getStep(currentStep);
        const nextEl    = getStep(next);
        if ( ! nextEl ) return;

        // Animación de salida sutil
        currentEl.style.opacity = '0';
        currentEl.style.transform = 'translateY(-10px)';

        setTimeout(() => {
            currentEl.classList.remove('active');
            getDot(currentStep).classList.remove('active');
            
            if (next > currentStep) {
                getDot(currentStep).classList.add('done');
            } else {
                getDot(next).classList.remove('done');
            }

            currentStep = next;
            nextEl.classList.add('active');
            getDot(currentStep).classList.add('active');
            
            setProgress(currentStep);
            
            // Scroll suave
            const headerOffset = 100;
            const elementPosition = wrapper.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }, 300);
    }

    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errEl = document.getElementById('err-' + fieldId);
        if (field) field.classList.add('bd-invalid');
        if (errEl) errEl.textContent = message;
    }

    function clearError(fieldId) {
        const field = document.getElementById(fieldId);
        const errEl = document.getElementById('err-' + fieldId);
        if (field) field.classList.remove('bd-invalid');
        if (errEl) errEl.textContent = '';
    }

    function validateStep(step) {
        const required = STEP_REQUIRED[step] || [];
        let valid = true;
        const config = window.bdFormConfig || {};
        const msgRequired = (config.strings && config.strings.required) || 'Este campo es obligatorio.';

        required.forEach(function(id) {
            const el = document.getElementById(id);
            if ( ! el ) return;
            clearError(id);
            const val = el.tagName === 'SELECT' ? el.value : el.value.trim();
            if ( ! val ) {
                showError(id, msgRequired);
                valid = false;
            }
        });
        return valid;
    }

    // ── Event Listeners ──────────────────────────────────────────

    wrapper.addEventListener('click', function(e) {
        const nextBtn = e.target.closest('[data-next]');
        if (nextBtn) {
            const next = parseInt(nextBtn.dataset.next, 10);
            if ( validateStep(currentStep) ) {
                showStep(next);
            }
        }

        const backBtn = e.target.closest('[data-back]');
        if (backBtn) {
            const back = parseInt(backBtn.dataset.back, 10);
            showStep(back);
        }
    });

    wrapper.addEventListener('input', function(e) {
        if (e.target.id) clearError(e.target.id);
    });

    // ── Submit Logic ──────────────────────────────────────────────

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if ( ! validateStep(currentStep) ) return;

        const submitBtn = document.getElementById('bd-submit-btn');
        const config    = window.bdFormConfig || {};
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="bd-btn-spinner"></span> Enviando...';

        const formData = new FormData(form);
        formData.append('action', 'bd_submit_negocio');
        formData.append('nonce', config.nonce || '');

        fetch(config.ajaxUrl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Éxito Premium
                form.style.display = 'none';
                wrapper.querySelector('.bd-progress-bar').style.display = 'none';
                successEl.style.display = 'block';
                window.scrollTo({ top: wrapper.offsetTop - 50, behavior: 'smooth' });
            } else {
                alert(data.data.message || 'Error al enviar.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Publicar Negocio';
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de conexión.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Publicar Negocio';
        });
    });

    // Init progress
    setProgress(1);

})();
