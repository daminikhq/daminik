import {Controller} from '@hotwired/stimulus';
import {useClickOutside} from 'stimulus-use';
import hotkeys from 'hotkeys-js';

export default class extends Controller {
    declare readonly toggleTarget: HTMLButtonElement;
    declare readonly menuTarget: HTMLUListElement;

    static get targets() {
        return ['toggle', 'menu'];
    }

    connect() {
        useClickOutside(this as Controller);
        hotkeys('esc', () => this.close());
        this.menuTarget.setAttribute('aria-expanded', 'false');
        this.toggleTarget.setAttribute('aria-pressed', 'false');
    }

    disconnect() {
        hotkeys.unbind('esc');
    }

    clickOutside(event) {
        if (this.element.classList.contains('is-open')) {
            event.preventDefault();
            this.close();
        }
    }

    close() {
        this.element.classList.remove('is-open');
        this.menuTarget.setAttribute('aria-expanded', 'false');
        this.toggleTarget.setAttribute('aria-pressed', 'false');
    }

    toggle() {
        this.element.classList.toggle('is-open');
        const state: string = this.element.classList.contains('is-open') ? 'true' : 'false';
        this.menuTarget.setAttribute('aria-expanded', state);
        this.toggleTarget.setAttribute('aria-pressed', state);
    }
}
