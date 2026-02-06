import './stimulus_bootstrap.js';
/*
 * Fichier JS principal inclus via importmap().
 */
import './styles/app.css';

// Les CSS/JS Bootstrap sont chargés via CDN dans base.html.twig,
// pas besoin d'importer les modules NPM ici (non résolus par l'importmap).

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
