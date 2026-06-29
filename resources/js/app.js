import './bootstrap';
import './form-loading';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

requestAnimationFrame(() => Alpine.start());
