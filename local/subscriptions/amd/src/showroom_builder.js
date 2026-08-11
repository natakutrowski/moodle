// This file is part of Moodle - http://moodle.org/

/**
 * Compatibility module for the Showroom builder.
 *
 * The builder runtime is deliberately loaded as a normal script from the
 * plugin's existing js/ directory so a builder failure cannot block the CRM
 * shell AMD modules (including the configured favicon).
 *
 * @module local_subscriptions/showroom_builder
 */
export const init = () => {};
