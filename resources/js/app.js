import './locale-switch';
import './bootstrap';
import './form-loading';
import './app-select';
import './document-upload';
import './identity-inputs';
import './amount-inputs';
import './track-id-copy';
import './pages/loan-wizard';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

requestAnimationFrame(() => Alpine.start());
