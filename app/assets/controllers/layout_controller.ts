import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    declare readonly layoutTarget: HTMLDivElement;
    declare readonly burgerButtonTarget: HTMLButtonElement;
    declare readonly burgerMenuTarget: HTMLDivElement;

    static get targets() {
        return ['layout', 'burgerButton', 'burgerMenu'];
    }

    connect() {
        this.burgerButtonTarget.setAttribute('aria-expanded', 'false');
    }

    toggleSubsidebar() {
        this.layoutTarget.classList.toggle('has-subsidebar');
        if (this.layoutTarget.classList.contains('has-subsidebar')) {
            const filter = this.layoutTarget.querySelector('#filters') as HTMLElement;
            filter.focus();
        } else {
            const header = this.layoutTarget.querySelector('#header') as HTMLElement;
            header.focus();
        }
    }
    toggleMenu() {
        this.layoutTarget.classList.toggle('has-menu');
        window.document.querySelector('body')?.classList.toggle('scrolllock');
        this.burgerMenuTarget.classList.toggle('is-visible');
        this.burgerButtonTarget.classList.toggle('is-open');
        this.burgerButtonTarget.setAttribute(
            'aria-expanded',
            this.burgerMenuTarget.classList.contains('is-visible') ? 'true' : 'false',
        );
    }
}
