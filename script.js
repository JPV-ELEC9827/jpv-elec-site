// JPV ELEC – script commun à toutes les pages

// Menu ☰ sur téléphone
const burger = document.querySelector('.burger'), tabs = document.getElementById('menu');
if (burger && tabs) {
  burger.onclick = () => { const o = tabs.classList.toggle('open'); burger.setAttribute('aria-expanded', o); };
}

// Année du copyright
const y = document.getElementById('y');
if (y) y.textContent = new Date().getFullYear();

// Message après l'envoi du formulaire de devis (page Contact, renvoyé par devis.php)
const envoi = new URLSearchParams(location.search).get('envoi');
if (envoi) {
  const m = document.getElementById(envoi === 'ok' ? 'msg-ok' : 'msg-err');
  if (m) m.hidden = false;
}

// Agrandissement des photos (page Réalisations) : tout bouton avec data-lb (photo 1600) et data-cap (légende)
const lb = document.getElementById('lb');
if (lb) {
  const shots = [...document.querySelectorAll('[data-lb]')];
  const lbImg = lb.querySelector('img'), lbTxt = lb.querySelector('p');
  let cur = 0;
  shots.forEach((b, i) => b.onclick = () => openLb(i));
  function openLb(i) {
    cur = (i + shots.length) % shots.length;
    const s = shots[cur];
    lbImg.src = s.dataset.lb; lbImg.alt = s.dataset.cap; lbTxt.textContent = s.dataset.cap;
    lb.classList.add('open');
  }
  const close = () => lb.classList.remove('open');
  lb.querySelector('.x').onclick = close;
  lb.querySelector('.prev').onclick = e => { e.stopPropagation(); openLb(cur - 1); };
  lb.querySelector('.next').onclick = e => { e.stopPropagation(); openLb(cur + 1); };
  lb.onclick = e => { if (e.target === lb) close(); };
  document.addEventListener('keydown', e => {
    if (!lb.classList.contains('open')) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowLeft') openLb(cur - 1);
    if (e.key === 'ArrowRight') openLb(cur + 1);
  });
}
