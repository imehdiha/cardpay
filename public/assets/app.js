(() => {
  const parserEditor = document.querySelector('[data-parser-editor]');
  const parserTest = document.querySelector('[data-parser-test]');
  if (parserEditor && parserTest) parserTest.addEventListener('submit', () => {
    Array.from(parserEditor.elements).forEach((source) => {
      if (!source.name || source.name === '_csrf' || source.tagName === 'BUTTON') return;
      let target = Array.from(parserTest.elements).find((item) => item.name === source.name);
      if (!target) { target = document.createElement('input'); target.type = 'hidden'; target.name = source.name; parserTest.appendChild(target); }
      target.value = source.type === 'checkbox' ? (source.checked ? source.value : '') : source.value;
    });
  });
  const toast = document.querySelector('[data-toast]');
  document.querySelectorAll('[data-copy]').forEach((button) => button.addEventListener('click', async () => {
    const value = button.dataset.copy || '';
    let copied = false;
    try { if (navigator.clipboard?.writeText) { await navigator.clipboard.writeText(value); copied = true; } } catch (_) {}
    if (!copied) {
      const field = document.createElement('textarea'); field.value = value; field.setAttribute('readonly', ''); field.style.position = 'fixed'; field.style.opacity = '0'; document.body.appendChild(field); field.select();
      try { copied = document.execCommand('copy'); } catch (_) {} field.remove();
    }
    if (!copied) return;
    const original = button.dataset.copyLabel || button.textContent.trim(); button.textContent = 'کپی شد ✓'; button.classList.add('copied');
    if (toast) { toast.classList.add('show'); setTimeout(() => toast.classList.remove('show'), 1600); }
    setTimeout(() => { button.textContent = original; button.classList.remove('copied'); }, 1600);
  }));
  const page = document.querySelector('[data-payment-page]');
  if (!page) return;
  let status = page.dataset.status;
  let delay = 4000;
  const terminal = ['paid', 'expired', 'canceled', 'rejected'];
  const show = (next) => {
    status = next; page.dataset.status = next;
    document.querySelectorAll('[data-state]').forEach((node) => node.classList.remove('active'));
    const key = ['canceled', 'rejected'].includes(next) ? 'expired' : next;
    document.querySelector(`[data-state="${key}"]`)?.classList.add('active');
  };
  const countdown = document.querySelector('[data-countdown]');
  const updateClock = () => {
    if (!countdown || status !== 'pending') return;
    const seconds = Math.max(0, Math.floor((new Date(page.dataset.expires).getTime() - Date.now()) / 1000));
    const minutes = String(Math.floor(seconds / 60)).padStart(2, '0');
    countdown.textContent = `${minutes}:${String(seconds % 60).padStart(2, '0')}`;
  };
  updateClock(); setInterval(updateClock, 1000);
  const poll = async () => {
    if (terminal.includes(status)) return;
    try {
      const response = await fetch(page.dataset.statusUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });
      const json = await response.json();
      if (json.success && json.data.status !== status) show(json.data.status);
      delay = Math.min(12000, delay + 500);
    } catch (_) { delay = Math.min(20000, delay * 1.6); }
    if (!terminal.includes(status)) setTimeout(poll, delay);
  };
  if (!terminal.includes(status)) setTimeout(poll, delay);
})();
