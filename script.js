// ===================================
// Mobile Navigation Toggle
// ===================================
const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
const navLinks = document.querySelector('.nav-links');

mobileMenuBtn?.addEventListener('click', () => {
    navLinks.classList.toggle('active');
    mobileMenuBtn.classList.toggle('active');
});

// Close mobile menu when clicking a link
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        navLinks.classList.remove('active');
        mobileMenuBtn.classList.remove('active');
    });
});

// ===================================
// Navbar Scroll Effect
// ===================================
const navbar = document.querySelector('.navbar');

window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// ===================================
// Active Navigation Link
// ===================================
const sections = document.querySelectorAll('section[id]');
const navLinksAll = document.querySelectorAll('.nav-links a');

function updateActiveLink() {
    const scrollY = window.scrollY;

    sections.forEach(section => {
        const sectionHeight = section.offsetHeight;
        const sectionTop = section.offsetTop - 100;
        const sectionId = section.getAttribute('id');

        if (scrollY > sectionTop && scrollY <= sectionTop + sectionHeight) {
            navLinksAll.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === `#${sectionId}`) {
                    link.classList.add('active');
                }
            });
        }
    });
}

window.addEventListener('scroll', updateActiveLink);

// ===================================
// Smooth Scroll for Anchor Links
// ===================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        const targetElement = document.querySelector(targetId);

        if (targetElement) {
            const navHeight = navbar.offsetHeight;
            const targetPosition = targetElement.offsetTop - navHeight;

            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        }
    });
});

// ===================================
// Form Submission Handler
// ===================================
const contactForm = document.getElementById('contactForm');

contactForm?.addEventListener('submit', async function(e) {
    e.preventDefault();

    // Honeypot: if the hidden field is filled, a bot submitted — bail silently
    const honeypot = this.querySelector('input[name="website"]');
    if (honeypot && honeypot.value !== '') {
        return;
    }

    const isFr = document.documentElement.lang === 'fr';
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.textContent;

    // Loading state
    btn.textContent = isFr ? 'Envoi…' : 'Sending…';
    btn.disabled = true;
    btn.style.background = '';

    try {
        const response = await fetch('/contact.php', {
            method: 'POST',
            body: new FormData(this),
        });
        const result = await response.json();

        if (result.ok) {
            btn.textContent = isFr ? 'Message envoyé !' : 'Message Sent!';
            btn.style.background = 'var(--success)';
            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.background = '';
                btn.disabled = false;
                this.reset();
            }, 3000);
        } else {
            btn.textContent = isFr ? 'Réessayer' : 'Try again';
            btn.style.background = 'var(--error)';
            btn.disabled = false;
        }
    } catch (_) {
        btn.textContent = isFr ? 'Réessayer' : 'Try again';
        btn.style.background = 'var(--error)';
        btn.disabled = false;
    }
});

// ===================================
// Intersection Observer for Animations
// ===================================
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-in');
            observer.unobserve(entry.target);
        }
    });
}, observerOptions);

// Observe elements for animation
document.querySelectorAll('.solution-card, .blog-card, .about-feature').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(20px)';
    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(el);
});

// Add animation styles dynamically
const style = document.createElement('style');
style.textContent = `
    .animate-in {
        opacity: 1 !important;
        transform: translateY(0) !important;
    }
`;
document.head.appendChild(style);

// ===================================
// Staggered Animation for Grid Items
// ===================================
document.querySelectorAll('.solutions-grid, .blog-grid').forEach(grid => {
    const items = grid.children;
    Array.from(items).forEach((item, index) => {
        item.style.transitionDelay = `${index * 0.1}s`;
    });
});
