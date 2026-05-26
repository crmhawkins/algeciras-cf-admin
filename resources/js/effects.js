/* ============================================================
   Algeciras CF — Motion FX library
   GSAP + ScrollTrigger powered.
   ============================================================ */

import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

// Reduce-motion respeto preferencia del usuario
const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function init() {
    if (prefersReduced) return;

    /* ============ 1. PARALLAX HERO ============ */
    // El escudo del hero se mueve verticalmente más lento que el scroll
    const heroBadge = document.querySelector('[data-fx="hero-badge"]');
    if (heroBadge) {
        gsap.to(heroBadge, {
            yPercent: 30,
            ease: 'none',
            scrollTrigger: {
                trigger: heroBadge.closest('section'),
                start: 'top top',
                end: 'bottom top',
                scrub: true,
            },
        });

        // Idle floating
        gsap.to(heroBadge, {
            y: -15,
            duration: 3,
            ease: 'sine.inOut',
            repeat: -1,
            yoyo: true,
        });
    }

    // Texto hero entra con stagger
    gsap.from('[data-fx="hero-text"] > *', {
        y: 50,
        opacity: 0,
        duration: 0.9,
        ease: 'power3.out',
        stagger: 0.12,
        delay: 0.2,
    });

    /* ============ 2. COUNTER ANIMADO ============ */
    document.querySelectorAll('[data-fx="counter"]').forEach((el) => {
        const target = parseInt(el.dataset.value || el.textContent.replace(/\D/g, ''), 10);
        if (Number.isNaN(target)) return;
        const suffix = el.dataset.suffix || '';
        const prefix = el.dataset.prefix || '';
        const obj = { v: 0 };
        gsap.to(obj, {
            v: target,
            duration: 1.6,
            ease: 'power2.out',
            scrollTrigger: { trigger: el, start: 'top 85%', once: true },
            onUpdate: () => { el.textContent = `${prefix}${Math.round(obj.v)}${suffix}`; },
        });
    });

    /* ============ 3. REVEAL ON SCROLL ============ */
    // Cualquier elemento con [data-fx="reveal"] se anima al entrar en viewport
    document.querySelectorAll('[data-fx="reveal"]').forEach((el) => {
        gsap.from(el, {
            y: 60,
            opacity: 0,
            duration: 0.8,
            ease: 'power3.out',
            scrollTrigger: { trigger: el, start: 'top 85%', once: true },
        });
    });

    // Reveal escalonado para grupos (data-fx="reveal-stagger" en parent)
    document.querySelectorAll('[data-fx="reveal-stagger"]').forEach((group) => {
        gsap.from(group.children, {
            y: 40,
            opacity: 0,
            duration: 0.7,
            ease: 'power3.out',
            stagger: 0.08,
            scrollTrigger: { trigger: group, start: 'top 80%', once: true },
        });
    });

    /* ============ 4. TILT CARDS ============ */
    document.querySelectorAll('[data-fx="tilt"]').forEach((card) => {
        const max = 8;  // grados máximos
        card.addEventListener('mousemove', (e) => {
            const r = card.getBoundingClientRect();
            const x = ((e.clientX - r.left) / r.width - 0.5) * 2;
            const y = ((e.clientY - r.top) / r.height - 0.5) * 2;
            gsap.to(card, {
                rotationX: -y * max,
                rotationY: x * max,
                transformPerspective: 1000,
                duration: 0.5,
                ease: 'power2.out',
            });
        });
        card.addEventListener('mouseleave', () => {
            gsap.to(card, { rotationX: 0, rotationY: 0, duration: 0.6, ease: 'power2.out' });
        });
    });

    /* ============ 5. SMOOTH SCROLL ============ */
    // Anchors internos con scroll suave + ajuste header sticky
    document.querySelectorAll('a[href^="#"]').forEach((a) => {
        a.addEventListener('click', (e) => {
            const target = document.querySelector(a.getAttribute('href'));
            if (!target) return;
            e.preventDefault();
            const headerH = 64;
            gsap.to(window, {
                duration: 0.8,
                scrollTo: { y: target, offsetY: headerH },
                ease: 'power3.inOut',
            });
        });
    });

    /* ============ 6. SECTION DIVIDERS — slash rojo entra ============ */
    document.querySelectorAll('[data-fx="slash"]').forEach((el) => {
        gsap.from(el, {
            scaleX: 0,
            transformOrigin: 'left center',
            duration: 0.8,
            ease: 'power3.out',
            scrollTrigger: { trigger: el, start: 'top 85%', once: true },
        });
    });

    /* ============ 7. PRODUCT IMAGES ZOOM ON SCROLL ============ */
    document.querySelectorAll('[data-fx="zoom-in"]').forEach((el) => {
        gsap.from(el, {
            scale: 0.85,
            opacity: 0,
            duration: 0.9,
            ease: 'power3.out',
            scrollTrigger: { trigger: el, start: 'top 90%', once: true },
        });
    });

    /* ============ 8. HEADER backdrop on scroll ============ */
    const header = document.querySelector('header.sticky');
    if (header) {
        ScrollTrigger.create({
            start: 'top -10',
            onEnter: () => header.classList.add('shadow-lg', 'backdrop-blur'),
            onLeaveBack: () => header.classList.remove('shadow-lg', 'backdrop-blur'),
        });
    }
}

// Esperar DOM + Livewire (por si Livewire reemplaza contenido)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

// Re-init en navegación Livewire
document.addEventListener('livewire:navigated', () => {
    ScrollTrigger.refresh();
    init();
});
