import {Controller} from '@hotwired/stimulus';
import hotkeys from 'hotkeys-js';

let body: HTMLBodyElement | null = null;

export default class extends Controller {
    declare readonly backdropTarget: HTMLDivElement;

    static targets = ['backdrop'];

    connect() {
        hotkeys('esc', () => this.close());
        body = window.document.querySelector('body');
        this.backdropTarget.addEventListener('click', () => {
            this.close();
        });
    }

    disconnect() {
        hotkeys.unbind('esc');
    }

    close() {
        body?.classList.remove('scrolllock');
        (this.element as HTMLDialogElement).close();
    }

    open() {
        body?.classList.add('scrolllock');
        (this.element as HTMLDialogElement).showModal();
    }
}
