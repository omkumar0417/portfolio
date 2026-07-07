document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  const navToggle = document.getElementById('nav-toggle');
  const mobileNav = document.getElementById('mobile-nav');
  const backToTop = document.getElementById('back-to-top');
  const contactForm = document.getElementById('contact-form');
  const submitButton = document.getElementById('contact-submit');
  const formStatus = document.getElementById('form-status');
  const themeToggle = document.getElementById('theme-toggle');
  const themeToggleMobile = document.getElementById('theme-toggle-mobile');
  const copySummaryButton = document.getElementById('copy-summary');
  const developerSummary = document.getElementById('developer-summary');
  const resumeDownload = document.getElementById('resume-download');
  const thankYouMessage = document.getElementById('thank-you-msg');
  const currentYear = document.getElementById('current-year');
  const loadingScreen = document.getElementById('loading-screen');
  const themeButtons = [themeToggle, themeToggleMobile].filter(Boolean);
  const sectionLinks = Array.from(document.querySelectorAll('[data-section-link]'));
  const revealElements = Array.from(document.querySelectorAll('.reveal'));
  const scrollSections = Array.from(document.querySelectorAll('main section[id]')).filter((section) =>
    ['home', 'about', 'experience', 'projects', 'skills', 'faq', 'contact'].includes(section.id)
  );

  initTheme();
  initLoadingScreen();
  initTypedText();
  initParticles(body);
  initNavigation();
  initReveal();
  initBackToTop();
  initContactForm();
  initFooterYear();
  initResumeMessage();
  initTerminalCommand();
  initGithubShowcase();
  initCounters();


  function initTheme() {
    const savedTheme = localStorage.getItem('portfolio-theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    setTheme(savedTheme || (prefersDark ? 'dark' : 'light'));

    themeButtons.forEach((button) => {
      button.addEventListener('click', () => {
        const nextTheme = body.classList.contains('dark-mode') ? 'light' : 'dark';
        setTheme(nextTheme);
      });
    });
  }

  function setTheme(theme) {
    const isDark = theme === 'dark';
    body.classList.toggle('dark-mode', isDark);
    localStorage.setItem('portfolio-theme', theme);

    if (themeToggle) {
      themeToggle.innerHTML = isDark
        ? '<i class="fa-solid fa-sun" aria-hidden="true"></i>'
        : '<i class="fa-solid fa-moon" aria-hidden="true"></i>';
      themeToggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
      themeToggle.setAttribute('aria-pressed', String(isDark));
    }

    if (themeToggleMobile) {
      themeToggleMobile.textContent = isDark ? 'Light mode' : 'Dark mode';
      themeToggleMobile.setAttribute('aria-pressed', String(isDark));
    }
  }

  function initLoadingScreen() {
    if (!loadingScreen) return;

    const hideLoadingScreen = () => {
      loadingScreen.classList.add('hidden');
    };

    if (document.readyState === 'complete') {
      setTimeout(hideLoadingScreen, 650);
    } else {
      window.addEventListener('load', () => setTimeout(hideLoadingScreen, 650), { once: true });
      setTimeout(hideLoadingScreen, 2200);
    }
  }

  function initTypedText() {
    const typedElement = document.querySelector('.typed-text');
    if (!typedElement) return;

    const phrases = [
      'Backend-focused developer for real-world web applications.',
      'Java, Servlets, JDBC, Oracle SQL, HTML, CSS, and JavaScript.'
    ];

    let phraseIndex = 0;
    let charIndex = 0;
    let deleting = false;

    const tick = () => {
      const phrase = phrases[phraseIndex];
      typedElement.textContent = deleting
        ? phrase.slice(0, charIndex--)
        : phrase.slice(0, charIndex++);

      if (!deleting && charIndex > phrase.length) {
        deleting = true;
        setTimeout(tick, 1200);
        return;
      }

      if (deleting && charIndex < 0) {
        deleting = false;
        phraseIndex = (phraseIndex + 1) % phrases.length;
        charIndex = 0;
        setTimeout(tick, 220);
        return;
      }

      setTimeout(tick, deleting ? 22 : 38);
    };

    tick();
  }

  function initNavigation() {
    const setActiveLink = (hash) => {
      sectionLinks.forEach((link) => {
        link.classList.toggle('is-active', link.getAttribute('href') === hash);
      });
    };

    setActiveLink(window.location.hash || '#home');

    if (navToggle && mobileNav) {
      navToggle.addEventListener('click', () => {
        const isOpen = navToggle.getAttribute('aria-expanded') === 'true';
        navToggle.setAttribute('aria-expanded', String(!isOpen));
        navToggle.setAttribute('aria-label', isOpen ? 'Open menu' : 'Close menu');
        mobileNav.hidden = isOpen;
        mobileNav.classList.toggle('is-open', !isOpen);
      });

      mobileNav.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
          navToggle.setAttribute('aria-expanded', 'false');
          navToggle.setAttribute('aria-label', 'Open menu');
          mobileNav.hidden = true;
          mobileNav.classList.remove('is-open');
        });
      });
    }

    if ('IntersectionObserver' in window) {
      const sectionObserver = new IntersectionObserver(
        (entries) => {
          const visibleSections = entries
            .filter((entry) => entry.isIntersecting)
            .sort((a, b) => b.intersectionRatio - a.intersectionRatio);

          if (visibleSections[0]) {
            setActiveLink(`#${visibleSections[0].target.id}`);
          }
        },
        { threshold: [0.25, 0.4, 0.6], rootMargin: '-26% 0px -50% 0px' }
      );

      scrollSections.forEach((section) => sectionObserver.observe(section));
    }
  }

  function initReveal() {
    if (!('IntersectionObserver' in window)) {
      revealElements.forEach((element) => element.classList.add('is-visible'));
      return;
    }

    const revealObserver = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        });
      },
      { threshold: 0.16, rootMargin: '0px 0px -8% 0px' }
    );

    revealElements.forEach((element) => revealObserver.observe(element));
  }

  function initBackToTop() {
    if (!backToTop) return;

    const toggleBackToTop = () => {
      backToTop.classList.toggle('is-visible', window.scrollY > 260);
    };

    toggleBackToTop();
    window.addEventListener('scroll', toggleBackToTop);
    backToTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  function initCopySummary() {
    if (!copySummaryButton || !developerSummary) return;

    copySummaryButton.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(developerSummary.innerText.trim());
        copySummaryButton.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
      } catch (error) {
        copySummaryButton.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';
      }

      setTimeout(() => {
        copySummaryButton.innerHTML = '<i class="fa-regular fa-copy" aria-hidden="true"></i>';
      }, 1200);
    });
  }

  function initResumeMessage() {
    if (!resumeDownload || !thankYouMessage) return;

    resumeDownload.addEventListener('click', () => {
      thankYouMessage.textContent = 'Resume downloaded. Thanks for taking a look.';
      if (typeof gtag === 'function') {
        gtag('event', 'resume_download', {
          event_category: 'Resume',
          event_label: 'OmKumarResume.pdf'
        });
      }
    });
  }

  function initFooterYear() {
    if (currentYear) {
      currentYear.textContent = String(new Date().getFullYear());
    }
  }

  function initContactForm() {
    if (window.emailjs) {
      window.emailjs.init({
        publicKey: 'OHA_QnV_Pku4_-jeL'
      });
    }

    if (!contactForm || !submitButton || !formStatus) return;

    contactForm.addEventListener('submit', async (event) => {
      event.preventDefault();

      if (!contactForm.reportValidity()) {
        formStatus.textContent = 'Please complete all fields before sending your message.';
        formStatus.style.color = '#ff8f6b';
        return;
      }

      if (!window.emailjs) {
        formStatus.textContent = 'Message service is unavailable right now. Please email me directly.';
        formStatus.style.color = '#ff8f6b';
        return;
      }

      const originalText = submitButton.textContent;
      submitButton.disabled = true;
      submitButton.textContent = 'Sending...';
      formStatus.textContent = '';

      try {
        await window.emailjs.sendForm('service_cqggltu', 'template_crrcrhc', contactForm);
        contactForm.reset();
        formStatus.textContent = 'Message sent successfully. I will get back to you soon.';
        formStatus.style.color = '#67d6d2';
      } catch (error) {
        formStatus.textContent = 'Message failed to send. Please try again or email me directly.';
        formStatus.style.color = '#ff8f6b';
      } finally {
        submitButton.disabled = false;
        submitButton.textContent = originalText;
      }
    });
  }

  function initCounters() {
    const counters = document.querySelectorAll('.hero-metrics strong');
    if (counters.length === 0) return;

    const animateCounter = (counter) => {
      const target = parseFloat(counter.getAttribute('data-count'));
      const decimals = parseInt(counter.getAttribute('data-decimals') || '0', 10);
      const duration = 1600; // 1.6 seconds
      const startTime = performance.now();

      const update = (now) => {
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const ease = progress * (2 - progress); // Ease out quad
        const current = ease * target;

        counter.textContent = current.toFixed(decimals);

        if (progress < 1) {
          requestAnimationFrame(update);
        } else {
          counter.textContent = target.toFixed(decimals);
        }
      };

      requestAnimationFrame(update);
    };

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            animateCounter(entry.target);
            obs.unobserve(entry.target);
          }
        });
      }, { threshold: 0.4 });
      counters.forEach(counter => observer.observe(counter));
    } else {
      counters.forEach(animateCounter);
    }
  }

  function initGithubShowcase() {
    const reposList = document.getElementById('github-repos-list');
    const reposCount = document.getElementById('gh-repos-count');
    const avatarImg = document.getElementById('gh-avatar');
    const bioText = document.getElementById('gh-bio');

    if (!reposList) return;

    // Fetch user details
    fetch('https://api.github.com/users/omkumar0417')
      .then(res => {
        if (!res.ok) throw new Error('API Rate Limited or Offline');
        return res.json();
      })
      .then(user => {
        if (reposCount) reposCount.textContent = user.public_repos;
        if (avatarImg && user.avatar_url) avatarImg.src = user.avatar_url;
        if (bioText && user.bio) bioText.textContent = user.bio;
      })
      .catch(() => {
        // Fallback to defaults
      });

    // Fetch repositories
    fetch('https://api.github.com/users/omkumar0417/repos?sort=pushed&per_page=4')
      .then(res => {
        if (!res.ok) throw new Error('API Rate Limited or Offline');
        return res.json();
      })
      .then(repos => {
        if (repos.length === 0) return;
        reposList.innerHTML = ''; // Clear default hardcoded entries
        
        repos.forEach(repo => {
          const card = document.createElement('div');
          card.classList.add('gh-repo-card');
          
          let langColor = '#858585';
          if (repo.language === 'Java') langColor = '#b07219';
          else if (repo.language === 'JavaScript') langColor = '#f1e05a';
          else if (repo.language === 'HTML') langColor = '#e34c26';
          else if (repo.language === 'CSS') langColor = '#563d7c';

          card.innerHTML = `
            <div class="gh-repo-header">
              <i class="fa-regular fa-bookmark" aria-hidden="true"></i>
              <a href="${repo.html_url}" target="_blank">${repo.name}</a>
            </div>
            <p class="gh-repo-desc">${repo.description || 'No description provided.'}</p>
            <div class="gh-repo-footer">
              <span class="gh-repo-lang"><i class="fa-solid fa-circle" style="color: ${langColor};" aria-hidden="true"></i> ${repo.language || 'Code'}</span>
            </div>
          `;
          reposList.appendChild(card);
        });
      })
      .catch(() => {
        // Keep default HTML fallback content
      });
  }

  function initTerminalCommand() {
    const typedRun = document.querySelector('.typed-run');
    if (!typedRun) return;
    const command = 'mvn spring-boot:run';
    let index = 0;
    
    const typeChar = () => {
      if (index < command.length) {
        typedRun.textContent += command[index++];
        setTimeout(typeChar, 80);
      }
    };
    
    setTimeout(typeChar, 500);
  }
});

