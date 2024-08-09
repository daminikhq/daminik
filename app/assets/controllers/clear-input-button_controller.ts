import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    declare readonly buttonTarget: HTMLButtonElement;
    declare readonly inputTarget: HTMLInputElement;

    static targets = ['button', 'input'];

    connect() {
        if (!('content' in document.createElement('template'))) {
            return;
        }

        const template: HTMLTemplateElement | null = document.querySelector('#clear-input-button');
        const clone: Node | undefined = template?.content.cloneNode(true);

        if (clone === undefined) {
            return;
        }

        this.element.appendChild(clone);
        this.buttonTarget.addEventListener('click', () => {
            this.inputTarget.value = '';
            this.inputTarget.focus();
            this.element.classList.remove('is-touched');
        });
        this.inputTarget.addEventListener('input', (event) => {
            if ((event.target as HTMLInputElement)?.value && !this.element.classList.contains('is-touched')) {
                this.element.classList.add('is-touched');
            } else if (!(event.target as HTMLInputElement)?.value && this.element.classList.contains('is-touched')) {
                this.element.classList.remove('is-touched');
            }
        });
    }
}
