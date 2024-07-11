import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    static get targets() {
        return ['share'];
    }

    connect() {
        if (!('share' in navigator)) {
            this.element.remove();
        }
    }
}
