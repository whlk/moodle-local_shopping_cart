// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @package    local_shopping_cart
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Templates from 'core/templates';

import {updateTotalPrice, buttoninit} from 'local_shopping_cart/cart';

import {
    get_string as getString,
    get_strings as getStrings
        }
        from 'core/str';
import {showNotification} from 'local_shopping_cart/notifications';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import ModalForm from 'core_form/modalform';

export const init = (cancelationFee = null) => {

    const buttons = document.querySelectorAll(".cashier-history-items .shopping_cart_history_cancel_button");

    buttons.forEach(button => {

        if (!button.dataset.initialized) {

            if (button.dataset.canceled == true) {
                setButtonToCanceled(button);
            } else {
                button.addEventListener('click', event => {

                    event.preventDefault();
                    event.stopPropagation();

                    if (button.dataset.canceled == false) {

                        // We find out if we are on the cachiers page. Only there, we set the cachiers modal.

                        if (window.location.href.includes('cashier.php')) {
                            confirmCancelAndSetCreditModal(button);
                        } else {
                            // We only add the functionality if we got a cancelation fee.
                            confirmCancelModal(button, cancelationFee);
                        }
                    }

                });
            }
            button.dataset.initialized = true;
        }
    });

    const elements = document.querySelectorAll('button.shopping_cart_history_paidback_button');

    elements.forEach(element => {

        if (!element.dataset.initialized) {
            element.addEventListener('click', event => {

                event.preventDefault();
                event.stopPropagation();

                 confirmPaidBackModal(element);
            });
            element.dataset.initialized = true;
        }
    });
};

/**
 * This triggers the ajax call to acutally cancel the purchase.
 * @param {int} itemid
 * @param {int} userid
 * @param {string} componentname
 * @param {int} historyid
 * @param {string} currency
 * @param {string} price
 * @param {string} credit
 * @param {type} button
 */
function cancelPurchase(itemid, userid, componentname, historyid, currency, price, credit, button) {

    Ajax.call([{
        methodname: "local_shopping_cart_cancel_purchase",
        args: {
            'itemid': itemid,
            'componentname': componentname,
            'userid': userid,
            'historyid': historyid,
            'credit': credit
        },
        done: function(data) {

            if (data.success == 1) {

                getString('cancelsuccess', 'local_shopping_cart').then(message => {

                    showNotification(message, "success");

                    return;
                }).catch(e => {
                    // eslint-disable-next-line no-console
                    console.log(e);
                });

                setButtonToCanceled(button);

                showCredit(data.credit, currency, userid);

                // Make sure addtocartbutton active againe once the item is removed from the shopping cart.
                const addtocartbutton = document.querySelector('#btn-' + componentname + '-' + itemid);

                // If there is not addtocartbutton, we have to add it anew.
                if (!addtocartbutton) {

                    data.itemid = itemid;
                    data.componentname = componentname;
                    data.price = price;

                    Templates.renderForPromise('local_shopping_cart/addtocartdb', data).then(({html}) => {

                        // Get parentelement.
                        let parent = document.querySelector('span.price_' + componentname + "_" + itemid);
                        parent.textContent = price + " " + currency;

                        if (parent) {
                            parent.insertAdjacentHTML('beforeend', html);
                        }

                        buttoninit(itemid, componentname);
                        return true;
                    }).catch((e) => {
                        // eslint-disable-next-line no-console
                        console.log(e);
                    });
                } else {

                     addtocartbutton.classList.remove('disabled');
                     addtocartbutton.dataset.initialized = false;
                     buttoninit(itemid, componentname);
                }

            } else {
                getString('canceldidntwork', 'local_shopping_cart').then(message => {

                    showNotification(message, "danger");

                    return;
                }).catch(e => {
                    // eslint-disable-next-line no-console
                    console.log(e);
                });
            }

        },
        fail: function(ex) {
            // eslint-disable-next-line no-console
            console.log("ex:" + ex);
        },
    }]);

}

/**
 * Function to change classes and text of button.
 * @param {*} button
 */
function setButtonToCanceled(button) {

    button.classList.add('disabled');
    button.classList.remove('btn-primary');
    button.classList.add('btn-danger');
    button.dataset.canceled = true;

    getString('canceled', 'local_shopping_cart').then(result => {

        button.innerText = result;
        return;
    }).catch(e => {
        // eslint-disable-next-line no-console
        console.log(e);
    });
}

/**
 *
 * @param {string} credit
 * @param {string} currency
 * @param {int} userid
 */
function showCredit(credit, currency, userid) {

    let creditelement = document.querySelector('li.shopping_cart_history_paidback');

    if (creditelement) {
        creditelement.classList.remove('hidden');

        let credittotalelement = creditelement.querySelector('span.credit_total');

        credittotalelement.textContent = credit;

    } else {

        let data = {
            'currency': currency,
            'credit': credit,
            'userid': userid
        };

        Templates.renderForPromise('local_shopping_cart/credit_item', data).then(({html}) => {

            // Get parentelement.
            let parent = document.querySelector('ul.cashier-history-items');

            parent.insertAdjacentHTML('afterbegin', html);

            // We rerun init after insert, to make sure we have the right value.
            init();
            return true;
        }).catch((e) => {
            // eslint-disable-next-line no-console
            console.log(e);
        });
    }
    // We also need to call the udpateTotalPrice function from this place to make sure everything is uptodate.
    updateTotalPrice();
}

