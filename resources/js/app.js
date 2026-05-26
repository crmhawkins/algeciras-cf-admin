import './bootstrap';

// NOTA: NO importar Alpine. Livewire 4 ya trae Alpine integrado y lo inicializa.
// Si lo importamos aparece warning "Detected multiple instances of Alpine running"
// que rompe Livewire (cart counter no refresca, etc).

// Motion FX (parallax, reveal, counters, tilt...)
import './effects.js';

// Exponer GSAP global para debug
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
window.gsap = gsap;
window.ScrollTrigger = ScrollTrigger;
