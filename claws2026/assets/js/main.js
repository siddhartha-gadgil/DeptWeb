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
      navLinksContainer.classList.toggle('active');
    });

    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        navLinksContainer.classList.remove('active');
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
      participants.forEach(li => {
        const text = li.textContent.toLowerCase();
        li.style.display = text.includes(query) ? '' : 'none';
      });
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
