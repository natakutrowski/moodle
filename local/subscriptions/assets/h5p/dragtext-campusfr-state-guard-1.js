/* CampusFR DragText 1.10: ignore invalid saved positions, not grades or results. */
(function () {
  'use strict';
  if (!window.H5P || typeof window.H5P.DragText !== 'function') { return; }
  var proto = window.H5P.DragText.prototype;
  var original = proto.setH5PUserState;
  if (typeof original !== 'function' || original.campusfrStateGuard) { return; }
  function valid(instance, state) {
    if (!Array.isArray(state)) { return false; }
    if (!Array.isArray(instance.draggables) || !Array.isArray(instance.droppables)) {
      return true;
    }
    return state.every(function (entry) {
      return entry && Number.isInteger(entry.draggable) && Number.isInteger(entry.droppable) &&
        entry.draggable >= 0 && entry.draggable < instance.draggables.length &&
        entry.droppable >= 0 && entry.droppable < instance.droppables.length &&
        (typeof instance.isValidIndex !== 'function' ||
          (instance.isValidIndex(entry.draggable) && instance.isValidIndex(entry.droppable))) &&
        (typeof instance.getDraggableByInitialIndex !== 'function' ||
          !!instance.getDraggableByInitialIndex(entry.draggable));
    });
  }
  function guarded() {
    if (this.previousState !== undefined && !valid(this, this.previousState)) {
      // In-memory restore only: no SQL DELETE, and Moodle grades are untouched.
      this.previousState = undefined;
      this.campusfrIgnoredInvalidState = true;
      if (window.console && window.console.warn) {
        window.console.warn('CampusFR H5P: incompatible DragText resume state ignored');
      }
      return;
    }
    return original.apply(this, arguments);
  }
  guarded.campusfrStateGuard = true;
  proto.setH5PUserState = guarded;
}());
