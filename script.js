document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  const navToggle = document.getElementById('nav-toggle');
  const mobileNav = document.getElementById('mobile-nav');
  const backToTop = document.getElementById('back-to-top');
  const contactForm = document.getElementById('contact-form');
  const submitButton = document.getElementById('contact-submit');
  const formStatus = document.getElementById('form-status');
  const themeButtons = [
    document.getElementById('theme-toggle'),
    document.getElementById('theme-toggle-mobile')
  ].filter(Boolean);
  const resumeDownload = document.getElementById('resume-download');
  const thankYouMessage = document.getElementById('thank-you-msg');
  const currentYear = document.getElementById('current-year');

  const setTheme = (theme) => {
    const isDark = theme === 'dark';
    body.classList.toggle('dark-mode', isDark);
    themeButtons.forEach((button) => {
      button.textContent = isDark ? 'Light mode' : 'Dark mode';
      button.setAttribute('aria-pressed', String(isDark));
    });
    localStorage.setItem('portfolio-theme', theme);
  };

  const savedTheme = localStorage.getItem('portfolio-theme');
  const preferredDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  setTheme(savedTheme || (preferredDark ? 'dark' : 'light'));

  themeButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const nextTheme = body.classList.contains('dark-mode') ? 'light' : 'dark';
      setTheme(nextTheme);
    });
  });

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

  const toggleBackToTop = () => {
    backToTop.classList.toggle('is-visible', window.scrollY > 260);
  };

  toggleBackToTop();
  window.addEventListener('scroll', toggleBackToTop);

  backToTop.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  if (resumeDownload && thankYouMessage) {
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

  if (currentYear) {
    currentYear.textContent = String(new Date().getFullYear());
  }

  if (window.emailjs) {
    window.emailjs.init({
      publicKey: 'OHA_QnV_Pku4_-jeL'
    });
  }

  if (contactForm && submitButton && formStatus) {
    contactForm.addEventListener('submit', async (event) => {
      event.preventDefault();

      if (!contactForm.reportValidity()) {
        formStatus.textContent = 'Please complete all fields before sending your message.';
        formStatus.style.color = '#b94f2b';
        return;
      }

      if (!window.emailjs) {
        formStatus.textContent = 'Message service is unavailable right now. Please email me directly.';
        formStatus.style.color = '#b94f2b';
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
        formStatus.style.color = '#2e8b57';
      } catch (error) {
        formStatus.textContent = 'Message failed to send. Please try again or email me directly.';
        formStatus.style.color = '#b94f2b';
      } finally {
        submitButton.disabled = false;
        submitButton.textContent = originalText;
      }
    });
  }
});
