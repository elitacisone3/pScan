(function () {
  "use strict";

  var DATA = window.PSCAN_DATA || { weekly: {}, projectWeekly: {}, projects: {} };
  var State = window.PscanState;
  var Charts = window.PscanCharts;
  var Palette = window.PscanPalette;

  var weeklySids = Object.keys(DATA.weekly);
  var weeklyRows = weeklySids.map(function (sid) {
    var r = Object.assign({ sid: sid }, DATA.weekly[sid]);
    r._date = parseItDate(r.date);
    return r;
  });

  var projectList = Object.keys(DATA.projects).map(function (pid) {
    return DATA.projects[pid];
  }).sort(function (a, b) { return a.id - b.id; });

  var allDates = weeklyRows.map(function (r) { return r._date; }).filter(Boolean);
  var overallMinDate = allDates.length ? new Date(Math.min.apply(null, allDates)) : null;
  var overallMaxDate = allDates.length ? new Date(Math.max.apply(null, allDates)) : null;

  var allYears = Array.from(new Set(Object.keys(DATA.daily || {}).map(function (dayId) {
    var d = parseItDate(DATA.daily[dayId].date);
    return d ? d.getUTCFullYear() : null;
  }).filter(function (y) { return y !== null; }))).sort(function (a, b) { return a - b; });
  var hasMultipleYears = allYears.length > 1;

  var echartInstances = {};

  function parseItDate(s) {
    if (!s) return null;
    var parts = s.split("/");
    if (parts.length !== 3) return null;
    return new Date(Date.UTC(parseInt(parts[2], 10), parseInt(parts[1], 10) - 1, parseInt(parts[0], 10)));
  }

  function toInputDate(d) {
    if (!d) return "";
    var y = d.getUTCFullYear();
    var m = String(d.getUTCMonth() + 1).padStart(2, "0");
    var day = String(d.getUTCDate()).padStart(2, "0");
    return y + "-" + m + "-" + day;
  }

  function pad2(n) { return String(n).padStart(2, "0"); }

  function fmtInt(n) { return Math.round(n).toLocaleString("it-IT"); }

  function weekLabel(r) { return r.year + "-W" + pad2(r.week); }

  function visibleProjects() {
    return projectList.filter(function (pr) { return !State.isExcluded(pr.id); });
  }

  function inRange(date, from, to) {
    if (!date) return true;
    if (from && date < from) return false;
    if (to && date > to) return false;
    return true;
  }

  function projectHoursForSids(sids, pid) {
    var sec = 0;
    sids.forEach(function (sid) {
      var m = DATA.projectWeekly[sid];
      if (m && m[pid]) sec += m[pid];
    });
    return sec / 3600;
  }

  function buildWeeklyViewRows(filteredRows) {
    var vis = visibleProjects();
    return filteredRows.map(function (r) {
      var byProject = {};
      vis.forEach(function (pr) {
        byProject[pr.id] = projectHoursForSids([r.sid], pr.id);
      });
      return {
        label: weekLabel(r),
        sids: [r.sid],
        totD: r.totD, totN: r.totN, totF: r.totF, totP: r.totP,
        totDP: r.totDP, totNP: r.totNP, totFP: r.totFP, totPP: r.totPP,
        byProject: byProject
      };
    });
  }

  function buildMonthlyViewRows(filteredRows) {
    var vis = visibleProjects();
    var buckets = {};
    var order = [];

    filteredRows.forEach(function (r) {
      var key = r.year + "-" + pad2(r.month);
      if (!buckets[key]) {
        buckets[key] = {
          label: key, sids: [], totD: 0, totN: 0, totF: 0, totP: 0,
          _pct: { totDP: [], totNP: [], totFP: [], totPP: [] }
        };
        order.push(key);
      }
      var b = buckets[key];
      b.sids.push(r.sid);
      b.totD += r.totD; b.totN += r.totN; b.totF += r.totF; b.totP += r.totP;
      b._pct.totDP.push(r.totDP); b._pct.totNP.push(r.totNP);
      b._pct.totFP.push(r.totFP); b._pct.totPP.push(r.totPP);
    });

    function avg(arr) {
      return arr.length ? Math.round((arr.reduce(function (s, v) { return s + v; }, 0) / arr.length) * 10) / 10 : 0;
    }

    return order.map(function (key) {
      var b = buckets[key];
      var byProject = {};
      vis.forEach(function (pr) {
        byProject[pr.id] = projectHoursForSids(b.sids, pr.id);
      });
      return {
        label: b.label,
        sids: b.sids,
        totD: b.totD, totN: b.totN, totF: b.totF, totP: b.totP,
        totDP: avg(b._pct.totDP), totNP: avg(b._pct.totNP),
        totFP: avg(b._pct.totFP), totPP: avg(b._pct.totPP),
        byProject: byProject
      };
    });
  }

  function renderProjectCheckboxes() {
    var container = document.getElementById("projectCheckboxes");
    container.innerHTML = "";
    var pids = projectList.map(function (p) { return String(p.id); });

    projectList.forEach(function (pr) {
      var wrap = document.createElement("label");
      wrap.className = "project-check";

      var input = document.createElement("input");
      input.type = "checkbox";
      input.checked = !State.isExcluded(pr.id);
      input.addEventListener("change", function () {
        State.toggleProject(pr.id);
        triggerRender();
      });

      var swatch = document.createElement("span");
      swatch.className = "project-swatch";
      swatch.style.background = Charts.colorForProject(pr.id, pids);

      var text = document.createElement("span");
      text.textContent = pr.title || pr.name;

      wrap.appendChild(input);
      wrap.appendChild(swatch);
      wrap.appendChild(text);
      container.appendChild(wrap);
    });
  }

  function renderTotaliTable(scopeSids) {
    var tbody = document.querySelector("#totaliTable tbody");
    tbody.innerHTML = "";

    var vis = visibleProjects();
    var totals = {};
    var grand = 0;

    vis.forEach(function (pr) {
      var h = projectHoursForSids(scopeSids, pr.id);
      totals[pr.id] = h;
      grand += h;
    });

    var sorted = vis.slice().sort(function (a, b) { return totals[b.id] - totals[a.id]; });
    var pids = vis.map(function (p) { return String(p.id); });

    sorted.forEach(function (pr) {
      var h = totals[pr.id];
      var pct = grand > 0 ? Math.round((h / grand) * 1000) / 10 : 0;
      var sym = pr.symbol || {};

      var tr = document.createElement("tr");

      var tdSym = document.createElement("td");
      var swatch = document.createElement("span");
      swatch.className = "project-swatch";
      swatch.style.background = Charts.colorForProject(pr.id, pids);
      tdSym.appendChild(swatch);
      var symSpan = document.createElement("span");
      symSpan.className = "term-symbol";
      symSpan.style.color = Palette.color(sym.inkIndex || 0);
      symSpan.style.background = Palette.color(sym.paperIndex || 0);
      symSpan.textContent = sym.char || "?";
      tdSym.appendChild(symSpan);

      var tdName = document.createElement("td");
      tdName.textContent = pr.title || pr.name;

      var tdType = document.createElement("td");
      tdType.textContent = pr.typeName || pr.typeClass || "";

      var tdHours = document.createElement("td");
      tdHours.textContent = (Math.round(h * 10) / 10).toLocaleString("it-IT");

      var tdPct = document.createElement("td");
      tdPct.textContent = pct.toLocaleString("it-IT") + "%";

      tr.appendChild(tdSym); tr.appendChild(tdName); tr.appendChild(tdType);
      tr.appendChild(tdHours); tr.appendChild(tdPct);
      tbody.appendChild(tr);
    });

    return totals;
  }

  function visiblePidsAt(hEntry, visIds) {
    for (var i = 0; i < hEntry.pids.length; i++) {
      if (visIds[String(hEntry.pids[i])]) return true;
    }
    return false;
  }

  // Aggregazione condivisa tra le Statistiche (periodo selezionato) e la tab
  // Annuale (stessa logica, ripetuta per ogni anno del dataset).
  function computeStatsForDayIds(dayIds, hourFrom, hourTo, visIds) {
    var projectHours = {};
    var grandTotal = 0;
    var peakDay = null;
    var peakDayHours = -1;

    var activeDayCount = 0;
    var festTotal = 0;
    var festActiveDayCount = 0;
    var dayHistBuckets = new Array(25).fill(0); // indice 1..24 usato
    var hourBuckets = new Array(24).fill(0);

    dayIds.forEach(function (dayId) {
      var day = DATA.daily[dayId];
      var dayTotal = 0;
      var distinctHours = {};
      var anyHourActive = {};

      day.hours.forEach(function (hEntry) {
        var hourHasVisible = visiblePidsAt(hEntry, visIds);
        if (hourHasVisible) anyHourActive[hEntry.h] = true;

        if (hEntry.h < hourFrom || hEntry.h > hourTo) return;
        hEntry.pids.forEach(function (pid) {
          var key = String(pid);
          if (!visIds[key]) return;
          projectHours[key] = (projectHours[key] || 0) + 1;
          dayTotal++;
          grandTotal++;
          distinctHours[hEntry.h] = true;
        });
      });

      if (dayTotal > peakDayHours) { peakDayHours = dayTotal; peakDay = day; }

      var distinctCount = Object.keys(distinctHours).length;
      if (distinctCount > 0) {
        activeDayCount++;
        dayHistBuckets[Math.min(distinctCount, 24)]++;
        if (day.fest) { festTotal += dayTotal; festActiveDayCount++; }
      }

      Object.keys(anyHourActive).forEach(function (h) { hourBuckets[parseInt(h, 10)]++; });
    });

    var peakProjectId = null;
    var peakProjectHours = -1;
    Object.keys(projectHours).forEach(function (pid) {
      if (projectHours[pid] > peakProjectHours) { peakProjectHours = projectHours[pid]; peakProjectId = pid; }
    });

    return {
      projectHours: projectHours,
      grandTotal: grandTotal,
      peakDay: peakDay,
      peakDayHours: peakDayHours,
      activeDayCount: activeDayCount,
      festTotal: festTotal,
      festActiveDayCount: festActiveDayCount,
      dayHistBuckets: dayHistBuckets,
      hourBuckets: hourBuckets,
      peakProjectId: peakProjectId,
      peakProjectHours: peakProjectHours
    };
  }

  function currentHourRange() {
    var st = State.get();
    var hourFrom = (st.hourFrom === null || st.hourFrom === undefined) ? 0 : st.hourFrom;
    var hourTo = (st.hourTo === null || st.hourTo === undefined) ? 23 : st.hourTo;
    if (hourFrom > hourTo) { var tmp = hourFrom; hourFrom = hourTo; hourTo = tmp; }
    return { from: hourFrom, to: hourTo };
  }

  function renderStats() {
    var container = document.getElementById("statsGrid");
    var st = State.get();

    var hr = currentHourRange();
    var hourFrom = hr.from, hourTo = hr.to;
    var hoursPerDay = hourTo - hourFrom + 1;

    var vis = visibleProjects();
    var visIds = {};
    vis.forEach(function (pr) { visIds[String(pr.id)] = true; });

    var dayIds = Object.keys(DATA.daily || {}).filter(function (dayId) {
      return inRange(parseItDate(DATA.daily[dayId].date), st.dateFrom, st.dateTo);
    });

    var s = computeStatsForDayIds(dayIds, hourFrom, hourTo, visIds);

    var rangeStart = st.dateFrom || overallMinDate;
    var rangeEnd = st.dateTo || overallMaxDate;
    var numDays = (rangeStart && rangeEnd) ? Math.round((rangeEnd - rangeStart) / 86400000) + 1 : 0;
    var totalAvailableHours = numDays * hoursPerDay;
    var pct = totalAvailableHours > 0 ? Math.round((s.grandTotal / totalAvailableHours) * 1000) / 10 : 0;

    var avgAll = s.activeDayCount > 0 ? Math.round((s.grandTotal / s.activeDayCount) * 10) / 10 : 0;
    var avgFest = s.festActiveDayCount > 0 ? Math.round((s.festTotal / s.festActiveDayCount) * 10) / 10 : 0;

    var peakProject = s.peakProjectId !== null ? DATA.projects[s.peakProjectId] : null;

    function tile(label, value, detail) {
      return '<div class="stat-tile"><div class="label">' + label + '</div>' +
        '<div class="value">' + value + '</div>' +
        (detail ? '<div class="detail">' + detail + '</div>' : '') + '</div>';
    }

    container.innerHTML =
      tile("Totale progetti", fmtInt(s.grandTotal) + " ore") +
      tile("Totale ore", fmtInt(totalAvailableHours) + " ore", numDays + " giorni × " + hoursPerDay + " h") +
      tile("Percentuale", pct.toLocaleString("it-IT") + "%") +
      tile("Picco massimo", s.peakDay ? (fmtInt(s.peakDayHours) + " ore") : "–", s.peakDay ? s.peakDay.date : "nessun dato") +
      tile("Progetto di picco", peakProject ? (peakProject.title || peakProject.name) : "–",
        peakProject ? (fmtInt(s.peakProjectHours) + " ore") : "") +
      tile("Media ore/giorno (tutto)", avgAll.toLocaleString("it-IT") + " ore", s.activeDayCount + " giorni con dati") +
      tile("Media ore/giorno (festivi)", avgFest.toLocaleString("it-IT") + " ore", s.festActiveDayCount + " giorni festivi con dati");

    echartInstances.dayHist = echarts.init(document.getElementById("dayHistChart"));
    echartInstances.dayHist.setOption(Charts.buildDayHoursHistogramOption(s.dayHistBuckets.slice(1, 25)));

    echartInstances.hourHist = echarts.init(document.getElementById("hourHistChart"));
    echartInstances.hourHist.setOption(Charts.buildHourOfDayHistogramOption(s.hourBuckets));
  }

  function isLeapYear(y) { return (y % 4 === 0 && y % 100 !== 0) || y % 400 === 0; }

  function renderAnnualCharts() {
    if (!hasMultipleYears) return;

    var hr = currentHourRange();
    var hourFrom = hr.from, hourTo = hr.to;
    var hoursPerDay = hourTo - hourFrom + 1;

    var vis = visibleProjects();
    var visIds = {};
    vis.forEach(function (pr) { visIds[String(pr.id)] = true; });

    var byYear = {};
    allYears.forEach(function (y) { byYear[y] = []; });
    Object.keys(DATA.daily || {}).forEach(function (dayId) {
      var d = parseItDate(DATA.daily[dayId].date);
      if (!d) return;
      var y = d.getUTCFullYear();
      if (byYear[y]) byYear[y].push(dayId);
    });

    var labels = allYears.map(String);
    var totals = [], pcts = [], peaks = [], peakDates = [], avgs = [], avgFests = [];

    allYears.forEach(function (y) {
      var s = computeStatsForDayIds(byYear[y], hourFrom, hourTo, visIds);
      var daysInYear = isLeapYear(y) ? 366 : 365;
      var totalAvail = daysInYear * hoursPerDay;

      totals.push(Math.round(s.grandTotal * 10) / 10);
      pcts.push(totalAvail > 0 ? Math.round((s.grandTotal / totalAvail) * 1000) / 10 : 0);
      peaks.push(s.peakDay ? s.peakDayHours : -1);
      peakDates.push(s.peakDay ? s.peakDay.date : null);
      avgs.push(s.activeDayCount > 0 ? Math.round((s.grandTotal / s.activeDayCount) * 10) / 10 : 0);
      avgFests.push(s.festActiveDayCount > 0 ? Math.round((s.festTotal / s.festActiveDayCount) * 10) / 10 : 0);
    });

    echartInstances.yearTotal = echarts.init(document.getElementById("yearTotalChart"));
    echartInstances.yearTotal.setOption(Charts.buildYearlyLineOption(labels, totals, "Ore"));

    echartInstances.yearPct = echarts.init(document.getElementById("yearPctChart"));
    echartInstances.yearPct.setOption(Charts.buildYearlyLineOption(labels, pcts, "%", "%"));

    echartInstances.yearPeak = echarts.init(document.getElementById("yearPeakChart"));
    echartInstances.yearPeak.setOption(Charts.buildYearlyPeakOption(labels, peaks, peakDates));

    echartInstances.yearAvg = echarts.init(document.getElementById("yearAvgChart"));
    echartInstances.yearAvg.setOption(Charts.buildYearlyLineOption(labels, avgs, "Ore/giorno"));

    echartInstances.yearAvgFest = echarts.init(document.getElementById("yearAvgFestChart"));
    echartInstances.yearAvgFest.setOption(Charts.buildYearlyLineOption(labels, avgFests, "Ore/giorno"));
  }

  function disposeCharts() {
    Object.keys(echartInstances).forEach(function (k) { echartInstances[k].dispose(); });
    echartInstances = {};
  }

  function triggerRender() {
    if (State.get().liveUpdate) recomputeAndRender();
  }

  function triggerTerminalRender() {
    if (State.get().liveUpdate) renderTerminalTabs();
  }

  function recomputeAndRender() {
    var st = State.get();

    var filtered = weeklyRows.filter(function (r) { return inRange(r._date, st.dateFrom, st.dateTo); });
    var viewRows = st.aggregationMode === "monthly" ? buildMonthlyViewRows(filtered) : buildWeeklyViewRows(filtered);

    var zoomWindow = Charts.defaultZoomWindow(viewRows.length);
    var vis = visibleProjects();

    disposeCharts();

    echartInstances.project = echarts.init(document.getElementById("projectChart"));
    echartInstances.project.setOption(Charts.buildProjectTotalsOption(viewRows, vis, zoomWindow));

    echartInstances.weekly = echarts.init(document.getElementById("weeklyChart"));
    echartInstances.weekly.setOption(Charts.buildWeeklyOption(viewRows, zoomWindow));

    echartInstances.pct = echarts.init(document.getElementById("pctChart"));
    echartInstances.pct.setOption(Charts.buildPctOption(viewRows, zoomWindow));

    ["project", "weekly", "pct"].forEach(function (k) { echartInstances[k].group = "attivita"; });
    echarts.connect("attivita");

    var pieSids = st.pieRelativeMode === "all" ? weeklySids : filtered.map(function (r) { return r.sid; });
    var totals = renderTotaliTable(pieSids);

    echartInstances.pie = echarts.init(document.getElementById("pieChart"));
    echartInstances.pie.setOption(Charts.buildTotaliPieOption(totals, vis));

    renderStats();
    renderAnnualCharts();
    renderTerminalTabs();
  }

  function renderTerminalTabs() {
    var st = State.get();
    var monthlyBox = document.getElementById("monthlyBox");
    var dailyBox = document.getElementById("dailyBox");

    window.PscanMonthly.render(monthlyBox, DATA, st, function (year, month) {
      activateTab("giornaliero");
      window.PscanDaily.render(dailyBox, DATA, State.get(), { year: year, month: month });
    });

    window.PscanDaily.render(dailyBox, DATA, st, null);
  }

  function setupOptionsPanel() {
    var minDate = overallMinDate;
    var maxDate = overallMaxDate;

    var fromInput = document.getElementById("dateFrom");
    var toInput = document.getElementById("dateTo");

    if (minDate) { fromInput.min = toInput.min = toInputDate(minDate); }
    if (maxDate) { fromInput.max = toInput.max = toInputDate(maxDate); }

    fromInput.addEventListener("change", function () {
      State.set({ dateFrom: fromInput.value ? new Date(fromInput.value + "T00:00:00Z") : null });
      triggerRender();
    });

    toInput.addEventListener("change", function () {
      State.set({ dateTo: toInput.value ? new Date(toInput.value + "T23:59:59Z") : null });
      triggerRender();
    });

    document.getElementById("aggregationMode").addEventListener("change", function (e) {
      State.set({ aggregationMode: e.target.value });
      triggerRender();
    });

    var hourFromInput = document.getElementById("hourFrom");
    var hourToInput = document.getElementById("hourTo");

    function parseHourInput(input) {
      if (input.value === "") return null;
      var n = parseInt(input.value, 10);
      if (isNaN(n)) return null;
      return Math.min(Math.max(n, 0), 23);
    }

    hourFromInput.addEventListener("change", function () {
      State.set({ hourFrom: parseHourInput(hourFromInput) });
      triggerRender();
    });

    hourToInput.addEventListener("change", function () {
      State.set({ hourTo: parseHourInput(hourToInput) });
      triggerRender();
    });

    document.getElementById("clearHourFilter").addEventListener("click", function () {
      hourFromInput.value = "";
      hourToInput.value = "";
      State.set({ hourFrom: null, hourTo: null });
      triggerRender();
    });

    document.getElementById("resetFilters").addEventListener("click", function () {
      fromInput.value = "";
      toInput.value = "";
      hourFromInput.value = "";
      hourToInput.value = "";
      document.getElementById("aggregationMode").value = "weekly";
      document.getElementById("dailyShowHourLabels").checked = false;
      document.getElementById("dailyShowEmpty").checked = true;
      document.getElementById("monthlyShowEmpty").checked = true;
      document.getElementById("liveUpdate").checked = true;
      State.set({
        dateFrom: null, dateTo: null, hourFrom: null, hourTo: null,
        aggregationMode: "weekly", excludedProjects: {},
        showHourLabels: false, showEmptyEntries: true, liveUpdate: true
      });
      renderProjectCheckboxes();
      recomputeAndRender();
    });

    document.querySelectorAll('input[name="pieRelativeMode"]').forEach(function (radio) {
      radio.addEventListener("change", function (e) {
        if (e.target.checked) {
          State.set({ pieRelativeMode: e.target.value });
          triggerRender();
        }
      });
    });

    document.getElementById("selectAllProjects").addEventListener("click", function () {
      State.set({ excludedProjects: {} });
      renderProjectCheckboxes();
      triggerRender();
    });

    document.getElementById("selectNoneProjects").addEventListener("click", function () {
      var all = {};
      projectList.forEach(function (pr) { all[String(pr.id)] = true; });
      State.set({ excludedProjects: all });
      renderProjectCheckboxes();
      triggerRender();
    });

    document.getElementById("liveUpdate").addEventListener("change", function (e) {
      State.set({ liveUpdate: e.target.checked });
      if (e.target.checked) recomputeAndRender();
    });

    document.getElementById("dailyShowHourLabels").addEventListener("change", function (e) {
      State.set({ showHourLabels: e.target.checked });
      triggerTerminalRender();
    });

    document.getElementById("dailyShowEmpty").addEventListener("change", function (e) {
      State.set({ showEmptyEntries: e.target.checked });
      document.getElementById("monthlyShowEmpty").checked = e.target.checked;
      triggerTerminalRender();
    });

    document.getElementById("monthlyShowEmpty").addEventListener("change", function (e) {
      State.set({ showEmptyEntries: e.target.checked });
      document.getElementById("dailyShowEmpty").checked = e.target.checked;
      triggerTerminalRender();
    });
  }

  function activateTab(tabKey) {
    document.querySelectorAll(".tab-btn").forEach(function (b) {
      b.classList.toggle("active", b.dataset.tab === tabKey);
    });
    document.querySelectorAll(".tab-panel").forEach(function (p) {
      p.classList.toggle("active", p.id === "tab-" + tabKey);
    });
    Object.keys(echartInstances).forEach(function (k) { echartInstances[k].resize(); });
  }

  function setupTabs() {
    document.querySelectorAll(".tab-btn").forEach(function (btn) {
      btn.addEventListener("click", function () { activateTab(btn.dataset.tab); });
    });
  }

  function setupAnnualTab() {
    if (hasMultipleYears) return;
    var btn = document.getElementById("tabAnnualeBtn");
    var panel = document.getElementById("tab-annuale");
    if (btn) btn.remove();
    if (panel) panel.remove();
  }

  window.addEventListener("resize", function () {
    Object.keys(echartInstances).forEach(function (k) { echartInstances[k].resize(); });
  });

  window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", function () {
    recomputeAndRender();
  });

  renderProjectCheckboxes();
  setupOptionsPanel();
  setupTabs();
  setupAnnualTab();
  recomputeAndRender();

})();
