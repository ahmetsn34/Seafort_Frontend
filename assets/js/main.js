/* ============================================================
   SEAFORT — global scripts
   ============================================================ */
(function () {
  'use strict';

  var mq = window.matchMedia('(max-width: 921px)');

  /* ---------- mobil menü ---------- */
  var burger = document.getElementById('burger');
  var nav = document.getElementById('nav');
  if (burger && nav) {
    burger.addEventListener('click', function () {
      var open = nav.classList.toggle('open');
      burger.setAttribute('aria-expanded', open);
    });
  }

  /* ---------- açılır menüler ----------
     Masaüstü: hover CSS ile açılır, tıklama/klavye ile de açılabilir.
     Mobil: akordeon gibi çalışır.                                     */
  var items = document.querySelectorAll('.nav__item.has-sub');

  function closeAll(except) {
    items.forEach(function (li) {
      if (li !== except) {
        li.classList.remove('is-open');
        var t = li.querySelector('.nav__link');
        if (t) t.setAttribute('aria-expanded', 'false');
      }
    });
  }

  items.forEach(function (li) {
    var trigger = li.querySelector('.nav__link');
    if (!trigger) return;

    trigger.addEventListener('click', function (e) {
      e.preventDefault();
      var open = !li.classList.contains('is-open');
      closeAll(li);
      li.classList.toggle('is-open', open);
      trigger.setAttribute('aria-expanded', open);
    });

    /* masaüstünde fareyle üzerine gelince aria durumunu da güncelle */
    li.addEventListener('mouseenter', function () {
      if (!mq.matches) trigger.setAttribute('aria-expanded', 'true');
    });
    li.addEventListener('mouseleave', function () {
      if (!mq.matches) {
        trigger.setAttribute('aria-expanded', 'false');
        li.classList.remove('is-open');
      }
    });
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.nav__item.has-sub')) closeAll(null);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeAll(null);
      if (nav && nav.classList.contains('open')) {
        nav.classList.remove('open');
        burger.setAttribute('aria-expanded', 'false');
      }
    }
  });

  /* ---------- sayaçlar ---------- */
  var counters = document.querySelectorAll('.count');
  // Keep the rendered totals when animation is unavailable or unwanted.
  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (counters.length && !reducedMotion && 'IntersectionObserver' in window) {
    var cio = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        var el = en.target, to = +el.dataset.to, t0 = performance.now(), dur = 1500;
        (function step(t) {
          var k = Math.min((t - t0) / dur, 1);
          el.textContent = Math.round(to * (1 - Math.pow(1 - k, 3)));
          if (k < 1) requestAnimationFrame(step);
        })(t0);
        cio.unobserve(el);
      });
    }, { threshold: 0.6 });
    counters.forEach(function (el) { cio.observe(el); });
  }

  /* ---------- logo şeridi (kesintisiz döngü için içeriği ikiye katlar) ---------- */
  var track = document.getElementById('track');
  if (track) track.innerHTML += track.innerHTML;

  /* ---------- yukarı çık ---------- */
  var topBtn = document.getElementById('top-btn');
  if (topBtn) {
    window.addEventListener('scroll', function () {
      topBtn.classList.toggle('show', window.scrollY > 500);
    }, { passive: true });
    topBtn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ---------- formlar ----------
     Şu an sadece tarayıcı doğrulaması var. Backend bağlandığında
     action="" alanını doldurup aşağıdaki bloğu silin.              */
  document.querySelectorAll('form[data-demo]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      var box = f.querySelector('.form-msg');
      if (box) {
        box.textContent = 'Form arayüzü hazır. Kayıt için backend bağlanmalı (bkz. db/schema.sql).';
        box.style.color = '#0372a9';
      }
    });
  });
})();
