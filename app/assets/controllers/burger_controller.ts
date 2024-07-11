import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    declare readonly burgerButtonTarget: HTMLButtonElement;
    declare readonly burgerMenuTarget: HTMLDivElement;

    static get targets() {
        return ['burgerButton', 'burgerMenu'];
    }

    connect() {
        this.burgerButtonTarget.setAttribute('aria-expanded', 'false');
    }

    toggleMenu() {
        this.burgerMenuTarget.classList.toggle('is-visible');
        this.burgerButtonTarget.classList.toggle('is-open');
        this.burgerButtonTarget.setAttribute(
            'aria-expanded',
            this.burgerMenuTarget.classList.contains('is-visible') ? 'true' : 'false',
        );
    }
}
