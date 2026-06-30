import './bootstrap';
import './form-loading';
import './pages/loan-wizard';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

requestAnimationFrame(() => Alpine.start());
