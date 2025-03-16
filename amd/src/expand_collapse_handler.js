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
 * JavaScript to handle expand/collapse.
 *
 * @module mod_openstudio/expand_collapse_handler
 * @copyright 2024 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Pending from 'core/pending';
import * as Repository from 'core_user/repository';
import Notification from 'core/notification';

let cmid = 0;
let activitiesID = [];
let expanded = {};
let userPreference = '';

/**
 * Initialise function.
 *
 */
export const init = () => {
    initActivitiesID();
    initClickEvent();
    updateExpandCollapseAll();
    cmid = document.querySelector('.openstudio-container').dataset.cmid;
    userPreference = 'mod_openstudio_expanded_' + cmid;
    Repository.getUserPreference(userPreference)
        .then((response) => {
            if (response) {
                expanded[cmid] = JSON.parse(response);
            } else {
                expanded[cmid] = {};
            }
        }).catch(Notification.exception);
};

/**
 * Init list activity id.
 *
 */
const initActivitiesID = () => {
    document.querySelectorAll('h2.openstudio-activity-title').forEach((activity) => {
        activitiesID.push(activity.dataset.id);
    });
};

/**
 * Init click event for expand/collapse link/button.
 *
 */
const initClickEvent = () => {
    document.body.addEventListener('click', (event) => {
        const target = event.target;
        if (target.closest('a.openstudio-expand')) {
            event.preventDefault();
            expandActivity(target.closest('a.openstudio-expand'), false);
        } else if (target.closest('a.openstudio-collapse')) {
            event.preventDefault();
            collapseActivity(target.closest('a.openstudio-collapse'), false);
        } else if (target.closest('#openstudio-expandall')) {
            event.preventDefault();
            expandAll(target.closest('#openstudio-expandall'), true);
        } else if (target.closest('#openstudio-collapseall')) {
            event.preventDefault();
            expandAll(target.closest('#openstudio-collapseall'),false);
        }
    });
};

/**
 * Handler for the expand/collapse all.
 *
 * @param {HTMLElement} button Expand/ Collapse button
 * @param {boolean} expand True if you want to expand all, and vice versa
 */
const expandAll = (button, expand) => {
    const pendingPromise = new Pending("mod_openstudio/expandall");
    const type = expand ? 'expand' : 'collapse';
    const all = document.querySelectorAll('a.openstudio-' + type);
    all.forEach((link) => {
        if (expand) {
            expandActivity(link, true);
        } else {
            collapseActivity(link, true);
        }
    });
    updateExpandCollapseLink(button, false, true);
    storeExpanded(expand, null);
    pendingPromise.resolve();
};

/**
 * Handle expand activity.
 *
 * @param {HTMLElement} link Expand link
 * @param {Boolean} preventFocus Prevent focus state
 */
const expandActivity = (link, preventFocus) => {
    const elid = 'openstudio_grid_' + link.dataset.activity;
    const activity = document.getElementById(elid);
    if (activity.classList.contains('openstudio-expanded')) {
        return;
    }
    const pendingPromise = new Pending("mod_openstudio/expand");
    const currentGrid = window.isotope[elid];
    activity.setAttribute('aria-expanded', 'true');
    // Display activity items before Isotope rearrange.
    activity.style.opacity = '0';
    activity.classList.add('openstudio-expanded');

    setTimeout(() => {
        currentGrid.layout();
        activity.style.opacity = '1';
    }, 50);
    updateExpandCollapseLink(link, preventFocus, false);
    updateExpandCollapseAll();
    if (!preventFocus) {
        storeExpanded(true, link.dataset.activity);
    }
    pendingPromise.resolve();
};

/**
 * Handle collapse activity.
 *
 * @param {HTMLElement} link Collapse link
 * @param {Boolean} preventFocus Prevent focus state
 */
const collapseActivity = (link, preventFocus) => {
    const elid = 'openstudio_grid_' + link.dataset.activity;
    const activity = document.getElementById(elid);
    if (!activity.classList.contains('openstudio-expanded')) {
        return;
    }
    const pendingPromise = new Pending("mod_openstudio/collapse");
    activity.setAttribute('aria-expanded', 'false');
    activity.style.height = '0';
    activity.style.opacity = '0';
    setTimeout(() => {
        activity.classList.remove('openstudio-expanded');
    }, 500);
    updateExpandCollapseLink(link, preventFocus, false);
    updateExpandCollapseAll();
    if (!preventFocus) {
        storeExpanded(false, link.dataset.activity);
    }
    pendingPromise.resolve();
};


/**
 * Switch expand collapse link/button after clicking.
 *
 * @param {HTMLElement} link Expand/Collapse link
 * @param {Boolean} preventFocus Prevent focus after clicking
 * @param {Boolean} preventToggle Prevent toggle class.
 */
const updateExpandCollapseLink = (link, preventFocus, preventToggle) => {
    if(!preventToggle) {
        link.parentNode.classList.toggle('expanded');
    }
    const sibling = link.nextElementSibling ?? link.previousElementSibling;
    if (!preventFocus) {
        sibling.focus();
    }
};

/**
 * Update the text of the expand/collapse all link, based
 * on whether any activities are open.
 *
 */
const updateExpandCollapseAll = () => {
    const links = document.querySelectorAll('div.openstudio-expandcollapse.expanded');
    const expandall = document.querySelector('div.openstudio-expandall');
    if (expandall) {
        if (links.length === 0) {
            expandall.classList.remove('expanded');
        } else {
            expandall.classList.add('expanded');
        }
    }
};

/**
 * User preference called to store expanded state.
 *
 * @param {Boolean} expand True for expanded
 * @param {number|null} activityID Activity id or null to expand all
 */
const storeExpanded = async (expand, activityID) => {
    const pendingPromise = new Pending("mod_openstudio/storeexpanded");
    const all = (activityID === null);
    if (all) {
        activitiesID.forEach(id => {
            expanded[cmid][id] = expand;
        });
    } else {
        expanded[cmid][activityID] = expand;
    }
    await Repository.setUserPreference(userPreference, JSON.stringify(expanded[cmid]));
    pendingPromise.resolve();
};
