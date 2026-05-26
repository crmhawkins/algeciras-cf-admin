/* ============================================================
   Algeciras CF — Cinematic Motion FX
   Lenis (smooth scroll) + GSAP + ScrollTrigger
   ============================================================ */

import Lenis from 'lenis';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* ====== Smooth scroll global con Lenis ====== */
function initSmoothScroll() {
    if (prefersReduced) return null;
    const lenis = new Lenis({
        duration: 1.2,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),  // ease out expo
        smoothWheel: true,
        wheelMultiplier: 1,
        touchMultiplier: 1.5,
    });
    // Sincronizar Lenis con ScrollTrigger (CRUCIAL para que los scrubs funcionen)
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((time) => lenis.raf(time * 1000));
    gsap.ticker.lagSmoothing(0);
    window.__lenis = lenis;  // debug global
    return lenis;
}

/* ====== Init de todos los efectos al cargar DOM ====== */
function initFX() {
    if (prefersReduced) return;

    /* === 1. HERO PARALLAX (escala + traslación del escudo, fade del texto) === */
    const heroBadge = document.querySelector('[data-fx="hero-badge"]');
    const heroText = document.querySelector('[data-fx="hero-text"]');
    const heroSection = heroBadge?.closest('section');

    if (heroBadge && heroSection) {
        // Escudo: escala creciente + translación vertical SCRUBBED al scroll
        gsap.fromTo(heroBadge,
            { scale: 1, y: 0, rotation: 0 },
            {
                scale: 1.25,
                y: 120,
                rotation: 3,
                ease: 'none',
                scrollTrigger: {
                    trigger: heroSection,
                    start: 'top top',
                    end: 'bottom top',
                    scrub: 1.2,  // ← scrub suave: la animación sigue al scroll
                },
            }
        );

        // Idle floating del escudo en estado reposo
        gsap.to(heroBadge, {
            y: '+=12',
            duration: 3,
            ease: 'sine.inOut',
            repeat: -1,
            yoyo: true,
        });
    }

    if (heroText && heroSection) {
        // Texto del hero: fade-out al hacer scroll
        gsap.to(heroText, {
            opacity: 0,
            y: -100,
            ease: 'none',
            scrollTrigger: {
                trigger: heroSection,
                start: 'top top',
                end: 'bottom 30%',
                scrub: 1,
            },
        });

        // Entrada inicial con stagger
        gsap.from(heroText.children, {
            y: 80,
            opacity: 0,
            duration: 1,
            ease: 'power3.out',
            stagger: 0.15,
            delay: 0.3,
        });
    }

    /* === 2. CAPAS ROJAS DEL HERO en parallax === */
    const redLayers = document.querySelectorAll('[data-fx="hero-layer"]');
    redLayers.forEach((layer, i) => {
        const speed = parseFloat(layer.dataset.speed || (0.3 + i * 0.15));
        gsap.to(layer, {
            yPercent: -50 * speed,
            ease: 'none',
            scrollTrigger: {
                trigger: heroSection,
                start: 'top top',
                end: 'bottom top',
                scrub: true,
            },
        });
    });

    /* === 3. IMÁGENES QUE SE EXPANDEN AL SCROLL (CINEMATIC) === */
    // Cualquier elemento con [data-fx="zoom-scroll"] hace zoom-in mientras está en viewport
    document.querySelectorAll('[data-fx="zoom-scroll"]').forEach((el) => {
        gsap.fromTo(el,
            { scale: 0.82 },
            {
                scale: 1.08,
                ease: 'none',
                scrollTrigger: {
                    trigger: el,
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: 1.5,
                },
            }
        );
    });

    /* === 4. CONTAINERS DE IMAGEN CON OVERFLOW HIDDEN + INNER ZOOM === */
    // <div data-fx="image-reveal" class="overflow-hidden"><img></div>
    document.querySelectorAll('[data-fx="image-reveal"]').forEach((wrapper) => {
        const img = wrapper.querySelector('img');
        if (!img) return;
        gsap.fromTo(img,
            { scale: 1.4 },
            {
                scale: 1,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: wrapper,
                    start: 'top 90%',
                    end: 'center center',
                    scrub: 1,
                },
            }
        );
    });

    /* === 5. COUNTERS — IntersectionObserver (rAF agnóstico) === */
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting || entry.target.dataset.fxDone) return;
            entry.target.dataset.fxDone = '1';
            const el = entry.target;
            const target = parseInt(el.dataset.value || '0', 10);
            if (!target) return;
            const obj = { v: 0 };
            gsap.to(obj, {
                v: target,
                duration: 2,
                ease: 'power2.out',
                onUpdate: () => { el.textContent = Math.round(obj.v); },
            });
        });
    }, { threshold: 0, rootMargin: '0px 0px -10% 0px' });
    document.querySelectorAll('[data-fx="counter"]').forEach((el) => observer.observe(el));

    /* === 6. REVEAL ON SCROLL (fade + slide up) ===
       immediateRender:false → si el ScrollTrigger no dispara por la razón que sea,
       el elemento se queda en su estado natural (visible), no en opacity:0 */
    document.querySelectorAll('[data-fx="reveal"]').forEach((el) => {
        gsap.from(el, {
            y: 80,
            opacity: 0,
            duration: 1,
            ease: 'power3.out',
            immediateRender: false,
            scrollTrigger: { trigger: el, start: 'top bottom-=50', once: true },
        });
    });

    /* === 7. REVEAL STAGGER (grupos) === */
    document.querySelectorAll('[data-fx="reveal-stagger"]').forEach((group) => {
        gsap.from(group.children, {
            y: 60,
            opacity: 0,
            scale: 0.95,
            duration: 0.9,
            ease: 'power3.out',
            stagger: 0.1,
            immediateRender: false,
            scrollTrigger: { trigger: group, start: 'top bottom-=20', once: true },
        });
    });

    /* === 8. TITULARES BIG: SCRUB SLIDE === */
    document.querySelectorAll('[data-fx="title-slide"]').forEach((el) => {
        gsap.fromTo(el,
            { x: '-15%', opacity: 0.5 },
            {
                x: '0%',
                opacity: 1,
                ease: 'none',
                scrollTrigger: {
                    trigger: el,
                    start: 'top bottom',
                    end: 'top 30%',
                    scrub: 1,
                },
            }
        );
    });

    /* === 9. TILT 3D CARDS === */
    document.querySelectorAll('[data-fx="tilt"]').forEach((card) => {
        const max = 6;
        card.addEventListener('mousemove', (e) => {
            const r = card.getBoundingClientRect();
            const x = ((e.clientX - r.left) / r.width - 0.5) * 2;
            const y = ((e.clientY - r.top) / r.height - 0.5) * 2;
            gsap.to(card, {
                rotationX: -y * max,
                rotationY: x * max,
                transformPerspective: 1200,
                duration: 0.6,
                ease: 'power2.out',
            });
        });
        card.addEventListener('mouseleave', () => {
            gsap.to(card, { rotationX: 0, rotationY: 0, duration: 0.7, ease: 'power3.out' });
        });
    });

    /* === 10. HEADER BACKDROP ON SCROLL === */
    const header = document.querySelector('header.sticky');
    if (header) {
        ScrollTrigger.create({
            start: 'top -10',
            onUpdate: (self) => {
                header.classList.toggle('backdrop-blur', self.scroll() > 50);
            },
        });
    }

    /* === 11. ANUNCIO LATERAL PROGRESS — barra roja arriba según scroll === */
    let progressBar = document.querySelector('[data-fx="progress-bar"]');
    if (!progressBar) {
        progressBar = document.createElement('div');
        progressBar.setAttribute('data-fx', 'progress-bar');
        progressBar.className = 'fixed top-0 left-0 h-[3px] bg-algeciras-red z-[60] origin-left';
        progressBar.style.width = '100%';
        progressBar.style.transform = 'scaleX(0)';
        document.body.appendChild(progressBar);
    }
    gsap.to(progressBar, {
        scaleX: 1,
        ease: 'none',
        scrollTrigger: {
            trigger: document.body,
            start: 'top top',
            end: 'bottom bottom',
            scrub: true,
        },
    });

    /* === Refresh ScrollTrigger tras inicializar todo === */
    ScrollTrigger.refresh();

    /* === FAILSAFE: cualquier elemento con opacity:0 atascado, lo dejamos visible tras 4s === */
    setTimeout(() => {
        document.querySelectorAll('[data-fx="reveal"], [data-fx="reveal-stagger"]').forEach((el) => {
            const opacity = parseFloat(getComputedStyle(el).opacity);
            if (opacity < 0.5) {
                gsap.to(el, { y: 0, opacity: 1, scale: 1, duration: 0.5, ease: 'power2.out' });
            }
            // Para reveal-stagger: revisar también children
            if (el.dataset.fx === 'reveal-stagger') {
                [...el.children].forEach((child) => {
                    if (parseFloat(getComputedStyle(child).opacity) < 0.5) {
                        gsap.to(child, { y: 0, opacity: 1, scale: 1, duration: 0.5, ease: 'power2.out' });
                    }
                });
            }
        });
    }, 4000);
}

/* ====== Boot ====== */
function boot() {
    initSmoothScroll();
    initFX();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

// Re-init en navegación SPA Livewire
document.addEventListener('livewire:navigated', () => {
    ScrollTrigger.refresh();
});

// Expose globals para debug en consola
window.gsap = gsap;
window.ScrollTrigger = ScrollTrigger;
