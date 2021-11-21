/* global window */
import Injector from 'lib/Injector'; // Provided by Silverstripe
import NamedLinkFormField from "./components/NamedLinkFormField";

require('./legacy/LinkFormField.entwine.js');

// export default () => { };

window.document.addEventListener('DOMContentLoaded', () => {

    Injector.component.registerMany({
        NamedLinkFormField,
    });
});
