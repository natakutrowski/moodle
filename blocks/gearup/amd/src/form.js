// This file is part of Level Up Quest.
//
// Level Up Quest is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Level Up Quest is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Level Up Quest.  If not, see <https://www.gnu.org/licenses/>.
//
// https://levelup.plus

/**
 * Form utils.
 *
 * @module     block_gearup/form
 * @copyright  2024 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import log from 'core/log';

const isEqual = (currentValue, testAgainstValues) => {
    return testAgainstValues.includes(currentValue);
};

const isNotEqual = (currentValue, testAgainstValues) => {
    return !testAgainstValues.includes(currentValue);
};

/**
 * Disable select options based on another field.
 *
 * @param {String} formId
 * @param {String} selectName
 * @param {String|NUmber|((String|NUmber)[])} selectValues
 * @param {String} dependentOnFieldName
 * @param {String|NUmber|((String|NUmber)[])} dependentOnFieldValues
 * @param {Function} tester The tester function.
 */
function disableOptionsWhenFieldTest(formId, selectName, selectValues, dependentOnFieldName, dependentOnFieldValues, tester) {
    const form = document.getElementById(formId);
    if (!form) {
        log.warn(`Could not find form with ID ${formId}`);
        return;
    }

    const dependentOnElement = form.querySelector(`[name="${dependentOnFieldName}"]`);
    const dependentValues = [].concat(dependentOnFieldValues).map(String);
    const targetElement = form.querySelector(`[name="${selectName}"]`);
    const targetValues = [].concat(selectValues).map(String);

    if (!dependentOnElement || !targetElement || !dependentValues.length || !targetValues.length) {
        log.warn('Invalid configuration of disableOptionsWhenFieldEquals');
        return;
    }

    /**
     * Update field.
     */
    function updateField() {
        const targetOptions = Array.from(targetElement.querySelectorAll('option')).filter(function(el) {
            return targetValues.includes(el.getAttribute('value'));
        });
        if (!tester(dependentOnElement.value, dependentValues)) {
            targetOptions.forEach(function(opt) {
                opt.removeAttribute('disabled');
            });
            return;
        }

        targetOptions.forEach(function(opt) {
            opt.setAttribute('disabled', 'disabled');
        });
        const isDisabledOptionSelected = targetOptions.some((opt) => targetElement.value === opt.getAttribute('value'));
        if (isDisabledOptionSelected) {
            targetElement.value = '';
        }
    }
    dependentOnElement.addEventListener('change', () => {
        updateField();
    });
    updateField();
}

/**
 * Disable select options based on another field.
 *
 * @param {String} formId
 * @param {String} selectName
 * @param {(String|NUmber)[]} selectValues
 * @param {String} dependentOnFieldName
 * @param {(String|NUmber)[]} dependentOnFieldValues
 */
export function disableOptionsWhenFielEquals(formId, selectName, selectValues, dependentOnFieldName, dependentOnFieldValues) {
    disableOptionsWhenFieldTest(formId, selectName, selectValues, dependentOnFieldName, dependentOnFieldValues, isEqual);
}
/**
 * Disable select options based on another field.
 *
 * @param {String} formId
 * @param {String} selectName
 * @param {(String|NUmber)[]} selectValues
 * @param {String} dependentOnFieldName
 * @param {(String|NUmber)[]} dependentOnFieldValues
 */
export function disableOptionsWhenFieldNotEquals(formId, selectName, selectValues, dependentOnFieldName, dependentOnFieldValues) {
    disableOptionsWhenFieldTest(formId, selectName, selectValues, dependentOnFieldName, dependentOnFieldValues, isNotEqual);
}