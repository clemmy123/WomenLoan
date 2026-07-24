import './accessibility-settings';
import './locale-switch';
import './bootstrap';
import './flash-messages';
import './password-requirements';
import './form-loading';
import './app-select';
import './app-date';
import './app-kebab';
import './document-upload';
import './identity-inputs';
import './age-display';
import './amount-inputs';
import './track-id-copy';
import './pages/loan-wizard';
import './pages/nida-register';
import './pages/admin-user-form';
import './pages/reports-filters';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

requestAnimationFrame(() => Alpine.start());
