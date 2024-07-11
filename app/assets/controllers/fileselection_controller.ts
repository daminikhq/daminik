import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    declare readonly toggleTarget: HTMLButtonElement;
    declare readonly toggleAllTarget: HTMLButtonElement;

    static get targets() {
        return ['toggle', 'toggleAll'];
    }

    connect() {
        this.toggleTarget.classList.remove('is-js-hidden');
    }

    toggleSelection() {
        this.element.classList.toggle('is-selection-enabled');
        this.toggleTarget.toggleAttribute('aria-pressed');
        this.toggleTarget.classList.toggle('is-active');
        if (this.toggleTarget.classList.contains('is-active') && this.toggleTarget.dataset.activeText) {
            this.toggleTarget.innerText = this.toggleTarget.dataset.activeText;
            this.toggleAllTarget.classList.remove('is-js-hidden');
        } else if (this.toggleTarget.dataset.defaultText) {
            this.toggleTarget.innerText = this.toggleTarget.dataset.defaultText;
            this.toggleAllTarget.classList.add('is-js-hidden');
        }
    }

    selectAll() {
        const checkboxes = this.element.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach((box) => {
            (box as HTMLInputElement).checked = true;
        });
        if (checkboxes.length > 0) {
            checkboxes[0].dispatchEvent(new Event('change'));
        }
        this.toggleAllTarget.dataset.action = 'fileselection#deselectAll';
    }

    deselectAll() {
        const checkboxes = this.element.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach((box) => {
            (box as HTMLInputElement).checked = false;
        });
        if (checkboxes.length > 0) {
            checkboxes[0].dispatchEvent(new Event('change'));
        }
        this.toggleAllTarget.dataset.action = 'fileselection#selectAll';
    }
}
