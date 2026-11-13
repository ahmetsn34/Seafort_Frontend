(() => {
  const form = document.querySelector('.site-content-form');
  if (!form) return;
  let changed = false;
  form.addEventListener('input', () => { changed = true; });
  form.addEventListener('change', () => { changed = true; });
  form.addEventListener('submit', () => { changed = false; });
  window.addEventListener('beforeunload', event => {
    if (changed) { event.preventDefault(); event.returnValue = ''; }
  });
})();
