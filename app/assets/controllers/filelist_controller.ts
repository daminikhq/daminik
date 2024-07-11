import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
    declare readonly modalnewfolderTarget: HTMLDivElement;
    declare readonly modalnewcollectionTarget: HTMLDivElement;

    static targets = ['modalnewfolder', 'modalnewcollection'];

    newFolder() {
        const modalController = this.application.getControllerForElementAndIdentifier(
            this.modalnewfolderTarget,
            'modal',
        );
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        modalController?.open();
    }

    newCollection() {
        const modalController = this.application.getControllerForElementAndIdentifier(
            this.modalnewcollectionTarget,
            'modal',
        );
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        modalController?.open();
    }
}
