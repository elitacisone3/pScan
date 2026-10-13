(function (global) {
  "use strict";

  // $WEEK_DAYS / $HOUR_MAP di pScan (inc/globals.php), trascritti per il rendering web.
  // "fest" è la festività di default (solo Sab/Dom), usata per sintetizzare i giorni/mesi
  // senza dati quando "Mostra anche i giorni/mesi senza dati" è attivo: le festività
  // configurate custom lato server non sono note al browser, questa è un'approssimazione.
  var WEEK_DAYS = {
    1: { letter: "L", color: 0, fest: false },
    2: { letter: "M", color: 0, fest: false },
    3: { letter: "E", color: 0, fest: false },
    4: { letter: "G", color: 0, fest: false },
    5: { letter: "V", color: 0, fest: false },
    6: { letter: "S", color: 4, fest: true },
    7: { letter: "D", color: 13, fest: true }
  };

  var HOUR_MAP = {
    0: { letter: "N", color: 12 },
    7: { letter: "M", color: 15 },
    14: { letter: "P", color: 4 },
    18: { letter: "S", color: 13 },
    22: { letter: "N", color: 12 }
  };

  // Stessa logica di calcDay()/getByMonthData() in inc/calculator.php:
  // 1 progetto -> il suo simbolo; 2+ -> cifra di conteggio (o '+' se >9); nessuno -> punto.
  function cellFor(pids, fest, projects) {
    if (!pids || pids.length === 0) {
      return { char: "·", ink: fest ? 13 : 8, paper: 0 };
    }
    if (pids.length === 1) {
      var pr = projects[pids[0]];
      var sym = pr && pr.symbol;
      if (sym && sym.char) {
        return { char: sym.char, ink: sym.inkIndex, paper: sym.paperIndex };
      }
      return { char: "!", ink: 7, paper: 8 };
    }
    var n = pids.length;
    return { char: n > 9 ? "+" : String(n), ink: 0, paper: 12 };
  }

  function makeSpan(text, ink, paper) {
    var span = document.createElement("span");
    span.textContent = text;
    span.style.color = global.PscanPalette.color(ink);
    if (paper) span.style.background = global.PscanPalette.color(paper);
    return span;
  }

  function renderLegend(container, legend, projects) {
    var entries = Object.keys(legend || {});
    if (!entries.length) return;
    var wrap = document.createElement("span");
    wrap.className = "term-legend";
    entries.forEach(function (pid, idx) {
      var pr = projects[pid];
      if (!pr) return;
      var sym = pr.symbol || {};
      wrap.appendChild(document.createTextNode(" "));
      wrap.appendChild(makeSpan(sym.char || "?", sym.inkIndex || 0, sym.paperIndex || 0));
      wrap.appendChild(document.createTextNode(" "));
      var nameSpan = makeSpan(pr.title || pr.name, idx % 2 ? 8 : 7, 0);
      wrap.appendChild(nameSpan);
    });
    container.appendChild(wrap);
  }

  function isoWeekday(date) {
    var jsDay = date.getUTCDay();
    return jsDay === 0 ? 7 : jsDay;
  }

  function pad2(n) { return String(n).padStart(2, "0"); }

  function formatDate(date) {
    return pad2(date.getUTCDate()) + "/" + pad2(date.getUTCMonth() + 1) + "/" + date.getUTCFullYear();
  }

  global.PscanTerminal = {
    WEEK_DAYS: WEEK_DAYS,
    HOUR_MAP: HOUR_MAP,
    cellFor: cellFor,
    makeSpan: makeSpan,
    isoWeekday: isoWeekday,
    formatDate: formatDate,
    renderLegend: renderLegend
  };

})(window);
