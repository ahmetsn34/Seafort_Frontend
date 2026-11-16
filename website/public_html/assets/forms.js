(() => {
  'use strict';
  const feedback = document.querySelector('[data-enquiry-feedback]');
  if (feedback && window.location.hash === '#contact-details') feedback.focus({ preventScroll: true });
  const form = document.querySelector('[data-enquiry-form]');
  if (!form) return;
  const attachment = form.querySelector('input[type="file"]');
  const fileStatus = document.getElementById('enquiry-file-status');
  const button = form.querySelector('button[type="submit"]');
  const submitStatus = form.querySelector('[data-submit-status]');
  if (attachment && fileStatus) {
    attachment.addEventListener('change', () => {
      attachment.setCustomValidity('');
      const file = attachment.files && attachment.files[0];
      if (!file) { fileStatus.textContent = ''; return; }
      const limit = Number(form.dataset.fileLimit || 0);
      if (limit && file.size > limit) {
        const message = 'Choose a file no larger than ' + form.dataset.fileLimitLabel + '.';
        attachment.setCustomValidity(message);
        fileStatus.textContent = message;
        return;
      }
      const size = file.size < 1024 * 1024 ? Math.max(1, Math.round(file.size / 1024)) + ' KB' : (file.size / 1024 / 1024).toFixed(1) + ' MB';
      fileStatus.textContent = 'Selected: ' + file.name + ' (' + size + ').';
    });
  }
  form.addEventListener('submit', event => {
    if (!form.reportValidity()) { event.preventDefault(); return; }
    if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); }
    if (submitStatus) submitStatus.textContent = 'Submitting your message. Please wait.';
  });
  window.addEventListener('pageshow', () => {
    if (button) { button.disabled = false; button.removeAttribute('aria-busy'); }
    if (submitStatus) submitStatus.textContent = '';
  });
})();
