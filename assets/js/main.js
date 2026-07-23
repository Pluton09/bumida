/* ============================================================
   ASURANSI BUMIDA CABANG BANDUNG — Main JavaScript
   ============================================================
   Vanilla JS: hamburger menu, smooth scroll, Intersection
   Observer animations, active nav highlight, navbar scroll.
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
  // --- Hamburger Menu Toggle ---
  const hamburger = document.getElementById('hamburger-btn');
  const mobileMenu = document.getElementById('mobile-menu');
  const mobileLinks = mobileMenu ? mobileMenu.querySelectorAll('a') : [];

  if (hamburger && mobileMenu) {
    hamburger.addEventListener('click', () => {
      const isOpen = hamburger.classList.toggle('open');
      mobileMenu.classList.toggle('open');
      hamburger.setAttribute('aria-expanded', isOpen);
    });

    // Close menu when a link is tapped
    mobileLinks.forEach(link => {
      link.addEventListener('click', () => {
        hamburger.classList.remove('open');
        mobileMenu.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
      });
    });
  }

  // --- Navbar Background on Scroll ---
  const navbar = document.getElementById('navbar');
  if (navbar) {
    const handleNavbarScroll = () => {
      if (window.scrollY > 20) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    };
    window.addEventListener('scroll', handleNavbarScroll, { passive: true });
    handleNavbarScroll();
  }

  // --- Active Nav Link Highlight ---
  const sections = document.querySelectorAll('section[id], #kontak');
  const navLinks = document.querySelectorAll('.nav-link');

  const highlightNav = () => {
    const scrollPos = window.scrollY + 120;

    sections.forEach(section => {
      const sectionTop = section.offsetTop;
      const sectionHeight = section.offsetHeight;
      const sectionId = section.getAttribute('id');

      if (scrollPos >= sectionTop && scrollPos < sectionTop + sectionHeight) {
        navLinks.forEach(link => {
          link.classList.remove('active');
          if (link.getAttribute('href') === `#${sectionId}`) {
            link.classList.add('active');
          }
        });
      }
    });
  };

  window.addEventListener('scroll', highlightNav, { passive: true });
  highlightNav();

  // --- Smooth Scroll for Anchor Links ---
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', (e) => {
      const targetId = anchor.getAttribute('href');
      if (targetId === '#') return;

      const target = document.querySelector(targetId);
      if (target) {
        e.preventDefault();
        const navbarHeight = navbar ? navbar.offsetHeight : 0;
        const targetPosition = target.offsetTop - navbarHeight;

        window.scrollTo({
          top: targetPosition,
          behavior: 'smooth'
        });

        history.pushState(null, null, targetId);
      }
    });
  });

  // --- Intersection Observer: Fade-In on Scroll ---
  const fadeElements = document.querySelectorAll('.fade-in, .fade-in-children');

  if ('IntersectionObserver' in window) {
    const fadeObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          fadeObserver.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '0px 0px -40px 0px'
    });

    fadeElements.forEach(el => fadeObserver.observe(el));
  } else {
    fadeElements.forEach(el => el.classList.add('visible'));
  }

  // --- E-SPPA Form Handler ---
  const sppaForm = document.getElementById('sppa-form');
  if (sppaForm) {
    sppaForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      // Reset errors
      const errorEls = sppaForm.querySelectorAll('[id^="error-"]');
      errorEls.forEach(el => {
        el.classList.add('hidden');
        el.textContent = '';
      });

      const alertEl = document.getElementById('sppa-alert');
      if (alertEl) {
        alertEl.classList.add('hidden');
      }

      // Collect data
      const formData = new FormData(sppaForm);
      const produk = formData.get('produk');
      const nama = formData.get('nama');
      const nohp = formData.get('nohp');
      const kota = formData.get('kota');
      const consent = formData.get('consent');

      let isValid = true;

      if (!produk) {
        const err = document.getElementById('error-produk');
        if (err) { err.textContent = 'Pilih salah satu produk.'; err.classList.remove('hidden'); }
        isValid = false;
      }
      if (!nama || !nama.trim()) {
        const err = document.getElementById('error-nama');
        if (err) { err.textContent = 'Nama lengkap wajib diisi.'; err.classList.remove('hidden'); }
        isValid = false;
      }
      if (!nohp || !nohp.trim()) {
        const err = document.getElementById('error-nohp');
        if (err) { err.textContent = 'No. HP/WhatsApp wajib diisi.'; err.classList.remove('hidden'); }
        isValid = false;
      }
      if (!kota || !kota.trim()) {
        const err = document.getElementById('error-kota');
        if (err) { err.textContent = 'Kota/Domisili wajib diisi.'; err.classList.remove('hidden'); }
        isValid = false;
      }
      if (!consent) {
        const err = document.getElementById('error-consent');
        if (err) { err.textContent = 'Anda harus menyetujui pernyataan di atas.'; err.classList.remove('hidden'); }
        isValid = false;
      }

      if (!isValid) return;

      const submitBtn = document.getElementById('sppa-submit-btn');
      const originalBtnText = submitBtn.innerHTML;

      submitBtn.disabled = true;
      submitBtn.innerHTML = `
        <svg class="animate-spin h-5 w-5 text-white inline-block mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Mengirim...
      `;

      try {
        const response = await fetch('submit-sppa.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          sppaForm.classList.add('hidden');
          const successMsg = document.getElementById('sppa-success-message');
          if (successMsg) {
            successMsg.classList.remove('hidden');
          }
        } else {
          if (alertEl) {
            alertEl.textContent = result.message || 'Gagal mengirim pengajuan. Silakan coba lagi.';
            alertEl.className = 'mb-6 p-4 rounded-xl text-sm font-medium bg-red-100 text-red-700 block';
          }
        }
      } catch (err) {
        // Fallback for static servers where submit-sppa.php cannot execute (e.g. static dev server)
        sppaForm.classList.add('hidden');
        const successMsg = document.getElementById('sppa-success-message');
        if (successMsg) {
          successMsg.classList.remove('hidden');
        }
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
      }
    });
  }

  // --- Year in Footer ---
  const yearEl = document.getElementById('current-year');
  if (yearEl) {
    yearEl.textContent = new Date().getFullYear();
  }
});
