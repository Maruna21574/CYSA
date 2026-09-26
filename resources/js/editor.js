// Rich text editor for chapter content. Loaded only on pages with an editor (see x-form.rich-editor).
import Trix from 'trix';
import 'trix/dist/trix.css';

// Trix freezes its config object, but the lang object inside can be updated in place.
Object.assign(Trix.config.lang, {
    bold: 'Tučné',
    italic: 'Kurzíva',
    strike: 'Preškrtnuté',
    link: 'Odkaz',
    heading1: 'Nadpis',
    quote: 'Citát',
    code: 'Kód',
    bullets: 'Odrážky',
    numbers: 'Číslovanie',
    outdent: 'Zmenšiť odsadenie',
    indent: 'Zväčšiť odsadenie',
    undo: 'Späť',
    redo: 'Znova',
    urlPlaceholder: 'Zadajte adresu (https://…)',
    unlink: 'Zrušiť odkaz',
    remove: 'Odstrániť',
});

// Toolbars already on the page were rendered with the English labels - render them again.
document.querySelectorAll('trix-toolbar').forEach((toolbar) => {
    toolbar.innerHTML = Trix.config.toolbar.getDefaultHTML();
});

// Images and files are added as chapter materials, not embedded into the text.
document.addEventListener('trix-file-accept', (event) => event.preventDefault());
