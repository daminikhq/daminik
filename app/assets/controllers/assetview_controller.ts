import {Controller} from '@hotwired/stimulus';
import axios from 'axios';
import hotkeys from 'hotkeys-js';

export default class extends Controller {
    declare readonly backbuttonTarget: HTMLAnchorElement;
    declare readonly modalTarget: HTMLDivElement;
    declare readonly modaltitleTarget: HTMLHeadingElement;
    declare readonly modalbodyTarget: HTMLDivElement;
    declare readonly modalOpenerTargets: NodeListOf<HTMLAnchorElement>;
    declare readonly categoryFormTarget: HTMLFormElement;
    declare readonly collectionFormTarget: HTMLFormElement;
    declare readonly categorySelectTarget: HTMLSelectElement;
    declare readonly collectionSelectTarget: HTMLSelectElement;

    static targets = [
        'backbutton',
        'modal',
        'modalOpener',
        'modaltitle',
        'modalbody',
        'categoryForm',
        'collectionForm',
        'categorySelect',
        'collectionSelect',
    ];

    private hasCategoryFormTarget: boolean;
    private hasCollectionFormTarget: boolean;

    connect() {
        hotkeys('esc', (event) => this.close(event));
        hotkeys('right,arrowright', () => this.navigateNext());
        hotkeys('left,arrowleft', () => this.navigatePrev());
        this.modalOpenerTargets.forEach((modalOpener) => {
            const parent = modalOpener.parentElement;
            const button = document.createElement('button');
            button.setAttribute('data-action', 'assetview#openModal');
            button.setAttribute('data-assetview-target', 'modalOpener');
            button.classList.add('link');
            button.setAttribute('type', 'button');
            button.dataset.endpoint = modalOpener.href;
            button.dataset.title = modalOpener.innerText;
            button.innerText = modalOpener.innerText;
            parent?.appendChild(button);
            modalOpener.remove();
        });
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

                    const modalController = this.application.getControllerForElementAndIdentifier(
                        this.modalTarget,
                        'modal',
                    );
                    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                    // @ts-ignore
                    modalController?.close();

                    if (response.data.body) {
                        const option = new Option(response.data.body.title, response.data.body.slug);

                        if (this.hasCategoryFormTarget && currentForm === this.categoryFormTarget) {
                            this.categorySelectTarget.append(option);
                            this.categorySelectTarget.value = response.data.body.slug;
                        } else if (this.hasCollectionFormTarget && currentForm === this.collectionFormTarget) {
                            // option.selected = true;
                            // this.collectionSelectTarget.insertBefore(option, this.collectionSelectTarget.firstChild);

                            this.collectionSelectTarget.dispatchEvent(
                                new CustomEvent(
                                    'assetviewAddCollection',
                                    {detail: {title: response.data.body.title, slug: response.data.body.slug, option}},
                                ),
                            );
                        }

                        if (response.data.message) {
                            window.dispatchEvent(
                                new CustomEvent(
                                    'toggleFlashEvent',
                                    {detail: {content: response.data.message, type: 'success'}},
                                ),
                            );
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

    close(event) {
        event.returnValue = false;
        window.location.href = this.backbuttonTarget.href;
    }

    navigatePrev() {
        const {prevUrl} = (this.element as HTMLElement).dataset;
        if (prevUrl) {
            window.location.href = prevUrl;
        }
    }

    navigateNext() {
        const {nextUrl} = (this.element as HTMLElement).dataset;
        if (nextUrl) {
            window.location.href = nextUrl;
        }
    }

    openModal(event) {
        const target = event.currentTarget;
        this.modaltitleTarget.innerHTML = target.dataset.title;

        axios(target.dataset.endpoint, {
            headers: {'X-Requested-With': 'XMLHttpRequest'},
        })
            .then((response) => {
                this.modalbodyTarget.innerHTML = response.data;
                const modalController = this.application.getControllerForElementAndIdentifier(
                    this.modalTarget,
                    'modal',
                );
                // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                // @ts-ignore
                modalController?.open();
                // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                // @ts-ignore
                if (this.hasCategoryFormTarget) {
                    this.connectForm(this.categoryFormTarget);
                }
                // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                // @ts-ignore
                if (this.hasCollectionFormTarget) {
                    this.connectForm(this.collectionFormTarget);
                }
            })
            .catch(() => {});
    }
}
