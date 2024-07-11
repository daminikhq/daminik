import {Controller} from '@hotwired/stimulus';
import axios from 'axios';

export default class extends Controller {
    declare readonly formTarget: HTMLFormElement;

    static get targets() {
        return ['form'];
    }

    connect() {
        this.connectForm(this.formTarget);
    }

    submit() {
        this.formTarget.submit();
    }

    requestSubmit() {
        this.formTarget.requestSubmit();
    }

    connectForm(form) {
        form.addEventListener('submit', (event) => this.submitForm(event));
    }

    submitForm(event) {
        event.preventDefault();
        const currentForm = event.target;

        const url = currentForm.getAttribute('action') ?? window.location.href;

        if (url) {
            const submit = currentForm.querySelector('[type="submit"]');
            submit?.classList.add('is-loading');
            submit?.setAttribute('disabled', 'true');
            axios(url, {
                method: currentForm.getAttribute('method') ?? 'post',
                data: new FormData(currentForm, event.submitter),
                headers: {'X-Requested-With': 'XMLHttpRequest'},
            })
                .then((response) => {
                    submit?.classList.remove('is-loading');
                    submit?.removeAttribute('disabled');
                    if (response.data.message) {
                        window.dispatchEvent(
                            new CustomEvent(
                                'toggleFlashEvent',
                                {detail: {content: response.data.message, type: 'success'}},
                            ),
                        );
                    }
                    if (response.data.status === 'ok') {
                        if (response.data.redirectTo) {
                            window.location.href = response.data.redirectTo;
                        }
                    }
                })
                .catch((response) => {
                    submit?.classList.remove('is-loading');
                    submit?.removeAttribute('disabled');
                    window.dispatchEvent(
                        new CustomEvent(
                            'toggleFlashEvent',
                            {detail: {content: response.data.message, type: 'error'}},
                        ),
                    );
                });
        }
    }
}
