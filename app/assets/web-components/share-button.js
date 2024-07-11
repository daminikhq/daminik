/* eslint max-len: 0 */
const template = document.createElement('template');
template.innerHTML = `
<style>
    .share-button {
        display: inline-flex;
        align-items: center;
        justify-content: var(--share-button-justify-content, center);
        flex-direction: var(--share-button-flex-direction, row);
        padding: var(--share-button-padding, 0 0.25rem);
        grid-gap: 0.25rem;
        gap: 0.25rem;
        border: 0;
        width: 100%;
        cursor: pointer;
        background-color: transparent;
        border-radius: 4px;
        font-family: var(--share-button-font-family, (Arial, sans-serif));
        font-size: var(--share-button-font-size, 0.825rem);
        font-weight: var(--share-button-font-weight, 600);
        color: var(--share-button-text-color, #222222);
        min-height: var(--share-button-min-height, 100%);
    }

    .share-button:focus {
        outline: none;
    }

    .share-button:focus-visible {
        outline-offset: 1px;
        outline-style: solid;
        outline-width: 2px;
        outline-color: var(--share-button-text-color, #2563EB);
    }

    .share-button__icon-copy {
        width: var(--share-button-icon-width, auto);
        height: var(--share-button-icon-height, auto);
        margin: var(--share-button-icon-margin, 0);
    }

    .share-button__state-default {
        display: inline;
    }

    .share-button__state-clicked {
        display: none;
    }

    .share-button.share-button--state-clicked .share-button__state-default {
        display: none;
    }

    .share-button.share-button--state-clicked .share-button__state-clicked {
        display: inline;
    }
</style>
<button class="share-button">
    <svg class="share-button__icon-copy share-button__state-default" width="12" height="12" viewBox="0 0 12 12" fill="none"
         xmlns="http://www.w3.org/2000/svg">
        <path fill-rule="evenodd" clip-rule="evenodd" d="M4.25 4.25V2.68578C4.25 1.80907 5.02189 1.25 5.77519 1.25H9.22481C9.60166 1.25 9.9788 1.3816 10.2699 1.63912C10.564 1.89927 10.75 2.27325 10.75 2.68578V6.31422C10.75 7.19093 9.97811 7.75 9.22481 7.75H7.75V9.31413C7.75 10.1942 6.97261 10.75 6.22082 10.75H2.77918C2.02787 10.75 1.251 10.195 1.25 9.31567V5.68587C1.25 4.80583 2.02739 4.25 2.77918 4.25H4.25ZM5.75 4.25V2.75242C5.75693 2.75099 5.7653 2.75 5.77519 2.75H9.22481C9.23404 2.75 9.24254 2.751 9.25 2.75259V6.24758C9.24307 6.24901 9.2347 6.25 9.22481 6.25H7.75V5.68587C7.75 4.80583 6.97261 4.25 6.22082 4.25H5.75ZM6.25 5.75309C6.24199 5.75127 6.23227 5.75 6.22082 5.75H2.77918C2.76773 5.75 2.75801 5.75127 2.75 5.75309V9.24691C2.75801 9.24873 2.76773 9.25 2.77918 9.25H6.22082C6.23227 9.25 6.24199 9.24873 6.25 9.24691V5.75309Z" fill="currentColor"/>
    </svg>
    <svg class="share-button__icon-copy share-button__state-clicked" width="12" height="12" viewBox="0 0 12 12" fill="none"
         xmlns="http://www.w3.org/2000/svg">
        <path id="Vector" fill-rule="evenodd" clip-rule="evenodd" d="M10.5303 2.71967C10.8232 3.01256 10.8232 3.48744 10.5303 3.78033L5.03033 9.28033C4.73744 9.57322 4.26256 9.57322 3.96967 9.28033L1.46967 6.78033C1.17678 6.48744 1.17678 6.01256 1.46967 5.71967C1.76256 5.42678 2.23744 5.42678 2.53033 5.71967L4.5 7.68934L9.46967 2.71967C9.76256 2.42678 10.2374 2.42678 10.5303 2.71967Z" fill="currentColor"/>
    </svg>
    <svg class="share-button__icon-share" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.6194 6.515V15.7087C12.6194 16.1376 13.1393 16.3779 13.4941 16.1129L18.7934 11.516C19.0689 11.3102 19.0689 10.9134 18.7934 10.7076L13.4941 6.1108C13.1393 5.84579 12.6194 6.08607 12.6194 6.515ZM5.04989 18C4.50921 12.8596 8.45101 8.86099 12.6193 8.80707V13.4037C9.35322 13.435 6.53994 14.6347 5.04989 18Z" fill="currentColor"/>
    </svg>

    <span class="share-button__text-share">Share</span>
    <span class="share-button__text-copy share-button__state-default">Copy</span>
    <span class="share-button__text-copy share-button__state-clicked">Copied</span>
</button>`;

