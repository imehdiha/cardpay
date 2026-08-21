(() => {
  const form = document.querySelector('[data-payment-form]');
  if (!form) return;

  form.addEventListener('submit', () => {
    const button = form.querySelector('[data-submit-button]');
    button.disabled = true;
    button.classList.add('loading');
    button.querySelector('span').textContent = 'در حال انتقال...';
  });
})();
