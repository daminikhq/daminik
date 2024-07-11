import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    declare readonly profileTarget: HTMLButtonElement;

    static get targets() {
        return ['profile'];
    }

    connect() {
        if ('content' in document.createElement('template')) {
            const template: HTMLTemplateElement | null = document.querySelector('#profile-menu');
            const clone: Node | undefined = template?.content.cloneNode(true);
            if (clone !== undefined) {
                this.profileTarget.appendChild(clone);
            }
        }
    }
}
