import {Controller} from '@hotwired/stimulus';
import axios from 'axios';

let paginationObserver : IntersectionObserver | null = null;

export default class extends Controller {
    declare readonly multiactionsTarget: HTMLDivElement;
    declare readonly classicPaginationTarget: HTMLDivElement;
    declare readonly fileGridWrapperTarget: HTMLDivElement;
    declare readonly loadMoreButtonTarget: HTMLButtonElement;

    static get targets() {
        return ['multiactions', 'classicPagination', 'loadMoreButton', 'fileGridWrapper'];
    }

    connect() {
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        if (this.hasClassicPaginationTarget) {
            this.classicPaginationTarget.remove();
        }

        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        if (this.hasLoadMoreButtonTarget) {
            paginationObserver = new IntersectionObserver(
                (entries) => this.observeLoadMoreButton(entries),
                {
                    root: document.querySelector('#main'),
                    threshold: 0.5,
                },
            );
            paginationObserver?.observe(this.loadMoreButtonTarget as HTMLElement);
        }
    }

    observeLoadMoreButton(entries) {
        entries.forEach((each) => {
            if (each.isIntersecting) {
                this.loadMore();
            }
        });
    }

    loadMore() {
        if (!this.loadMoreButtonTarget.dataset.url) {
            return;
        }

        this.loadMoreButtonTarget.setAttribute('disabled', 'true');
        paginationObserver?.disconnect();
        axios(this.loadMoreButtonTarget.dataset.url, {
            headers: {'X-Requested-With': 'XMLHttpRequest'},
        })
            .then((response) => {
                this.fileGridWrapperTarget.insertAdjacentHTML('beforeend', response.data.html);
                const multiactionsController = this.application.getControllerForElementAndIdentifier(
                    this.multiactionsTarget,
                    'multiactions',
                );
                // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                // @ts-ignore
                multiactionsController?.connectItems();
                if (response.data.nextPage) {
                    this.loadMoreButtonTarget.dataset.url = response.data.nextPage;
                    this.loadMoreButtonTarget.removeAttribute('disabled');
                    paginationObserver?.observe(this.loadMoreButtonTarget);
                } else {
                    this.loadMoreButtonTarget.remove();
                }
            })
            .catch(() => {});
    }
}
