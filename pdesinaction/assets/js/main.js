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
});