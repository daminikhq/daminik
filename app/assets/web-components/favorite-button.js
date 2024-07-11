/* eslint max-len: 0 */
const template = document.createElement('template');
template.innerHTML = `
<style>
    .favorite-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
        background-color: transparent;
    }

    .favorite-button:focus {
        outline: none;
    }

    .favorite-button:focus-visible {
        outline-offset: -2px;
        outline-style: solid;
        outline-width: 2px;
        outline-color: #1D4ED8;
        border-radius: 8px;
    }

    .favorite-button svg {
        pointer-events: none;
        /*margin-top: 0.25rem;*/
    }

    .favorite-button path {
        stroke: var(--favorite-button-stroke-color, #FFFFFF);
        fill: var(--favorite-button-fill-color, transparent);
        transition: stroke 0.2s ease-out, fill 0.2s ease-out;
    }

    .favorite-button:hover path {
        stroke: var(--favorite-button-stroke-color, #FFFFFF);
        fill: var(--favorite-button-fill-color, rgba(255,255,255,0.5));
        transition: stroke 0.2s ease-out, fill 0.2s ease-out;
    }

    .visually-hidden {
        border: 0;
        clip: rect(1px, 1px, 1px, 1px);
        clip-path: inset(50%);
        height: 1px;
        margin: -1px;
        overflow: hidden;
        padding: 0;
        position: absolute;
        width: 1px;
        word-wrap: normal !important;
    }
</style>
<button type="button" class="favorite-button">
    <svg aria-hidden="true" width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path class="favorite-button__star" fill-rule="evenodd" clip-rule="evenodd" d="M5.44272 16.3606C5.02016 16.5811 4.50896 16.5423 4.12457 16.2604C3.74018 15.9785 3.54946 15.5027 3.6328 15.0333L4.30725 11.1676L1.46773 8.44643C1.12065 8.11544 0.993448 7.6151 1.14031 7.15853C1.28717 6.70196 1.68222 6.36961 2.15718 6.30304L6.09966 5.73947L7.87957 2.18883C8.09061 1.763 8.52478 1.49361 9.00003 1.49361C9.47529 1.49361 9.90946 1.763 10.1205 2.18883L11.9004 5.73947L15.8429 6.30304C16.3178 6.36961 16.7129 6.70196 16.8598 7.15853C17.0066 7.6151 16.8794 8.11544 16.5323 8.44643L13.6928 11.1676L14.3673 15.0342C14.4506 15.5035 14.2599 15.9794 13.8755 16.2613C13.4911 16.5431 12.9799 16.582 12.5573 16.3614L9.00003 14.5231L5.44272 16.3606Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span class="favorite-button__label visually-hidden"></span>
</button>`;

/**
 * Web Component "Favorite Button"
 */
class FavoriteButton extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({mode: 'open'});
        this.shadowRoot.appendChild(template.content.cloneNode(true));
    }

    static get observedAttributes() {
        return ['label', 'pressed', 'color'];
    }

    dispatchFavoriteEvent() {
        this.dispatchEvent(new CustomEvent('favoriteButtonClicked', {
            composed: true,
            bubbles: true,
        }));
    }

    connectedCallback() {
        const label = this.getAttribute('label');
        const pressed = this.getAttribute('pressed');
        const color = this.getAttribute('color');
        this.shadowRoot.querySelector('.favorite-button')
            .setAttribute('aria-pressed', pressed);
        this.shadowRoot.querySelector('.favorite-button__label').innerHTML = label;
        this.shadowRoot.querySelector('.favorite-button__star')
            .setAttribute('stroke', color);
        if (pressed) {
            this.shadowRoot.querySelector('.favorite-button__star')
                .setAttribute('fill', color);
        }

        this.shadowRoot.querySelector('.favorite-button')
            .addEventListener('click', () => this.dispatchFavoriteEvent());
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name === 'label') {
            this.shadowRoot.querySelector('.favorite-button__label').innerHTML = newValue;
        } else if (name === 'pressed') {
            this.shadowRoot.querySelector('.favorite-button')
                .setAttribute('aria-pressed', newValue);
        } else if (name === 'color') {
            const pressed = this.getAttribute('pressed');
            this.shadowRoot.querySelector('.favorite-button__star')
                .setAttribute('stroke', newValue);
            if (pressed) {
                this.shadowRoot.querySelector('.favorite-button__star')
                    .setAttribute('fill', newValue);
            } else {
                this.shadowRoot.querySelector('.favorite-button__star')
                    .setAttribute('fill', 'transparet');
            }
        }
    }
}

window.customElements.define('favorite-button', FavoriteButton);
