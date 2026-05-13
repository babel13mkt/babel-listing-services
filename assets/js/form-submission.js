/**
 * Babel Directory — form-submission.js · Hito 13
 * Vanilla JS puro: sin jQuery, sin dependencias, Divi-safe.
 * Gestiona: multistep navigation, validación, char count, AJAX submit.
 */

(function () {
    'use strict';

    // ── Constantes ──────────────────────────────────────────────
    const STEPS_TOTAL = 3;
    const STEP_REQUIRED = {
        1: ['bd_nombre', 'bd_descripcion', 'bd_categoria', 'bd_region'],
        2: [], // paso 2 no tiene obligatorios
        3: [], // paso 3 tampoco
    };

    // ── Estado ───────────────────────────────────────────────────
    let currentStep = 1;

    // ── Referencias DOM ──────────────────────────────────────────
    const wrapper   = document.getElementById('bd-submission-wrapper');
    const form      = document.getElementById('bd-submission-form');
    const successEl = document.getElementById('bd-form-success');
    const successMsg = document.getElementById('bd-success-msg');
    const progressFill = document.getElementById('bd-progress-fill');

    if ( ! form ) return; // guard — no hacer nada si el form no existe en la página

    // ── Helpers ──────────────────────────────────────────────────

    function getStep(n) {
        return wrapper.querySelector(`.bd-form-step[data-step="${n}"]`);
    }

    function getDot(n) {
        return wrapper.querySelector(`.bd-step-dot[data-step="${n}"]`);
    }

    function setProgress(step) {
        const pct = (step / STEPS_TOTAL) * 100;
        progressFill.style.width = pct + '%';
    }

    function showStep(next) {
        const currentEl = getStep(currentStep);
        const nextEl    = getStep(next);
        if ( ! nextEl ) return;

        currentEl.classList.remove('active');
        getDot(currentStep).classList.remove('active');
        if (next > currentStep) getDot(currentStep).classList.add('done');

        currentStep = next;
        nextEl.classList.add('active');
        getDot(currentStep).classList.add('active');
        if (next <= STEPS_TOTAL) getDot(next).classList.remove('done');

        setProgress(currentStep);
        wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errEl = document.getElementById('err-' + fieldId);
        if (field)  field.classList.add('bd-invalid');
        if (errEl)  errEl.textContent = message;
    }

    function clearError(fieldId) {
        const field = document.getElementById(fieldId);
        const errEl = document.getElementById('err-' + fieldId);
        if (field)  field.classList.remove('bd-invalid');
        if (errEl)  errEl.textContent = '';
    }

    function validateStep(step) {
        const required = STEP_REQUIRED[step] || [];
        let valid = true;
        const strings = (window.bdFormConfig && window.bdFormConfig.strings) || {};
        const msgRequired = strings.required || 'Este campo es obligatorio.';

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

    // ── Navegación entre pasos ────────────────────────────────────

    wrapper.addEventListener('click', function(e) {
        // Botón "Siguiente"
        const nextBtn = e.target.closest('[data-next]');
        if (nextBtn) {
            const next = parseInt(nextBtn.dataset.next, 10);
            if ( validateStep(currentStep) ) {
                showStep(next);
            }
        }
        // Botón "Atrás"
        const backBtn = e.target.closest('[data-back]');
        if (backBtn) {
            const back = parseInt(backBtn.dataset.back, 10);
            showStep(back);
        }
    });

    // Limpiar error en input al escribir
    wrapper.addEventListener('input', function(e) {
        const el = e.target;
        if (el.id) clearError(el.id);
    });

    // ── Contador de caracteres textarea ──────────────────────────

    const textarea = document.getElementById('bd_descripcion');
    const charCount = document.getElementById('bd-desc-count');
    if (textarea && charCount) {
        textarea.addEventListener('input', function() {
            charCount.textContent = textarea.value.length;
        });
    }

    // ── Price selector: visual feedback ─────────────────────────
    // (el radio ya funciona nativo — solo añadimos feedback visual extra si se necesita)

    // ── Submit AJAX ──────────────────────────────────────────────

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if ( ! validateStep(currentStep) ) return;

        const submitBtn  = document.getElementById('bd-submit-btn');
        const btnText    = submitBtn.querySelector('.bd-btn-text');
        const btnSpinner = submitBtn.querySelector('.bd-btn-spinner');
        const config     = window.bdFormConfig || {};
        const strings    = config.strings || {};

        // Estado de carga
        submitBtn.disabled = true;
        if (btnText)    btnText.textContent = strings.sending || 'Enviando...';
        if (btnSpinner) btnSpinner.hidden = false;

        const formData = new FormData(form);
        formData.append('action', 'bd_submit_negocio');
        formData.append('nonce',  config.nonce || '');

        fetch(config.ajaxUrl || '/wp-admin/admin-ajax.php', {
            method:      'POST',
            credentials: 'same-origin',
            body:        formData,
        })
        .then(function(response) {
            if ( ! response.ok ) throw new Error('Network error: ' + response.status);
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                // Ocultar formulario, mostrar éxito
                form.hidden = true;
                wrapper.querySelector('.bd-progress-bar').hidden = true;
                if (successMsg) successMsg.textContent = data.data.message || strings.success || '¡Enviado!';
                successEl.hidden = false;
                wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                const msg = (data.data && data.data.message) ? data.data.message : (strings.error || 'Hubo un error.');
                showGlobalError(msg);
                resetSubmitBtn(submitBtn, btnText, btnSpinner);
            }
        })
        .catch(function() {
            showGlobalError(strings.error || 'Hubo un error de conexión. Por favor intentá de nuevo.');
            resetSubmitBtn(submitBtn, btnText, btnSpinner);
        });
    });

    function resetSubmitBtn(btn, text, spinner) {
        btn.disabled = false;
        if (text)    text.textContent = 'Publicar mi negocio';
        if (spinner) spinner.hidden = true;
    }

    function showGlobalError(message) {
        let errEl = document.getElementById('bd-global-error');
        if ( ! errEl ) {
            errEl = document.createElement('div');
            errEl.id = 'bd-global-error';
            errEl.style.cssText = [
                'padding:12px 16px',
                'background:#fef2f2',
                'border:1.5px solid #fca5a5',
                'border-radius:8px',
                'color:#b91c1c',
                'font-size:14px',
                'font-weight:600',
                'margin-bottom:16px',
                'text-align:center',
            ].join(';');
            const actions = getStep(currentStep).querySelector('.bd-form-actions');
            if (actions) actions.parentNode.insertBefore(errEl, actions);
        }
        errEl.textContent = message;
        errEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // ── Init ──────────────────────────────────────────────────────
    setProgress(1);

})();
