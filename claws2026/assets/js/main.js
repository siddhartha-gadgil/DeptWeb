document.addEventListener('DOMContentLoaded', () => {
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

  // The faint IISc drawing behind the page (styled in CSS) zooms in as you scroll.
  const bg = document.createElement('div');
  bg.id = 'dynamic-bg';
  document.body.prepend(bg);
  const zoomBackground = () => {
    const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
    const progress = Math.min(window.scrollY / Math.max(maxScroll, 1), 1);
    bg.style.transform = `translateX(-2.28%) translateY(5%) scale(${1 + progress * 0.6})`;
  };
  window.addEventListener('scroll', zoomBackground, { passive: true });
  zoomBackground();

  // Mobile menu
  const menuToggle = document.getElementById('menu-toggle');
  const nav = document.getElementById('nav-links');
  const setMenu = (open) => {
    nav.classList.toggle('active', open);
    menuToggle.setAttribute('aria-expanded', String(open));
  };
  menuToggle.addEventListener('click', () => setMenu(!nav.classList.contains('active')));
  nav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setMenu(false)));

  // Participant search
  const searchInput = document.getElementById('participant-search');
  const participantList = document.getElementById('participant-list');
  if (searchInput && participantList) {
    const participants = Array.from(participantList.children);
    searchInput.addEventListener('input', () => {
      const query = searchInput.value.toLowerCase();
      let visible = 0;
      participants.forEach(li => {
        li.hidden = !li.textContent.toLowerCase().includes(query);
        if (!li.hidden) visible += 1;
      });
      document.getElementById('participant-empty').hidden = visible !== 0;
    });
  }

  // Rotate the short preview while the full list is closed.
  const participantDetails = document.getElementById('participants-details');
  const participantPreview = document.getElementById('participant-preview');
  if (participantDetails && participantPreview && participantList) {
    const people = Array.from(participantList.children);
    let previewOffset = 0;
    const updatePreview = () => {
      participantPreview.replaceChildren();
      for (let i = 0; i < Math.min(3, people.length); i += 1) {
        const card = document.createElement('div');
        const person = people[(previewOffset + i) % people.length];
        const name = document.createElement('strong');
        name.textContent = person.querySelector('strong')?.textContent || '';
        card.append(name);
        const institution = person.querySelector('span')?.textContent;
        if (institution) {
          const affiliation = document.createElement('span');
          affiliation.textContent = institution;
          card.append(affiliation);
        }
        participantPreview.append(card);
      }
    };
    participantDetails.addEventListener('toggle', () => {
      participantPreview.hidden = participantDetails.open;
    });
    if (people.length > 3) {
      window.setInterval(() => {
        if (participantDetails.open || reducedMotion.matches || document.hidden) return;
        participantPreview.classList.add('is-changing');
        window.setTimeout(() => {
          if (!participantDetails.open && !document.hidden && !reducedMotion.matches) {
            previewOffset = (previewOffset + 3) % people.length;
            updatePreview();
          }
          participantPreview.classList.remove('is-changing');
        }, 450);
      }, 5000);
    }
  }

  // Search the page's people, talks, programme and main sections.
  const siteSearch = document.getElementById('site-search-input');
  const searchResults = document.getElementById('site-search-results');
  const entries = [];
  const addEntry = (element, label, category, searchable) => {
    if (!label) return;
    if (!element.id) element.id = `site-search-target-${entries.length}`;
    entries.push({ element, label, category, searchable: (searchable || label).toLowerCase() });
  };
  document.querySelectorAll('.speaker-card').forEach(card => {
    addEntry(card, card.querySelector('.speaker-info strong').textContent.trim(), 'Speaker', card.textContent);
  });
  document.querySelectorAll('.talk-card').forEach(card => {
    addEntry(card, card.querySelector('.talk-title').textContent.trim(), `Talk · ${card.dataset.speaker}`, card.textContent);
  });
  document.querySelectorAll('#participant-list li').forEach(item => {
    addEntry(item, item.querySelector('strong').textContent.trim(), 'Participant', item.textContent);
  });
  document.querySelectorAll('#programme tbody td').forEach(cell => {
    const label = cell.textContent.replace(/\s+/g, ' ').trim();
    addEntry(cell, label, 'Programme', label);
  });
  document.querySelectorAll('main section[id]').forEach(section => {
    addEntry(section, section.querySelector('h1, h2')?.textContent.trim() || section.id, 'Section', section.textContent);
  });
  const closeResults = () => {
    searchResults.hidden = true;
    siteSearch.setAttribute('aria-expanded', 'false');
  };
  siteSearch.addEventListener('input', () => {
    const query = siteSearch.value.trim().toLowerCase();
    searchResults.replaceChildren();
    if (!query) { closeResults(); return; }
    const matches = entries.filter(entry => entry.searchable.includes(query)).slice(0, 8);
    if (!matches.length) {
      const message = document.createElement('p');
      message.textContent = 'No results found.';
      searchResults.append(message);
    }
    matches.forEach(entry => {
      const link = document.createElement('a');
      link.href = `#${entry.element.id}`;
      const title = document.createElement('strong');
      title.textContent = entry.label;
      const category = document.createElement('span');
      category.textContent = entry.category;
      link.append(title, category);
      link.addEventListener('click', () => {
        if (entry.category === 'Participant') {
          participantDetails.open = true;
          searchInput.value = '';
          searchInput.dispatchEvent(new Event('input'));
        }
        closeResults();
      });
      searchResults.append(link);
    });
    searchResults.hidden = false;
    siteSearch.setAttribute('aria-expanded', 'true');
  });
  siteSearch.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeResults();
    if (event.key === 'Enter') {
      const firstResult = searchResults.querySelector('a');
      if (firstResult && !searchResults.hidden) { event.preventDefault(); firstResult.click(); }
    }
  });
  document.addEventListener('click', event => {
    if (!event.target.closest('.site-search')) closeResults();
  });

  // Highlight the nav link of the section in view.
  const sections = document.querySelectorAll('main section[id]');
  const scrollSpy = () => {
    sections.forEach(section => {
      const top = section.offsetTop - 150;
      const inView = window.scrollY > top && window.scrollY <= top + section.offsetHeight;
      document.querySelector(`.nav-links a[href="#${section.id}"]`)?.classList.toggle('active', inView);
    });
  };
  window.addEventListener('scroll', scrollSpy, { passive: true });
  scrollSpy();

  // Talks: without JS the cards are a plain list; here they become a carousel
  // with the previous and next talk receding on either side.
  const talksBox = document.getElementById('talks-carousel');
  const cards = Array.from(talksBox.querySelectorAll('.talk-card'));
  const dots = Array.from(talksBox.querySelectorAll('.talk-dots button'));
  const counter = document.getElementById('talks-counter');
  const toggle = document.getElementById('talks-toggle');
  const track = talksBox.querySelector('.talk-track');
  const listLabel = toggle.textContent;
  const isList = () => talksBox.classList.contains('expanded');
  let current = 0;

  const show = (index) => {
    const n = cards.length;
    current = (index + n) % n;
    const list = isList();
    cards.forEach((card, i) => {
      card.classList.toggle('active', !list && i === current);
      card.classList.toggle('prev', !list && n > 2 && i === (current - 1 + n) % n);
      card.classList.toggle('next', !list && n > 1 && i === (current + 1) % n);
      card.inert = !list && i !== current;
    });
    dots.forEach((dot, i) => dot.setAttribute('aria-current', String(i === current)));
    counter.textContent = `${current + 1} / ${n}`;
  };

  talksBox.classList.remove('expanded');
  toggle.hidden = false;
  toggle.addEventListener('click', () => {
    toggle.textContent = talksBox.classList.toggle('expanded') ? 'Show as carousel' : listLabel;
    show(current);
  });
  document.getElementById('talks-prev').addEventListener('click', () => show(current - 1));
  document.getElementById('talks-next').addEventListener('click', () => show(current + 1));
  dots.forEach((dot, i) => dot.addEventListener('click', () => show(i)));

  talksBox.addEventListener('keydown', event => {
    const step = { ArrowLeft: -1, ArrowRight: 1 }[event.key];
    if (!step || isList() || event.altKey || event.ctrlKey || event.metaKey) return;
    if (event.target.closest('.talk-card')) talksBox.focus(); // the focused card is about to slide away
    show(current + step);
  });

  // Hidden cards are inert, so clicks on the side cards land on the track.
  track.addEventListener('click', event => {
    if (isList()) return;
    const { left, right } = cards[current].getBoundingClientRect();
    if (event.clientX < left) show(current - 1);
    else if (event.clientX > right) show(current + 1);
  });

  // Touch swipe; mouse users have the arrows, dots and side cards.
  let swipeX = null;
  track.addEventListener('pointerdown', event => {
    swipeX = event.pointerType === 'mouse' || isList() ? null : event.clientX;
  });
  track.addEventListener('pointerup', event => {
    if (swipeX !== null && Math.abs(event.clientX - swipeX) > 50) show(current + (event.clientX < swipeX ? 1 : -1));
    swipeX = null;
  });
  track.addEventListener('pointercancel', () => { swipeX = null; });

  // Links to #talk-… (speaker grid, programme, search, shared URLs) open that talk.
  const openFromHash = (hash) => {
    const index = cards.findIndex(card => `#${card.id}` === hash);
    if (index >= 0) show(index);
  };
  document.addEventListener('click', event => {
    const link = event.target.closest('a[href^="#talk-"]');
    if (link) openFromHash(link.getAttribute('href'));
  });
  window.addEventListener('hashchange', () => openFromHash(location.hash));
  show(0);
  openFromHash(location.hash);
});
