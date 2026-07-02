import './bootstrap';
import './form-loading';
import './app-select';
import './pages/loan-wizard';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

requestAnimationFrame(() => Alpine.start());
