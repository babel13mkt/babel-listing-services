/**
 * bd-frontend-registration.js
 * v8.2.0 — Multi-step business registration wizard.
 * Vanilla JS only — no jQuery.
 */

(function () {
	'use strict';

	// ══════════════════════════════════════════════════════════════
	// DOM REFERENCES
	// ══════════════════════════════════════════════════════════════
	const wrapper      = document.getElementById('bd-registration-wrapper');
	if (!wrapper) return; // Shortcode not present.

	const form         = document.getElementById('bd-reg-form');
	const steps        = Array.from(wrapper.querySelectorAll('.bd-reg__step'));
	const prevBtn      = document.getElementById('bd-prev-btn');
	const nextBtn      = document.getElementById('bd-next-btn');
	const submitBtn    = document.getElementById('bd-submit-btn');
	const responseDiv  = document.getElementById('bd-reg-response');
	const progressFill = wrapper.querySelector('.bd-reg__progress-fill');
	const stepCurrent  = wrapper.querySelector('.bd-reg__step-current');

	const TOTAL_STEPS  = steps.length;
	let currentStep    = 0; // zero-based index

	// ══════════════════════════════════════════════════════════════
	// STEP NAVIGATION
	// ══════════════════════════════════════════════════════════════

	function showStep(index) {
		steps.forEach((s, i) => {
			s.hidden = i !== index;
			s.classList.toggle('bd-reg__step--active', i === index);
		});
		currentStep = index;

		// Progress bar.
		const pct = ((index + 1) / TOTAL_STEPS) * 100;
		if (progressFill) progressFill.style.width = pct + '%';
		if (stepCurrent)  stepCurrent.textContent = index + 1;

		// Buttons.
		prevBtn.hidden     = index === 0;
		nextBtn.hidden     = index === TOTAL_STEPS - 1;
		submitBtn.hidden   = index !== TOTAL_STEPS - 1;

		// Scroll to top of form.
		wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
	}

	prevBtn.addEventListener('click', () => {
		if (currentStep > 0) showStep(currentStep - 1);
	});

	nextBtn.addEventListener('click', () => {
		if (validateStep(currentStep)) {
			if (currentStep < TOTAL_STEPS - 1) showStep(currentStep + 1);
		}
	});

	// ══════════════════════════════════════════════════════════════
	// VALIDATION
	// ══════════════════════════════════════════════════════════════

	function validateStep(index) {
		const step  = steps[index];
		const fields = step.querySelectorAll('input[required], select[required], textarea[required]');
		let valid = true;

		fields.forEach(field => {
			clearError(field);
			if (!field.value.trim()) {
				valid = false;
				showError(field, bd_reg_vars.strings.required);
			} else if (field.type === 'email' && !isValidEmail(field.value)) {
				valid = false;
				showError(field, bd_reg_vars.strings.invalid_email);
			} else if (field.type === 'url' && !isValidUrl(field.value)) {
				valid = false;
				showError(field, bd_reg_vars.strings.invalid_url);
			}
		});

		return valid;
	}

	function showError(input, message) {
		input.classList.add('bd-reg__input--error');
		const fieldWrapper = input.closest('.bd-reg__field');
		if (fieldWrapper) {
			const errorSpan = fieldWrapper.querySelector('.bd-reg__error');
			if (errorSpan) {
				errorSpan.textContent = message;
				errorSpan.hidden = false;
			}
		}
		input.addEventListener('input', function handler() {
			clearError(input);
			input.removeEventListener('input', handler);
		}, { once: true });
	}

	function clearError(input) {
		input.classList.remove('bd-reg__input--error');
		const fieldWrapper = input.closest('.bd-reg__field');
		if (fieldWrapper) {
			const errorSpan = fieldWrapper.querySelector('.bd-reg__error');
			if (errorSpan) errorSpan.hidden = true;
		}
	}

	function isValidEmail(v) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
	}

	function isValidUrl(v) {
		try { new URL(v); return true; } catch { return v.startsWith('localhost'); }
	}

	// ══════════════════════════════════════════════════════════════
	// STEP 3 — LEAFLET MAP
	// ══════════════════════════════════════════════════════════════
	let map = null, mapMarker = null;

	const gpsBtn       = document.getElementById('bd-gps-btn');
	const mapEl        = document.getElementById('bd-map');
	const latInput     = document.getElementById('bd_lat');
	const lngInput     = document.getElementById('bd_lng');
	const latDisplay   = document.getElementById('bd_lat_display');
	const lngDisplay   = document.getElementById('bd_lng_display');

	function initMap(lat, lng) {
		if (typeof L === 'undefined') return;
		if (map) { map.remove(); map = null; mapMarker = null; }

		map = L.map('bd-map').setView([lat, lng], 15);
		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; OpenStreetMap contributors',
		}).addTo(map);

		mapMarker = L.marker([lat, lng], { draggable: true }).addTo(map);
		mapMarker.on('dragend', function (e) {
			const pos = e.target.getLatLng();
			setCoords(pos.lat, pos.lng);
		});

		setTimeout(() => map.invalidateSize(), 300);
	}

	function setCoords(lat, lng) {
		const latF = parseFloat(lat).toFixed(7);
		const lngF = parseFloat(lng).toFixed(7);
		latInput.value = latF;
		lngInput.value = lngF;
		if (latDisplay) latDisplay.value = latF;
		if (lngDisplay) lngDisplay.value = lngF;
	}

	if (gpsBtn) {
		gpsBtn.addEventListener('click', () => {
			if (!navigator.geolocation) {
				alert(bd_reg_vars.strings.gps_not_supported);
				return;
			}
			gpsBtn.disabled = true;
			gpsBtn.textContent = '⏳ ' + bd_reg_vars.strings.submitting;

			navigator.geolocation.getCurrentPosition(
				pos => {
					const { latitude, longitude } = pos.coords;
					setCoords(latitude, longitude);
					initMap(latitude, longitude);
					gpsBtn.disabled = false;
					gpsBtn.textContent = '✅ ' + bd_reg_vars.strings.of;
				},
				() => {
					gpsBtn.disabled = false;
					gpsBtn.textContent = '📍 ' + bd_reg_vars.strings.of;
					alert(bd_reg_vars.strings.gps_error);
				},
				{ enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
			);
		});
	}

	// Allow clicking on the map to set marker.
	document.addEventListener('bd-reg-step3-shown', () => {
		if (map) setTimeout(() => map.invalidateSize(), 200);
	});

	// Hook into step navigation to lazy-init map.
	const _origShowStep = showStep;
	showStep = function(index) {
		_origShowStep(index);
		if (index === 2 && typeof L !== 'undefined' && !map) {
			// Default center: Santiago, Chile.
			initMap(-33.4489, -70.6693);
		}
	};

	// ══════════════════════════════════════════════════════════════
	// STEP 4 — LOGO & GALLERY
	// ══════════════════════════════════════════════════════════════
	const logoDropzone  = document.getElementById('bd-logo-dropzone');
	const logoInput     = document.getElementById('bd-logo-input');
	const logoPreview   = document.getElementById('bd-logo-preview');
	const logoPlaceholder = logoDropzone ? logoDropzone.querySelector('.bd-reg__dropzone-placeholder') : null;

	const galleryInput    = document.getElementById('bd-gallery-input');
	const galleryPreviews = document.getElementById('bd-gallery-previews');
	let galleryFiles      = [];

	// Logo preview.
	function handleLogoFile(file) {
		if (!file || !file.type.startsWith('image/')) return;
		if (file.size > 5 * 1024 * 1024) {
			alert(bd_reg_vars.strings.file_too_large);
			return;
		}
		const allowed = ['image/jpeg', 'image/png', 'image/webp'];
		if (!allowed.includes(file.type)) {
			alert(bd_reg_vars.strings.invalid_type);
			return;
		}
		const reader = new FileReader();
		reader.onload = e => {
			logoPreview.src = e.target.result;
			logoPreview.hidden = false;
			if (logoPlaceholder) logoPlaceholder.hidden = true;
		};
		reader.readAsDataURL(file);
	}

	if (logoInput) {
		logoInput.addEventListener('change', () => {
			if (logoInput.files[0]) handleLogoFile(logoInput.files[0]);
		});
	}

	// Drag & drop logo.
	if (logoDropzone) {
		logoDropzone.addEventListener('dragover', e => {
			e.preventDefault();
			logoDropzone.classList.add('bd-reg__dropzone--dragover');
		});
		logoDropzone.addEventListener('dragleave', () => {
			logoDropzone.classList.remove('bd-reg__dropzone--dragover');
		});
		logoDropzone.addEventListener('drop', e => {
			e.preventDefault();
			logoDropzone.classList.remove('bd-reg__dropzone--dragover');
			const file = e.dataTransfer.files[0];
			if (file && logoInput) {
				const dt = new DataTransfer();
				dt.items.add(file);
				logoInput.files = dt.files;
				handleLogoFile(file);
			}
		});
	}

	// Gallery.
	if (galleryInput) {
		galleryInput.addEventListener('change', () => {
			const remaining = 5 - galleryFiles.length;
			if (remaining <= 0) {
				alert(bd_reg_vars.strings.max_photos);
				galleryInput.value = '';
				return;
			}
			const newFiles = Array.from(galleryInput.files).slice(0, remaining);
			newFiles.forEach(file => {
				if (!file.type.startsWith('image/')) return;
				if (file.size > 5 * 1024 * 1024) {
					alert(bd_reg_vars.strings.file_too_large);
					return;
				}
				const allowed = ['image/jpeg', 'image/png', 'image/webp'];
				if (!allowed.includes(file.type)) {
					alert(bd_reg_vars.strings.invalid_type);
					return;
				}
				galleryFiles.push(file);
			});
			renderGallery();
			syncGalleryInput();
			galleryInput.value = '';
		});
	}

	function renderGallery() {
		if (!galleryPreviews) return;
		galleryPreviews.innerHTML = '';
		galleryFiles.forEach((file, idx) => {
			const reader = new FileReader();
			reader.onload = e => {
				const div = document.createElement('div');
				div.className = 'bd-reg__gallery-item';
				div.innerHTML = `
					<img src="${e.target.result}" alt="">
					<button type="button" class="bd-reg__gallery-remove" data-index="${idx}" aria-label="Eliminar">×</button>
				`;
				galleryPreviews.appendChild(div);
			};
			reader.readAsDataURL(file);
		});
	}

	galleryPreviews.addEventListener('click', e => {
		const btn = e.target.closest('.bd-reg__gallery-remove');
		if (!btn) return;
		const idx = parseInt(btn.dataset.index, 10);
		galleryFiles.splice(idx, 1);
		renderGallery();
		syncGalleryInput();
	});

	function syncGalleryInput() {
		if (!galleryInput) return;
		const dt = new DataTransfer();
		galleryFiles.forEach(f => dt.items.add(f));
		galleryInput.files = dt.files;
	}

	// ══════════════════════════════════════════════════════════════
	// FORM SUBMISSION
	// ══════════════════════════════════════════════════════════════

	form.addEventListener('submit', function (e) {
		e.preventDefault();

		// Validate all steps.
		let allValid = true;
		for (let i = 0; i < TOTAL_STEPS; i++) {
			if (!validateStep(i)) {
				allValid = false;
				showStep(i);
				break;
			}
		}
		if (!allValid) return;

		// Disable submit.
		submitBtn.disabled = true;
		submitBtn.textContent = bd_reg_vars.strings.submitting;
		responseDiv.hidden = true;
		responseDiv.innerHTML = '';

		const formData = new FormData(form);
		formData.append('action', 'bd_frontend_register');
		formData.append('security', bd_reg_vars.registration_nonce);

		// Sync gallery files.
		formData.delete('gallery_images[]');
		galleryFiles.forEach(file => formData.append('gallery_images[]', file));

		fetch(bd_reg_vars.ajax_url, { method: 'POST', body: formData })
			.then(res => res.json())
			.then(data => {
				submitBtn.disabled = false;
				submitBtn.textContent = bd_reg_vars.strings.submit;

				const payload = data.data || data;
				responseDiv.hidden = false;

				if (payload.success) {
					responseDiv.innerHTML = `
						<div class="bd-reg__alert bd-reg__alert--success">
							<strong>✅ ${bd_reg_vars.strings.success}</strong>
							<p>${payload.message}</p>
						</div>`;
					form.reset();
					galleryFiles = [];
					if (galleryPreviews) galleryPreviews.innerHTML = '';
					if (logoPreview) { logoPreview.src = ''; logoPreview.hidden = true; }
					if (logoPlaceholder) logoPlaceholder.hidden = false;
					if (map) { map.remove(); map = null; mapMarker = null; }
					setCoords('', '');
					if (latDisplay) latDisplay.value = '';
					if (lngDisplay) lngDisplay.value = '';
					showStep(0);
				} else {
					responseDiv.innerHTML = `
						<div class="bd-reg__alert bd-reg__alert--error">
							<strong>❌ ${payload.message || bd_reg_vars.strings.error}</strong>
						</div>`;
				}
				responseDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
			})
			.catch(() => {
				submitBtn.disabled = false;
				submitBtn.textContent = bd_reg_vars.strings.submit;
				responseDiv.hidden = false;
				responseDiv.innerHTML = `
					<div class="bd-reg__alert bd-reg__alert--error">
						<strong>❌ ${bd_reg_vars.strings.error}</strong>
					</div>`;
			});
	});

	// ══════════════════════════════════════════════════════════════
	// INIT
	// ══════════════════════════════════════════════════════════════
	showStep(0);

})();
