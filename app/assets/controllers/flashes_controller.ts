import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    declare readonly flashTargets: NodeListOf<HTMLParagraphElement>;
    declare readonly containerTarget: HTMLDivElement;

    static targets = ['flash', 'container'];

    connect() {
        // flashes passed on from the backend
        if (this.flashTargets.length) {
            this.element.appendChild(this.containerTarget);
            this.flashTargets.forEach((flash) => {
                if (flash.dataset.permanent) {
                    flash.classList.add('is-shown');
                } else {
                    flash.classList.add('is-flashing');
                    setTimeout(() => flash.remove(), 5500);
                }
            });
        }

        // frontend generated flashes
        window.addEventListener('toggleFlashEvent', (text) => this.toggleFlash(text));
    }

    toggleFlash(text) {
        if ('content' in document.createElement('template')) {
            const template: HTMLTemplateElement | null = document.querySelector('#flash-message');
            const clone: Node | undefined = template?.content.cloneNode(true);
            const textNode: Text = document.createTextNode(text.detail.content ?? 'An unexpected error has occured.');
            if (clone !== undefined) {
                this.containerTarget.appendChild(clone);
                const element = this.containerTarget.lastElementChild as HTMLElement;
                if (element) {
                    element.appendChild(textNode);
                    element.classList.add(`is-${text.detail.type}`);
                    if (element.dataset.permanent) {
                        element.classList.add('is-shown');
                    } else {
                        element.classList.add('is-flashing');
                        setTimeout(() => element.remove(), 5500);
                    }
                }
            }
        }
    }

    closeFlash(event) {
        const target = event.currentTarget;
        target.closest('.flashes__message').classList.remove('is-shown');
        target.closest('.flashes__message').classList.remove('is-flashing');
        target.closest('.flashes__message').classList.add('is-hidden');
    }
}
