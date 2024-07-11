import {Controller} from '@hotwired/stimulus';
import axios from 'axios';

export default class extends Controller {
    declare readonly statusTarget: HTMLDivElement;

    static get targets() {
        return ['status'];
    }

    connect() {
        const {statusurl, formurl} = (this.element as HTMLDivElement).dataset;
        const {ajaxText, manualText} = this.statusTarget.dataset;
        if (!statusurl) {
            this.statusTarget.innerText = manualText ?? 'Please refresh the page.';
            return;
        }

        this.statusTarget.classList.add('is-active');
        this.statusTarget.innerText = ajaxText ?? 'Please wait.';

        this.waitForStatusChange(statusurl, formurl, manualText);
    }

    loadForm(formurl) {
        axios(formurl, {
            headers: {'X-Requested-With': 'XMLHttpRequest'},
        })
            .then((response) => {
                this.element.innerHTML = response.data;
            })
            .catch(() => {});
    }

    waitForStatusChange(statusurl, formurl, manualText) {
        axios(statusurl)
            .then((response) => {
                if (response.data.status === 'ok') {
                    this.loadForm(formurl);
                } else {
                    setTimeout(() => {
                        this.waitForStatusChange(statusurl, formurl, manualText);
                    }, 1000);
                }
            })
            .catch(() => {
                this.statusTarget.innerText = manualText ?? 'Please refresh the page.';
            });
    }
}
