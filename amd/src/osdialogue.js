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

/**
 * Extend Moodle dialogue.
 *
 * @module mod_openstudio/osdialogue
 * @copyright 2017 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import ModalEvents from 'core/modal_events';
import * as FocusLockManager from 'core/local/aria/focuslock';

export default class osDialogue extends Modal {
    static TYPE = 'mod_openstudio/osdialogue';
    static TEMPLATE = 'mod_openstudio/osdialogue';
    registerEventListeners() {
        // Call the parent registration.
        super.registerEventListeners();

        // Lock tab control inside modal.
        this.getRoot().on(ModalEvents.shown, () => {
            FocusLockManager.trapFocus(document.querySelector('.modal-dialog.openstudio-dialogue'));
        });

        this.getRoot().on(ModalEvents.hidden, () => {
            FocusLockManager.untrapFocus();
        });

        // Register to close on save/cancel.
        this.registerCloseOnCancel();
    }

    configure(modalConfig) {
        super.configure(modalConfig);
    }

    addButton(property, sections) {
        const self = this;
        let footer = self.getFooter()[0];
        sections.forEach(function(section) {
            if (section === 'footer') {
                // Create a button element using native JS.
                const button = document.createElement('button');
                button.className = property.classNames;
                button.textContent = property.label;

                // Add event listeners for 'action' or custom events.
                if (property.action === 'hide') {
                    button.setAttribute('data-action', 'cancel');
                }

                if (property.events) {
                    Object.keys(property.events).forEach(eventType => {
                        button.addEventListener(eventType, function(event) {
                            property.events[eventType].apply(self, [event]);
                        });
                    });
                }

                // Collect the button for footer replacement.
                footer.appendChild(button);
            }
        });
    }
}

osDialogue.registerModalType();
