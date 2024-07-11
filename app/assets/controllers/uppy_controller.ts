import Uppy from '@uppy/core';
import Dashboard from '@uppy/dashboard';
import XHR from '@uppy/xhr-upload';
import '@uppy/core/dist/style.min.css';
import '@uppy/dashboard/dist/style.min.css';
import German from '@uppy/locales/lib/de_DE';
import English from '@uppy/locales/lib/en_US';
import {Controller} from '@hotwired/stimulus';
import axios from 'axios';

const lang = document.querySelector('html')?.getAttribute('lang');
const defaultFileTypes = ['.png', '.jpg', '.jpeg', '.gif', '.webp', '.svg'];

export default class extends Controller {
    declare readonly wrapperTarget: HTMLDivElement;
    declare readonly triggerTarget: HTMLLinkElement | null;

    static get targets() {
        return ['wrapper', 'trigger'];
    }

    async fetchFiletypes() {
        let filetypes = [''];
        const response = await fetch('/filetypes.json');
        const json = await response.json();
        filetypes = json.filetypes;
        return filetypes;
    }

    newUppy(id, filetypes, action, dashboardOptions) {
        return new Uppy({
            id,
            locale: lang === 'de' ? German : English,
            restrictions: {
                allowedFileTypes: filetypes,
            },
            logger: {
                debug: () => {},
                warn: (...args) => this.sendLog('warning', ...args),
                error: (...args) => this.sendLog('error', ...args),
            },
        })
            .use(Dashboard, dashboardOptions)
            .use(XHR, {
                endpoint: action,
                fieldName: 'file',
                limit: 1,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Daminik-Context': this.triggerTarget?.dataset.uploadcontext ?? 'home',
                },
            });
    }

    sendLog(level, ...args) {
        axios('/fe/logger', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            data: {
                level,
                message: args.toString(),
            },
        })
            .catch(() => {});
    }

    initializeUpload(filetypes) {
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        if (this.hasTriggerTarget) {
            this.triggerTarget?.setAttribute('href', '#');
            const action = this.triggerTarget?.dataset.url;
            if (!action) {
                return;
            }
            const id = this.triggerTarget?.getAttribute('id');
            const homeUrl = this.triggerTarget?.dataset.homeurl;
            const options = {
                inline: false,
                trigger: id ? `#${id}` : '',
                proudlyDisplayPoweredByUppy: false,
                doneButtonHandler: () => {
                    if (homeUrl != null) {
                        window.location.href = homeUrl;
                    }
                },
            };

            const uppyInstance = this.newUppy('baseUppy', filetypes, action, options);
            uppyInstance.on('complete', () => {
                if (homeUrl != null) {
                    window.location.href = homeUrl;
                }
            });
        } else {
            const uppy = this.element.querySelector('#uppy');
            if (!uppy || !(uppy instanceof HTMLElement)) {
                return;
            }
            const {action} = uppy.dataset;
            if (!action) {
                return;
            }
            const homeUrl = uppy.dataset.homeurl;
            const options = {
                inline: true,
                target: this.wrapperTarget,
                proudlyDisplayPoweredByUppy: false,
                width: '100%',
                doneButtonHandler: () => {
                    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                    // @ts-ignore
                    this.uppy.cancelAll();
                    if (homeUrl != null) {
                        window.location.href = homeUrl;
                    } else {
                        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                        // @ts-ignore
                        this.requestCloseModal();
                    }
                },
            };

            const uppyInstance = this.newUppy('pageUppy', filetypes, action, options);
            uppyInstance.on('complete', () => {
                if (homeUrl != null) {
                    window.location.href = homeUrl;
                } else {
                    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                    // @ts-ignore
                    this.requestCloseModal();
                }
            });
        }
    }

    connect() {
        this.fetchFiletypes().then((filetypes) => {
            this.initializeUpload(filetypes);
        }).catch(() => {
            this.initializeUpload(defaultFileTypes);
        });
    }
}