/**
 * Kopiert einen Text
 * @param text
 * @returns {Promise<void>}
 */
function copyText(text) {
    return navigator.clipboard.writeText(text);
}

/**
 * Kopiert ein Value oder den Text eines ein HTML Elements und triggert anschließend ein Custom Event.
 * @param button
 * @returns {Promise<void>}
 */
async function copy(button) {
    const url = button.getAttribute('url');

    function trigger() {
        button.dispatchEvent(new CustomEvent('clipboard-copy', {bubbles: true}));
    }

    if (url) {
        await copyText(url);
        trigger();
    } else {
        await copyText(window.location.href);
        trigger();
    }
}

/**
 * Händelt das Click-Event
 * @param event
 */
function clicked(event) {
    if ('share' in navigator) {
        let url = document.location.href;

        const canonicalElement = document.querySelector('link[rel=canonical]');

        if (canonicalElement !== null) {
            url = canonicalElement.href;
        }

        navigator
            .share({url})
            .catch(() => {});
    } else {
        const button = event.currentTarget;
        if (button instanceof HTMLElement) {
            copy(button)
                .then(() => {
                    const {shadowRoot} = button;
                    shadowRoot.querySelector('.share-button')
                        .setAttribute('aria-pressed', 'true');
                    shadowRoot.querySelector('.share-button')
                        .classList.add('share-button--state-clicked');
                    setTimeout(() => {
                        shadowRoot.querySelector('.share-button')
                            .setAttribute('aria-pressed', 'false');
                        shadowRoot.querySelector('.share-button')
                            .classList.remove('share-button--state-clicked');
                    }, 500);
                });
        }
    }
}

/**
 * Web Component "Share Button"
 */
class ShareButton extends HTMLElement {
    constructor() {
        super();

        this.attachShadow({mode: 'open'});
        this.shadowRoot.appendChild(template.content.cloneNode(true));

        this.addEventListener('click', clicked);

        if ('share' in navigator) {
            this.shadowRoot.querySelector('.share-button__icon-copy').remove();
            this.shadowRoot.querySelector('.share-button__text-copy').remove();
            const shareLabel = this.getAttribute('shareLabel');
            this.shadowRoot.querySelector('.share-button__text-share').innerHTML = shareLabel || 'Share';
        } else {
            this.shadowRoot.querySelector('.share-button__icon-share').remove();
            this.shadowRoot.querySelector('.share-button__text-share').remove();
            const label = this.getAttribute('label');
            const pressedLabel = this.getAttribute('pressedLabel');
            this.shadowRoot.querySelector('span.share-button__state-default').innerHTML = label || 'Copy';
            this.shadowRoot.querySelector('span.share-button__state-clicked').innerHTML = pressedLabel || 'Copied';
        }
    }

    connectedCallback() {
        if ('share' in navigator) {
            this.shadowRoot.querySelector('.share-button').removeAttribute('aria-pressed');
        } else {
            this.shadowRoot.querySelector('.share-button').setAttribute('aria-pressed', 'false');
        }
    }
}

/**
 * Share Button wird nur registriert, wenn die Clipboard oder Share API vorhanden ist.
 */
if ('share' in navigator || 'clipboard' in navigator) {
    window.customElements.define('share-button', ShareButton);
}
