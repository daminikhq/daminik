import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    declare readonly currentSelectionTarget: HTMLSpanElement;
    declare readonly submitTarget: HTMLButtonElement;
    declare readonly containerTarget: HTMLDivElement;
    declare readonly multiactionTarget: HTMLSelectElement;
    declare readonly itemTargets: NodeListOf<HTMLInputElement>;

    static get targets() {
        return ['currentSelection', 'submit', 'container', 'multiaction', 'item'];
    }

    connect() {
        this.submitTarget.setAttribute('disabled', 'true');
        this.multiactionTarget.setAttribute('disabled', 'true');
        this.containerTarget.classList.add('is-js-hidden');

        this.multiactionTarget.addEventListener('change', () => {
            if (this.multiactionTarget.value) {
                this.submitTarget.removeAttribute('disabled');
            } else {
                this.submitTarget.setAttribute('disabled', 'true');
            }
        });

        this.connectItems();
    }

    connectItems() {
        this.itemTargets.forEach((itemTarget) => {
            itemTarget.addEventListener('change', () => {
                const checkedItems = this.element.querySelectorAll('input[type="checkbox"]:checked');
                if (checkedItems.length) {
                    this.containerTarget.classList.remove('is-js-hidden');
                    const text = this.currentSelectionTarget.dataset.selectedText || 'Asset(s)';
                    this.currentSelectionTarget.innerHTML = `${checkedItems.length} ${text}`;
                    this.multiactionTarget.removeAttribute('disabled');
                } else {
                    this.containerTarget.classList.add('is-js-hidden');
                    this.currentSelectionTarget.innerHTML = '';
                    this.multiactionTarget.value = '';
                    this.multiactionTarget.setAttribute('disabled', 'true');
                    this.submitTarget.setAttribute('disabled', 'true');
                }
            });
        });
    }
}
