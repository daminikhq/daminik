import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    declare readonly tabTargets;
    declare readonly tabPanelTargets: [];

    static targets = ['tab', 'tabPanel'];

    initialize() {
        this.showTab();
    }

    change(event) {
        this.index = this.tabTargets.findIndex((element) => element === event.target.parentNode);
        this.showTab();
        event.preventDefault();
    }

    showTab() {
        this.tabPanelTargets.forEach((element: HTMLElement, index: number) => {
            if (index === this.index) {
                element.classList.remove('is-hidden');
            } else {
                element.classList.add('is-hidden');
            }
        });

        this.tabTargets.forEach((element: HTMLElement, index: number) => {
            if (index === this.index) {
                element.classList.add('is-active');
            } else {
                element.classList.remove('is-active');
            }
        });
    }

    get index() {
        return parseInt(this.data.get('index') as string, 10);
    }

    set index(value: number) {
        this.data.set('index', value.toString());
        this.showTab();
    }
}