/**
 *
 * @param {*} element
 */
function confirmPaidBack(element) {
    const userid = element.dataset.userid;
    Ajax.call([{
        methodname: "local_shopping_cart_credit_paid_back",
        args: {
            userid
        },
        done: function(data) {

            // eslint-disable-next-line no-console
            console.log(data);

            let creditelement = document.querySelector('.credit_total');

            creditelement.textContent = 0;

            // We hide the creditelement once we have paid back everything.
            let licreditelement = document.querySelector('.shopping_cart_history_paidback');
            licreditelement.classList.add('hidden');

            getString('creditpaidback', 'local_shopping_cart').then(message => {

                showNotification(message, 'success');

                return;
            }).catch(e => {
                // eslint-disable-next-line no-console
                console.log(e);
            });

             // We also need to call the udpateTotalPrice function from this place to make sure everything is uptodate.
            updateTotalPrice();
            return;
        },
        fail: function(ex) {
        // eslint-disable-next-line no-console
        console.log("ex:" + ex);
        },
    }]);
}

/**
 *
 * @param {*} button
 * @param {*} cancelationFee
 */
function confirmCancelModal(button, cancelationFee) {

    if (cancelationFee === null) {
        cancelationFee = 0;
    }

    getStrings([
            {key: 'confirmcanceltitle', component: 'local_shopping_cart'},
            {key: 'confirmcancelbodyuser', component: 'local_shopping_cart', param: cancelationFee},
            {key: 'cancelpurchase', component: 'local_shopping_cart'}
        ]
        ).then(strings => {

            ModalFactory.create({type: ModalFactory.types.SAVE_CANCEL}).then(modal => {

                modal.setTitle(strings[0]);
                    modal.setBody(strings[1]);
                    modal.setSaveButtonText(strings[2]);
                    modal.getRoot().on(ModalEvents.save, function() {

                        const historyid = button.dataset.historyid;
                        const itemid = button.dataset.itemid;
                        const userid = button.dataset.userid;
                        const currency = button.dataset.currency;
                        const componentname = button.dataset.componentname;
                        const price = button.dataset.price;

                        cancelPurchase(itemid, userid, componentname, historyid, currency, price, 0, button);
                    });

                    modal.show();
                    return modal;
            }).catch(e => {
                // eslint-disable-next-line no-console
                console.log(e);
            });
            return true;
        }).catch(e => {
            // eslint-disable-next-line no-console
            console.log(e);
        });
}

/**
 *
 * @param {*} button
 */
function confirmCancelAndSetCreditModal(button) {

    const price = button.dataset.price;
    const historyid = button.dataset.historyid;
    const itemid = button.dataset.itemid;
    const userid = button.dataset.userid;
    const currency = button.dataset.currency;
    const componentname = button.dataset.componentname;

    const modalForm = new ModalForm({

        // Name of the class where form is defined (must extend \core_form\dynamic_form):
        formClass: "local_shopping_cart\\form\\modal_cancel_addcredit",
        // Add as many arguments as you need, they will be passed to the form:
        args: {'price': price,
               'historyid': historyid,
               'itemid': itemid,
               'userid': userid,
               'currency': currency,
               'componentname': componentname},
        // Pass any configuration settings to the modal dialogue, for example, the title:
        modalConfig: {title: getString('confirmcanceltitle', 'local_shopping_cart')},
        // DOM element that should get the focus after the modal dialogue is closed:
        returnFocus: button
    });
    // Listen to events if you want to execute something on form submit.
    // Event detail will contain everything the process() function returned:
    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (e) => {
        const response = e.detail;
        // eslint-disable-next-line no-console
        console.log(response);
        window.location.reload();
    });

    // Show the form.
    modalForm.show();
}

/**
 *
 * @param {*} element
 */
function confirmPaidBackModal(element) {

    getStrings([
        {key: 'confirmpaidbacktitle', component: 'local_shopping_cart'},
        {key: 'confirmpaidbackbody', component: 'local_shopping_cart'},
        {key: 'confirmpaidback', component: 'local_shopping_cart'}
    ]
    ).then(strings => {

        ModalFactory.create({type: ModalFactory.types.SAVE_CANCEL}).then(modal => {

            modal.setTitle(strings[0]);
                modal.setBody(strings[1]);
                modal.setSaveButtonText(strings[2]);
                modal.getRoot().on(ModalEvents.save, function() {

                    confirmPaidBack(element);
                });

                modal.show();
                return modal;
        }).catch(e => {
            // eslint-disable-next-line no-console
            console.log(e);
        });
        return true;
    }).catch(e => {
        // eslint-disable-next-line no-console
        console.log(e);
    });
}