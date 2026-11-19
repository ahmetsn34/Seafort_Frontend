(() => {
  'use strict';
  const picker = document.getElementById('site-language');
  if (!picker) return;
  const supported = new Set(['en', 'tr', 'ar', 'ru', 'zh-CN']);
  const requested = new URLSearchParams(location.search).get('lang') || 'en';
  const language = supported.has(requested) ? requested : 'en';
  picker.value = language;
  const dock = document.querySelector('.language-dock');
  const current = document.getElementById('language-current');
  if (current) current.textContent = {en:'EN',tr:'TR',ar:'AR',ru:'RU','zh-CN':'中文'}[language];
  if (dock) {
    document.addEventListener('click', event => {
      if (dock.open && !dock.contains(event.target)) dock.open = false;
    });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && dock.open) {
        dock.open = false;
        dock.querySelector('summary').focus();
      }
    });
  }
  picker.addEventListener('change', () => {
    const next = new URL(location.href);
    if (picker.value === 'en') next.searchParams.delete('lang');
    else next.searchParams.set('lang', picker.value);
    location.assign(next.href);
  });
  if (language === 'en') {
    document.documentElement.dataset.languageState = 'ready';
    return;
  }
  const ui = {
    tr: {'Language':'Dil','Play slideshow':'Slaytı oynat','Pause slideshow':'Slaytı duraklat','Play':'Oynat','Pause':'Duraklat','Open menu':'Menüyü aç','Close menu':'Menüyü kapat'},
    ar: {'Language':'اللغة','Play slideshow':'تشغيل عرض الشرائح','Pause slideshow':'إيقاف عرض الشرائح مؤقتًا','Play':'تشغيل','Pause':'إيقاف مؤقت','Open menu':'فتح القائمة','Close menu':'إغلاق القائمة'},
    ru: {'Language':'Язык','Play slideshow':'Запустить слайд-шоу','Pause slideshow':'Приостановить слайд-шоу','Play':'Воспроизвести','Pause':'Пауза','Open menu':'Открыть меню','Close menu':'Закрыть меню'},
    'zh-CN': {'Language':'语言','Play slideshow':'播放幻灯片','Pause slideshow':'暂停幻灯片','Play':'播放','Pause':'暂停','Open menu':'打开菜单','Close menu':'关闭菜单'}
  };
  const normalize = value => value.replace(/\s+/g, ' ').trim();
  fetch('/translations/?lang=' + encodeURIComponent(language), {credentials:'same-origin'})
    .then(response => { if (!response.ok) throw new Error('Language unavailable'); return response.json(); })
    .then(catalog => {
      Object.assign(catalog, ui[language]);
      picker.setAttribute('aria-label', ui[language].Language);
      document.querySelector('label[for="site-language"]').textContent = ui[language].Language;
      const translate = value => {
        const key = normalize(value);
        if (!Object.hasOwn(catalog, key) || typeof catalog[key] !== 'string') return value;
        const edges = value.match(/^(\s*)[\s\S]*?(\s*)$/);
        return edges[1] + catalog[key] + edges[2];
      };
      const excluded = node => node.parentElement && node.parentElement.closest('script,style,textarea,input,code,[data-no-translate]');
      const attributes = ['aria-label','placeholder','alt'];
      const applyElement = element => {
        if (element.closest('[data-no-translate]')) return;
        for (const attribute of attributes) {
          if (element.hasAttribute(attribute)) {
            const value = element.getAttribute(attribute);
            const translated = translate(value);
            if (value !== translated) element.setAttribute(attribute, translated);
          }
        }
      };
      const apply = root => {
        if (root.nodeType === Node.TEXT_NODE) {
          if (!excluded(root)) {
            const next = translate(root.nodeValue);
            if (next !== root.nodeValue) root.nodeValue = next;
          }
          return;
        }
        if (root.nodeType !== Node.ELEMENT_NODE) return;
        applyElement(root);
        root.querySelectorAll('[aria-label],[placeholder],[alt]').forEach(applyElement);
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        let node;
        while ((node = walker.nextNode())) {
          if (excluded(node)) continue;
          const next = translate(node.nodeValue);
          if (next !== node.nodeValue) node.nodeValue = next;
        }
      };
      apply(document.body);
      document.title = translate(document.title);
      document.documentElement.lang = language;
      document.documentElement.dir = language === 'ar' ? 'rtl' : 'ltr';
      for (const anchor of document.querySelectorAll('a[href]')) {
        const href = anchor.getAttribute('href');
        if (href.startsWith('#')) continue;
        const url = new URL(href, location.href);
        if (url.origin !== location.origin || url.pathname.startsWith('/admin') || url.pathname.startsWith('/assets/')) continue;
        url.searchParams.set('lang', language);
        anchor.setAttribute('href', url.pathname + url.search + url.hash);
      }
      for (const form of document.querySelectorAll('form')) {
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'get') { const action = new URL(form.action, location.href); action.searchParams.set('lang', language); form.action = action.pathname + action.search + action.hash; continue; }
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'lang'; input.value = language; form.append(input);
      }
      new MutationObserver(records => {
        for (const record of records) {
          if (record.type === 'attributes') applyElement(record.target);
          else if (record.type === 'characterData') apply(record.target);
          else record.addedNodes.forEach(apply);
        }
      }).observe(document.body, {childList:true, subtree:true, characterData:true, attributes:true, attributeFilter:attributes});
      document.documentElement.dataset.languageState = 'ready';
    })
    .catch(() => {
      picker.value = 'en';
      if (current) current.textContent = 'EN';
      document.documentElement.dataset.languageState = 'unavailable';
      document.getElementById('language-status').textContent = 'Translation is unavailable. English content is shown.';
    });
})();
