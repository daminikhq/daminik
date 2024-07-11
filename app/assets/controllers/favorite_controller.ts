import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.removeAttribute('hidden');
        this.element.addEventListener('favoriteButtonClicked', (event) => this.toggleFavorite(event));
    }

    toggleFavorite(event) {
        const element = event.currentTarget;

        fetch(`/file/favorite/${element.dataset.filename}`, {
            method: 'POST',
        }).then((response) => {
            if (response.ok) {
                return response.json();
            }

            return Promise.reject(response);
        }).then((data) => {
            if (data.favorite) {
                element.setAttribute('pressed', 'true');
                element.setAttribute('label', element.dataset.pressedLabel);
            } else {
                element.setAttribute('pressed', 'false');
                element.setAttribute('label', element.dataset.unpressedLabel);
            }
        }).catch(() => {});
    }
}