function initParticles(body) {
  const canvas = document.getElementById('particles-canvas');
  if (!canvas) return;

  const context = canvas.getContext('2d');
  if (!context) return;

  const particles = [];
  let width = 0;
  let height = 0;

  const resizeCanvas = () => {
    width = canvas.width = window.innerWidth;
    height = canvas.height = window.innerHeight;
  };

  const createParticles = () => {
    particles.length = 0;
    const count = Math.min(52, Math.floor(window.innerWidth / 28));

    for (let index = 0; index < count; index += 1) {
      particles.push({
        x: Math.random() * width,
        y: Math.random() * height,
        radius: Math.random() * 2 + 0.8,
        vx: (Math.random() - 0.5) * 0.22,
        vy: (Math.random() - 0.5) * 0.22,
        alpha: Math.random() * 0.45 + 0.12
      });
    }
  };

  const drawFrame = () => {
    context.clearRect(0, 0, width, height);
    const isDark = body.classList.contains('dark-mode');
    const dotColor = isDark ? '124, 141, 255' : '84, 103, 255';
    const lineColor = isDark ? '103, 214, 210' : '15, 181, 176';

    for (let i = 0; i < particles.length; i += 1) {
      const particle = particles[i];
      context.beginPath();
      context.fillStyle = `rgba(${dotColor}, ${particle.alpha})`;
      context.arc(particle.x, particle.y, particle.radius, 0, Math.PI * 2);
      context.fill();

      particle.x += particle.vx;
      particle.y += particle.vy;

      if (particle.x < 0 || particle.x > width) particle.vx *= -1;
      if (particle.y < 0 || particle.y > height) particle.vy *= -1;

      for (let j = i + 1; j < particles.length; j += 1) {
        const other = particles[j];
        const dx = particle.x - other.x;
        const dy = particle.y - other.y;
        const distance = Math.sqrt(dx * dx + dy * dy);

        if (distance > 140) continue;

        context.beginPath();
        context.strokeStyle = `rgba(${lineColor}, ${0.13 - distance / 1500})`;
        context.lineWidth = 1;
        context.moveTo(particle.x, particle.y);
        context.lineTo(other.x, other.y);
        context.stroke();
      }
    }

    window.requestAnimationFrame(drawFrame);
  };

  resizeCanvas();
  createParticles();
  drawFrame();

  window.addEventListener('resize', () => {
    resizeCanvas();
    createParticles();
  });
}
