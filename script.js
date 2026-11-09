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

// Galerie des réalisations (seulement sur la page Réalisations)
// Pour ajouter une photo : fournir la vignette et le grand format WebP, leurs largeurs et, pour un portrait, true.
const g = document.getElementById('gallery');
if (g) {
  const IMG = (id, size) => `img/${id}-${size}.webp`;
  const photos = [
    ['ia21ee60344812179', "Armoire d'automatisme", 640, 1600],
    ['i665c8f02cade0d58', 'Armoire de distribution', 382, 900, true],
    ['i850024b28430c142', 'Armoire avec variateurs', 382, 900, true],
    ['i2200077b1ac6ef0a', 'Façade de pupitre de commande', 510, 1200, true],
    ['i98001e78bb20ee24', 'Armoire de puissance', 382, 900, true],
    ['ic2472af842b0183d', 'Façades de coffrets de commande', 640, 1600],
    ['i20ad3a57366db96e', 'Platine de commande', 510, 960, true],
    ['ie3887bef27c87bd1', 'Pupitre de commande', 510, 960, true],
    ['ib6460f8733a0351c', 'Câblage de façade', 640, 1600],
    ['ie0422e1c7916a09d', "Racks d'automates", 640, 1600],
    ['i2bd8a94980a3b8ce', 'Armoire automate', 510, 960, true],
    ['i00cb1612d4e588b3', 'Série industrielle', 510, 960, true],
  ];
  photos.forEach(([id, t, smallWidth, largeWidth, portrait]) => {
    const b = document.createElement('button');
    b.dataset.lb = IMG(id, 1600); b.dataset.cap = t;
    b.innerHTML = `<img class="${portrait ? 'photo-portrait' : ''}" src="${IMG(id, 'vignette')}" srcset="${IMG(id, 'vignette')} ${smallWidth}w, ${IMG(id, 1600)} ${largeWidth}w" sizes="auto, 280px" alt="${t} réalisée par JPV ELEC" loading="lazy"><span>${t}</span>`;
    g.appendChild(b);
  });
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
