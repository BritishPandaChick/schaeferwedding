/*
Anti-spam plugin
No spam in comments. No captcha.
wordpress.org/plugins/anti-spam/
*/

"use strict";
(function() {
	function initAntiSpam() {
		const answerInput = document.querySelector('.wantispam-control-a');
		const answer = answerInput ? answerInput.value : '';
		const currentYear = new Date().getFullYear();
		const forms = document.querySelectorAll('.wantispam-required-fields');

		forms.forEach(function(form) {
			if (!form.classList.contains('wantispam-form-processed')) {
				// Hide inputs from users
				const spamGroup = form.querySelector('.wantispam-group');
				if (spamGroup) {
					spamGroup.style.display = 'none';
				}

				// Set js support marker
				const jsControlInput = form.querySelector('.wantispam-control-j');
				if (jsControlInput) {
					jsControlInput.value = 'on';
				}

				// Set answer into other input instead of user
				const answerControlInput = form.querySelector('.wantispam-control-q');
				if (answerControlInput) {
					answerControlInput.value = answer;
				}

				// Clear value of the empty input because some themes are adding some value for all inputs
				const emptyControlInput = form.querySelector('.wantispam-control-e');
				if (emptyControlInput) {
					emptyControlInput.value = '';
				}

				// Create and append dynamic control
				const dynamicControl = document.createElement('input');
				dynamicControl.type = 'hidden';
				dynamicControl.name = 'wantispam_d';
				dynamicControl.classList.add('wantispam-control', 'wantispam-control-d');
				dynamicControl.value = currentYear;
				form.appendChild(dynamicControl);

				form.classList.add('wantispam-form-processed');
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAntiSpam);
	} else {
		initAntiSpam();
	}
})();
