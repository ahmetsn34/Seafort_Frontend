(() => {
  const body = document.querySelector('#body');
  const count = document.querySelector('#word-count');
  const form = document.querySelector('#post-form');
  if (body && count) {
    const updateCount = () => {
      const words = body.value.trim().split(/\s+/u).filter(Boolean).length;
      count.textContent = `${words.toLocaleString('tr-TR')} kelime`;
    };
    body.addEventListener('input', updateCount);
    updateCount();
  }
  if (!form) return;
  let dirty = false;
  form.addEventListener('input', () => { dirty = true; });
  form.addEventListener('change', () => { dirty = true; });
  form.addEventListener('submit', () => { dirty = false; });
  window.addEventListener('beforeunload', event => {
    if (dirty) { event.preventDefault(); event.returnValue = ''; }
  });
  const title = document.querySelector('#title');
  const slug = document.querySelector('#slug');
  if (title && slug && slug.value === '') {
    let touched = false;
    slug.addEventListener('input', () => { touched = true; });
    title.addEventListener('input', () => {
      if (touched) return;
      slug.value = title.value.toLowerCase().replace(/ı/g, 'i').replace(/ğ/g, 'g').replace(/ü/g, 'u').replace(/ş/g, 's').replace(/ö/g, 'o').replace(/ç/g, 'c').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 150);
    });
  }
})();
