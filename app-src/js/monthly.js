(function (global) {
  "use strict";

  function pad2(n) { return String(n).padStart(2, "0"); }

  function monthInRange(year, month, from, to) {
    var start = new Date(Date.UTC(year, month - 1, 1));
    var end = new Date(Date.UTC(year, month, 0, 23, 59, 59));
    if (to && start > to) return false;
    if (from && end < from) return false;
    return true;
  }

  function synthesizeMonth(monthId, Term) {
    var year = Math.floor(monthId / 12);
    var month = (monthId % 12) + 1;
    var len = new Date(Date.UTC(year, month, 0)).getUTCDate();
    var days = [];
    for (var d = 1; d <= len; d++) {
      var wDay = Term.isoWeekday(new Date(Date.UTC(year, month - 1, d)));
      var wd = Term.WEEK_DAYS[wDay] || { fest: false };
      days.push({ d: d, fest: !!wd.fest, pids: [] });
    }
    return {
      year: year, month: month, len: len, min: 1, max: len,
      liv: 0, numDays: 0, hours: 0, tFes: 0, days: days, legend: {}
    };
  }

  function renderMonthly(container, DATA, state, onMonthClick) {
    container.innerHTML = "";

    var projects = DATA.projects || {};
    var excluded = state.excludedProjects || {};
    var Term = global.PscanTerminal;

    function visiblePids(pids) {
      return (pids || []).filter(function (pid) { return !excluded[String(pid)]; });
    }

    var realIds = Object.keys(DATA.monthly || {})
      .map(function (id) { return parseInt(id, 10); })
      .filter(function (id) {
        var m = DATA.monthly[String(id)];
        return monthInRange(m.year, m.month, state.dateFrom, state.dateTo);
      })
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
        if (DATA.monthly[key]) {
          sequence.push(DATA.monthly[key]);
        } else {
          sequence.push(synthesizeMonth(id, Term));
        }
      }
    } else {
      sequence = realIds.map(function (id) { return DATA.monthly[String(id)]; });
    }

    var running = 0;

    sequence.forEach(function (m) {
      running += m.hours;

      var row = document.createElement("div");
      row.className = "term-row";

      var dateLabel = Term.makeSpan(m.year + "/" + pad2(m.month) + " " + pad2(m.min || 1) + "-" + pad2(m.max || m.len) + " ", 15, 0);
      dateLabel.className = "term-date-link";
      dateLabel.addEventListener("click", function () {
        if (onMonthClick) onMonthClick(m.year, m.month);
      });
      row.appendChild(dateLabel);

      row.appendChild(document.createTextNode("│"));

      m.days.forEach(function (d) {
        var pids = visiblePids(d.pids);
        var c = Term.cellFor(pids, d.fest, projects);
        row.appendChild(Term.makeSpan(c.char, c.ink, c.paper));
      });
      // Pareggia tutti i mesi a 31 colonne (come pScan stesso), per non sfalsare
      // le colonne successive (Liv/Nu./Cas/...) tra mesi di lunghezza diversa.
      if (m.days.length < 31) {
        row.appendChild(document.createTextNode(" ".repeat(31 - m.days.length)));
      }

      row.appendChild(document.createTextNode("│"));
      row.appendChild(Term.makeSpan(String(m.liv).padStart(3, " "), m.liv > 4 ? 13 : 4, 0));
      row.appendChild(document.createTextNode("│"));
      row.appendChild(Term.makeSpan(String(m.numDays).padStart(3, " "), 7, 0));
      row.appendChild(Term.makeSpan(String(m.hours).padStart(4, " "), 15, 0));
      row.appendChild(document.createTextNode("│"));

      var tFesInk = m.tFes > 0 ? (m.tFes > 1 ? 12 : 13) : 8;
      row.appendChild(Term.makeSpan(String(m.tFes).padStart(4, " "), tFesInk, 0));
      row.appendChild(document.createTextNode(" "));
      row.appendChild(Term.makeSpan(String(Math.round(running * 10) / 10).padStart(5, " "), 15, 0));

      var legend = {};
      Object.keys(m.legend || {}).forEach(function (pid) {
        if (!excluded[pid]) legend[pid] = m.legend[pid];
      });
      Term.renderLegend(row, legend, projects);

      container.appendChild(row);
    });
  }

  global.PscanMonthly = { render: renderMonthly };

})(window);
