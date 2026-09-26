// Rich text editor for chapter content. Loaded only on pages with an editor (see x-form.rich-editor).
import Trix from 'trix';
import 'trix/dist/trix.css';

Trix.config.lang = {
    ...Trix.config.lang,
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
};

// Images and files are added as chapter materials, not embedded into the text.
document.addEventListener('trix-file-accept', (event) => event.preventDefault());
