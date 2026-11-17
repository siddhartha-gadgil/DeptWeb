/* newsite/assets/js/main.js */
document.addEventListener('DOMContentLoaded', () => {
  // --- Dynamic Background ---
  const bg = document.createElement('div');
  bg.id = 'dynamic-bg';
  Object.assign(bg.style, {
    position: 'fixed',
    top: '0',
    left: '0',
    width: '100vw',
    height: '100vh',
    zIndex: '-1',
    pointerEvents: 'none',
    backgroundImage: 'url("assets/img/IISC.svg")',
    backgroundRepeat: 'no-repeat',
    backgroundPosition: 'center',
    backgroundSize: 'contain',
    opacity: '0.1',
    transformOrigin: 'center',
    transition: 'transform 0.1s ease-out',
    transform: 'translateX(-2.28%) translateY(5%) scale(1)',
  });
  document.body.prepend(bg);

  const updateBackgroundZoom = () => {
    const scrollY = window.scrollY;
    const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
    const scrollPercent = Math.min(scrollY / Math.max(maxScroll, 1), 1);
    const scale = 1 + scrollPercent * 0.6; // Zoom up to 1.6x (doubled intensity)
    bg.style.transform = `translateX(-2.28%) translateY(5%) scale(${scale})`;
  };

  window.addEventListener('scroll', updateBackgroundZoom);
  updateBackgroundZoom(); // Initial call

  // Mobile Menu
  const menuToggle = document.getElementById('menu-toggle');
  const navLinksContainer = document.getElementById('nav-links');
  const navLinks = navLinksContainer.querySelectorAll('a');

  if (menuToggle && navLinksContainer) {
    menuToggle.addEventListener('click', () => {
      const isOpen = navLinksContainer.classList.toggle('active');
      menuToggle.setAttribute('aria-expanded', String(isOpen));
    });

    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        navLinksContainer.classList.remove('active');
        menuToggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  // Participant Search
  const searchInput = document.getElementById('participant-search');
  const participantList = document.getElementById('participant-list');

  if (searchInput && participantList) {
    const participants = Array.from(participantList.getElementsByTagName('li'));
    
    searchInput.addEventListener('input', (e) => {
      const query = e.target.value.toLowerCase();
      let visible = 0;
      participants.forEach(li => {
        const matches = li.textContent.toLowerCase().includes(query);
        li.hidden = !matches;
        if (matches) visible += 1;
      });
      const emptyMessage = document.getElementById('participant-empty');
      if (emptyMessage) emptyMessage.hidden = visible !== 0;
    });
  }

  // Rotate the short preview while the full list is closed.
  const participantDetails = document.getElementById('participants-details');
  const participantPreview = document.getElementById('participant-preview');
  if (participantDetails && participantPreview && participantList) {
    const people = Array.from(participantList.children);
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
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

  // Search the page's people, programme and main sections.
  const siteSearch = document.getElementById('site-search-input');
  const searchResults = document.getElementById('site-search-results');
  if (siteSearch && searchResults) {
    const entries = [];
    const addEntry = (element, label, category, searchable) => {
      if (!label || !element) return;
      if (!element.id) element.id = `site-search-target-${entries.length}`;
      entries.push({ element, label, category, searchable: (searchable || label).toLowerCase() });
    };
    document.querySelectorAll('.speaker-card').forEach(card => {
      addEntry(card, card.querySelector('.speaker-info strong')?.textContent.trim(), 'Speaker', card.textContent);
    });
    document.querySelectorAll('#participant-list li').forEach(item => {
      addEntry(item, item.querySelector('strong')?.textContent.trim(), 'Participant', item.textContent);
    });
    document.querySelectorAll('#programme tbody td').forEach(cell => {
      const label = cell.textContent.replace(/\s+/g, ' ').trim();
      if (label) addEntry(cell, label, 'Programme', label);
    });
    document.querySelectorAll('main section[id]').forEach(section => {
      const heading = section.querySelector('h1, h2')?.textContent.trim() || section.id;
      addEntry(section, heading, 'Section', section.textContent);
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
          if (entry.category === 'Participant' && searchInput && participantList) {
            if (participantDetails) participantDetails.open = true;
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
  }

  // Scroll Spy
  const sections = document.querySelectorAll('section[id]');
  
  function scrollSpy() {
    const scrollY = window.pageYOffset;

    sections.forEach(current => {
      const sectionHeight = current.offsetHeight;
      const sectionTop = current.offsetTop - 150;
      const sectionId = current.getAttribute('id');

      if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
        document.querySelector('.nav-links a[href*=' + sectionId + ']')?.classList.remove('active');
        document.querySelector('.nav-links a[href*=' + sectionId + ']')?.classList.add('active');
      } else {
        document.querySelector('.nav-links a[href*=' + sectionId + ']')?.classList.remove('active');
      }
    });
  }

  window.addEventListener('scroll', scrollSpy);
  scrollSpy(); // Initial call

  // Titles & Abstracts jump links
  const normalizeTalkName = (value) => (value || '')
    .toLowerCase()
    .replace(/[‪]/g, '')
    .replace(/\bprof\.?\s*/g, '')
    .replace(/\s+/g, ' ')
    .trim();

  const talkAliases = new Map([
    ['k. balachandran', 'talk-k-balachandran'],
    ['patrizia donato', 'talk-p-donato'],
    ['p. donato', 'talk-p-donato'],
    ['antonio gaudiello', 'talk-antonio-gaudiello'],
    ['raju k. george', 'talk-raju-k-george'],
    ['tuhin ghosh', 'talk-tuhin-ghosh'],
    ['harsha hutridurga', 'talk-harsha-hutridurga'],
    ['k. t. joseph', 'talk-k-t-joseph'],
    ['p. k. ratnakumar', 'talk-ratnakumar-pk'],
    ['ratnakumar p k', 'talk-ratnakumar-pk'],
    ['ratnakumar p.k.', 'talk-ratnakumar-pk'],
    ['venky krishnan', 'talk-venky-krishnan'],
    ['sandeep kunnath', 'talk-sandeep-k'],
    ['k. sandeep', 'talk-sandeep-k'],
    ['sandeep k.', 'talk-sandeep-k'],
    ['rajesh mahadevan', 'talk-rajesh-mahadevan'],
    ['t. muthukumar', 'talk-t-muthukumar'],
    ['m. t. nair', 'talk-m-t-nair'],
    ['m.t. nair', 'talk-m-t-nair'],
    ['mythily ramaswamy', 'talk-mythily-ramaswamy'],
    ['mallikarjuna rao', ''],
    ['p.n. srikanth', 'talk-p-n-srikanth'],
    ['p. n. srikanth', 'talk-p-n-srikanth'],
    ['sivaguru sritharan', 'talk-sivaguru-sritharan'],
    ['sivaguru s. sritharan', 'talk-sivaguru-sritharan'],
    ['s. sundar', 'talk-s-sundar'],
    ['s. thangavelu', 'talk-s-thangavelu'],
    ['hari varma', 'talk-hari-varma']
  ]);

  const talksCard = document.getElementById('titles-abstracts');
  const talks = talksCard ? Array.from(talksCard.querySelectorAll('.talk-card')) : [];
  const talksToggle = document.getElementById('talks-toggle');
  const talksPrev = document.getElementById('talks-prev');
  const talksNext = document.getElementById('talks-next');
  const talksCounter = document.getElementById('talks-counter');
  let currentTalkIndex = 0;

  const setActiveTalk = (index) => {
    if (!talks.length) return;
    currentTalkIndex = (index + talks.length) % talks.length;
    talks.forEach((talk, talkIndex) => {
      talk.classList.toggle('active-talk', talkIndex === currentTalkIndex);
    });
    if (talksCounter) {
      talksCounter.textContent = `${currentTalkIndex + 1} / ${talks.length}`;
    }
  };

  const scrollToTalk = (targetId) => {
    if (!targetId) return;
    const card = document.getElementById(targetId);
    if (!card) return;
    const talkIndex = talks.indexOf(card);
    if (talkIndex >= 0) setActiveTalk(talkIndex);
    talksCard?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  const resolveTalkTarget = (speakerName) => {
    const key = normalizeTalkName(speakerName);
    return talkAliases.get(key) || '';
  };

  document.querySelectorAll('.speaker-card').forEach((card) => {
    const speaker = card.querySelector('.speaker-info strong')?.textContent || '';
    const targetId = resolveTalkTarget(speaker);
    if (!targetId) return;
    card.dataset.talkTarget = targetId;
    card.setAttribute('tabindex', '0');
    card.setAttribute('role', 'button');
    card.setAttribute('aria-label', `Go to title and abstract for ${speaker}`);
    card.addEventListener('click', () => scrollToTalk(targetId));
    card.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        scrollToTalk(targetId);
      }
    });
  });

  document.querySelectorAll('#programme tbody td').forEach((cell) => {
    const speaker = cell.querySelector('.schedule-speaker')?.textContent.trim() || cell.textContent.trim();
    const targetId = resolveTalkTarget(speaker);
    if (!targetId) return;
    cell.dataset.talkTarget = targetId;
    cell.setAttribute('tabindex', '0');
    cell.setAttribute('role', 'button');
    cell.setAttribute('aria-label', `Go to title and abstract for ${speaker}`);
    cell.addEventListener('click', () => scrollToTalk(targetId));
    cell.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        scrollToTalk(targetId);
      }
    });
  });

  setActiveTalk(0);

  if (talksPrev) {
    talksPrev.addEventListener('click', () => setActiveTalk(currentTalkIndex - 1));
  }

  if (talksNext) {
    talksNext.addEventListener('click', () => setActiveTalk(currentTalkIndex + 1));
  }

  if (talksToggle && talksCard) {
    talksToggle.addEventListener('click', () => {
      const expanded = talksCard.classList.toggle('expanded');
      talksToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      talksToggle.textContent = expanded ? 'Show one talk' : 'Expand all talks';
    });
  }
});
