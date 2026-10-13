(function (global) {
  "use strict";

  var listeners = [];

  var state = {
    dateFrom: null,
    dateTo: null,
    hourFrom: null,
    hourTo: null,
    excludedProjects: {},
    aggregationMode: "weekly",
    pieRelativeMode: "selection",
    showHourLabels: false,
    showEmptyEntries: true,
    liveUpdate: true
  };

  function get() { return state; }

  function set(patch) {
    Object.keys(patch).forEach(function (k) { state[k] = patch[k]; });
    listeners.forEach(function (fn) { fn(state); });
  }

  function toggleProject(pid) {
    pid = String(pid);
    if (state.excludedProjects[pid]) delete state.excludedProjects[pid];
    else state.excludedProjects[pid] = true;
    listeners.forEach(function (fn) { fn(state); });
  }

  function isExcluded(pid) { return !!state.excludedProjects[String(pid)]; }

  function subscribe(fn) { listeners.push(fn); }

  global.PscanState = {
    get: get,
    set: set,
    toggleProject: toggleProject,
    isExcluded: isExcluded,
    subscribe: subscribe
  };

})(window);
