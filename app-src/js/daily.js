(function (global) {
  "use strict";

  function parseDate(s) {
    var parts = s.split("/");
    if (parts.length !== 3) return null;
    return new Date(Date.UTC(parseInt(parts[2], 10), parseInt(parts[1], 10) - 1, parseInt(parts[0], 10)));
  }

  function dayInRange(dateStr, from, to) {
    var d = parseDate(dateStr);
    if (!d) return true;
    if (from && d < from) return false;
    if (to && d > to) return false;
    return true;
  }

  function emptyHours() {
    var hours = [];
    for (var h = 0; h < 24; h++) hours.push({ h: h, pids: [] });
    return hours;
  }

  function synthesizeDay(dayId, Term) {
    var date = new Date(dayId * 86400000);
    var wDay = Term.isoWeekday(date);
    var wd = Term.WEEK_DAYS[wDay] || { fest: false };
    return {
      date: Term.formatDate(date),
      wDay: wDay,
      fest: !!wd.fest,
      cont: 0,
      tot: 0,
      totSum: 0, // sovrascritto dal riporto del totale precedente
      hours: emptyHours(),
      legend: {}
    };
  }

  function renderDaily(container, DATA, state, scrollToMonth) {
    container.innerHTML = "";

    var projects = DATA.projects || {};
    var excluded = state.excludedProjects || {};
    var Term = global.PscanTerminal;
    var WD = Term.WEEK_DAYS;
    var HM = Term.HOUR_MAP;

    var hourFrom = (state.hourFrom === null || state.hourFrom === undefined) ? 0 : state.hourFrom;
    var hourTo = (state.hourTo === null || state.hourTo === undefined) ? 23 : state.hourTo;

    function visiblePids(pids) {
      return (pids || []).filter(function (pid) { return !excluded[String(pid)]; });
    }

    function inHourRange(h) { return h >= hourFrom && h <= hourTo; }

    var realIds = Object.keys(DATA.daily || {})
      .filter(function (dayId) { return dayInRange(DATA.daily[dayId].date, state.dateFrom, state.dateTo); })
      .map(function (dayId) { return parseInt(dayId, 10); })
      .sort(function (a, b) { return a - b; });

    if (!realIds.length) {
      container.textContent = "Nessun dato per il periodo selezionato.";
      return;
    }

    var sequence = [];

    if (state.showEmptyEntries) {
      var minId = realIds[0];
      var maxId = realIds[realIds.length - 1];
      for (var id = minId; id <= maxId; id++) {
        var key = String(id);
        if (DATA.daily[key]) {
          sequence.push({ dayId: id, day: DATA.daily[key] });
        } else {
          sequence.push({ dayId: id, day: synthesizeDay(id, Term) });
        }
      }
    } else {
      sequence = realIds.map(function (id) { return { dayId: id, day: DATA.daily[String(id)] }; });
    }

    var rendered = {};
    var lastTotSum = 0;

    sequence.forEach(function (entry) {
      var day = entry.day;
      if (DATA.daily[String(entry.dayId)]) {
        lastTotSum = day.totSum;
      } else {
        day.totSum = lastTotSum;
      }

      var row = document.createElement("div");
      row.className = "term-row";
      row.dataset.dayId = entry.dayId;

      row.appendChild(Term.makeSpan(day.date + " ", 15, 0));

      var wd = WD[day.wDay] || { letter: "?", color: 0 };
      row.appendChild(Term.makeSpan(wd.letter, wd.color || 8, 0));
      row.appendChild(Term.makeSpan(day.fest ? "■" : "·", day.fest ? 13 : 3, 0));
      row.appendChild(document.createTextNode(" "));

      row.appendChild(document.createTextNode("│"));

      day.hours.forEach(function (hEntry) {
        if (state.showHourLabels && HM[hEntry.h]) {
          row.appendChild(Term.makeSpan(HM[hEntry.h].letter + " ", 3, 0));
        }
        var pids = inHourRange(hEntry.h) ? visiblePids(hEntry.pids) : [];
        var c = Term.cellFor(pids, day.fest, projects);
        row.appendChild(Term.makeSpan(c.char, c.ink, c.paper));
      });

      row.appendChild(document.createTextNode("│"));
      row.appendChild(Term.makeSpan(String(day.tot).padStart(3, " "), 3, 0));
      row.appendChild(document.createTextNode("│"));
      row.appendChild(Term.makeSpan(String(day.totSum).padStart(5, " "), 15, 0));

      var legend = {};
      day.hours.forEach(function (hEntry) {
        if (!inHourRange(hEntry.h)) return;
        visiblePids(hEntry.pids).forEach(function (pid) {
          var key = String(pid);
          legend[key] = (legend[key] || 0) + 1;
        });
      });
      Term.renderLegend(row, legend, projects);

      container.appendChild(row);
      rendered[entry.dayId] = row;
    });

    if (scrollToMonth) {
      var targetRow = null;
      var targetId = null;
      Object.keys(rendered).forEach(function (dayId) {
        var day = DATA.daily[dayId] || sequence.find(function (e) { return e.dayId === parseInt(dayId, 10); }).day;
        var d = parseDate(day.date);
        if (!d) return;
        if (d.getUTCFullYear() === scrollToMonth.year && (d.getUTCMonth() + 1) === scrollToMonth.month) {
          if (targetId === null || parseInt(dayId, 10) < targetId) {
            targetId = parseInt(dayId, 10);
            targetRow = rendered[dayId];
          }
        }
      });
      if (targetRow) {
        var cRect = container.getBoundingClientRect();
        var rRect = targetRow.getBoundingClientRect();
        container.scrollTop += (rRect.top - cRect.top);
      }
    }
  }

  global.PscanDaily = { render: renderDaily };

})(window);
