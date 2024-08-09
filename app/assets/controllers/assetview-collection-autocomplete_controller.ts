import {Controller} from '@hotwired/stimulus';
import TomSelect from 'tom-select';
import {TomOptions} from 'tom-select/dist/types/types';

let collectionSelect:TomSelect | null = null;
let collectionSelectOptions:TomOptions | null = null;

export default class extends Controller {
    initialize() {
        this._onConnect = this._onConnect.bind(this);
        this._onAddCollection = this._onAddCollection.bind(this);
    }

    connect() {
        // eslint-disable-next-line @typescript-eslint/unbound-method
        this.element.addEventListener('autocomplete:connect', this._onConnect);
        // eslint-disable-next-line @typescript-eslint/unbound-method
        this.element.addEventListener('assetviewAddCollection', this._onAddCollection);
    }

    disconnect() {
        // eslint-disable-next-line @typescript-eslint/unbound-method
        this.element.removeEventListener('autocomplete:connect', this._onConnect);
        // eslint-disable-next-line @typescript-eslint/unbound-method
        this.element.removeEventListener('assetviewAddCollection', this._onAddCollection);
    }

    _onAddCollection(event) {
        if (!collectionSelect || !collectionSelectOptions) {
            return;
        }
        collectionSelect.addOption(event.detail.option);
        collectionSelect.refreshOptions();
        collectionSelect.addItem(event.detail.slug);
    }

    _onConnect(event) {
        collectionSelect = event.detail.tomSelect;
        collectionSelectOptions = event.detail.options;
    }
}
