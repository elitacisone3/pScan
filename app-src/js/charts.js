(function (global) {
  "use strict";

  var darkQuery = window.matchMedia("(prefers-color-scheme: dark)");

  var CATEGORIES = [
    { key: "totD", pctKey: "totDP", label: "Totale" },
    { key: "totF", pctKey: "totFP", label: "Festivi" },
    { key: "totN", pctKey: "totNP", label: "Notte" },
    { key: "totP", pctKey: "totPP", label: "Pomeridiani" }
  ];

  var PALETTE = {
    light: { totD: "#2a78d6", totF: "#e34948", totN: "#4a3aa7", totP: "#1baf7a", ink: "#4b4b48", text: "#4b4b48", grid: "#e1e0d9" },
    dark:  { totD: "#3987e5", totF: "#e66767", totN: "#9085e9", totP: "#199e70", ink: "#c8c7c0", text: "#c8c7c0", grid: "#2c2c2a" }
  };

  // Palette categoriale dedicata ai progetti (indipendente dai colori terminale di pScan,
  // che identificano un progetto tramite carattere+due colori insieme, non un singolo swatch).
  var PROJECT_PALETTE = ["#0072B2", "#E69F00", "#009E73", "#D55E00", "#CC79A7", "#56B4E9", "#F0E442", "#8c8c8c"];

  function isDark() {
    var explicit = document.documentElement.getAttribute("data-theme");
    if (explicit === "dark") return true;
    if (explicit === "light") return false;
    return darkQuery.matches;
  }
  function palette() { return isDark() ? PALETTE.dark : PALETTE.light; }
  function colorForCategory(key) { return palette()[key] || palette().ink; }

  function colorForProject(pid, orderedPids) {
    pid = String(pid);
    var idx = orderedPids.indexOf(pid);
    if (idx < 0) idx = 0;
    return PROJECT_PALETTE[idx % PROJECT_PALETTE.length];
  }

  function baseGrid() {
    return { left: 56, right: 24, top: 46, bottom: 78, containLabel: false };
  }

  function baseDataZoom(zoomWindow) {
    return [
      { type: "inside", start: zoomWindow.start, end: zoomWindow.end },
      { type: "slider", start: zoomWindow.start, end: zoomWindow.end, bottom: 10, height: 22 }
    ];
  }

  function baseToolbox(p) {
    return {
      right: 10,
      top: 0,
      itemSize: 14,
      iconStyle: { borderColor: p.text },
      emphasis: { iconStyle: { borderColor: p.totD } },
      feature: {
        dataZoom: { yAxisIndex: "none", title: { zoom: "Zoom", back: "Reset zoom" } },
        restore: { title: "Ripristina" },
        saveAsImage: { title: "Salva immagine" }
      }
    };
  }

  function axisCommon(p) {
    return {
      axisLine: { lineStyle: { color: p.grid } },
      axisLabel: { color: p.text },
      splitLine: { lineStyle: { color: p.grid } }
    };
  }

  function defaultZoomWindow(len) {
    var windowSize = Math.min(len, 104);
    var start = len > 0 ? Math.max(0, 100 - (windowSize / len) * 100) : 0;
    return { start: start, end: 100 };
  }

  function buildWeeklyOption(rows, zoomWindow) {
    var p = palette();
    var labels = rows.map(function (r) { return r.label; });
    return {
      backgroundColor: "transparent",
      textStyle: { color: p.text },
      tooltip: { trigger: "axis" },
      legend: { data: CATEGORIES.map(function (c) { return c.label; }), top: 0, left: 0, textStyle: { color: p.text } },
      toolbox: baseToolbox(p),
      grid: baseGrid(),
      dataZoom: baseDataZoom(zoomWindow),
      xAxis: Object.assign({ type: "category", data: labels, boundaryGap: false }, axisCommon(p)),
      yAxis: Object.assign({ type: "value", name: "Ore" }, axisCommon(p)),
      series: CATEGORIES.map(function (c) {
        return {
          name: c.label,
          type: "line",
          data: rows.map(function (r) { return r[c.key]; }),
          showSymbol: false,
          lineStyle: { width: 2, color: colorForCategory(c.key) },
          itemStyle: { color: colorForCategory(c.key) },
          emphasis: { focus: "series" }
        };
      })
    };
  }

  function buildPctOption(rows, zoomWindow) {
    var p = palette();
    var labels = rows.map(function (r) { return r.label; });
    return {
      backgroundColor: "transparent",
      textStyle: { color: p.text },
      tooltip: {
        trigger: "axis",
        valueFormatter: function (v) { return (Math.round(v * 10) / 10) + "%"; }
      },
      legend: { data: CATEGORIES.map(function (c) { return c.label; }), top: 0, left: 0, textStyle: { color: p.text } },
      toolbox: baseToolbox(p),
      grid: baseGrid(),
      dataZoom: baseDataZoom(zoomWindow),
      xAxis: Object.assign({ type: "category", data: labels, boundaryGap: false }, axisCommon(p)),
      yAxis: Object.assign({ type: "value", name: "%" }, axisCommon(p)),
      series: CATEGORIES.map(function (c) {
        return {
          name: c.label,
          type: "line",
          data: rows.map(function (r) { return r[c.pctKey]; }),
          showSymbol: false,
          lineStyle: { width: 2, color: colorForCategory(c.key) },
          itemStyle: { color: colorForCategory(c.key) },
          emphasis: { focus: "series" }
        };
      })
    };
  }

  function buildProjectTotalsOption(rows, projectList, zoomWindow) {
    var p = palette();
    var labels = rows.map(function (r) { return r.label; });
    var pids = projectList.map(function (pr) { return String(pr.id); });
    return {
      backgroundColor: "transparent",
      textStyle: { color: p.text },
      tooltip: { trigger: "axis" },
      legend: { data: projectList.map(function (pr) { return pr.title || pr.name; }), top: 0, left: 0, textStyle: { color: p.text }, type: "scroll" },
      toolbox: baseToolbox(p),
      grid: baseGrid(),
      dataZoom: baseDataZoom(zoomWindow),
      xAxis: Object.assign({ type: "category", data: labels, boundaryGap: false }, axisCommon(p)),
      yAxis: Object.assign({ type: "value", name: "Ore" }, axisCommon(p)),
      series: projectList.map(function (pr) {
        return {
          name: pr.title || pr.name,
          type: "line",
          data: rows.map(function (r) { return Math.round((r.byProject[pr.id] || 0) * 10) / 10; }),
          showSymbol: false,
          lineStyle: { width: 2, color: colorForProject(pr.id, pids) },
          itemStyle: { color: colorForProject(pr.id, pids) },
          emphasis: { focus: "series" }
        };
      })
    };
  }

  function buildTotaliPieOption(totalsByPid, projectList) {
    var p = palette();
    var pids = projectList.map(function (pr) { return String(pr.id); });
    var data = projectList
      .filter(function (pr) { return (totalsByPid[pr.id] || 0) > 0; })
      .map(function (pr) {
        return {
          name: pr.title || pr.name,
          value: Math.round((totalsByPid[pr.id] || 0) * 10) / 10,
          itemStyle: { color: colorForProject(pr.id, pids) }
        };
      });
    return {
      backgroundColor: "transparent",
      textStyle: { color: p.text },
      tooltip: { trigger: "item", formatter: "{b}: {c}h ({d}%)" },
      series: [{
        type: "pie",
        radius: ["35%", "65%"],
        data: data,
        label: { show: false },
        labelLine: { show: false }
      }]
    };
  }

  function buildSimpleBarOption(labels, values, axisName) {
    var p = palette();
    return {
      backgroundColor: "transparent",
      textStyle: { color: p.text },
      tooltip: { trigger: "axis" },
      grid: { left: 48, right: 16, top: 24, bottom: 36, containLabel: true },
      xAxis: Object.assign({ type: "category", data: labels, name: axisName }, axisCommon(p)),
      yAxis: Object.assign({ type: "value" }, axisCommon(p)),
      series: [{
        type: "bar",
        data: values,
        itemStyle: { color: p.totD }
      }]
    };
  }

  function buildYearlyLineOption(labels, values, yName, valueSuffix) {
    var p = palette();
    return {
      backgroundColor: "transparent",
      textStyle: { color: p.text },
      tooltip: {
        trigger: "axis",
        valueFormatter: valueSuffix ? function (v) { return v + valueSuffix; } : undefined
      },
      grid: { left: 56, right: 24, top: 24, bottom: 36, containLabel: true },
      xAxis: Object.assign({ type: "category", data: labels }, axisCommon(p)),
      yAxis: Object.assign({ type: "value", name: yName }, axisCommon(p)),
      series: [{
        type: "line",
        data: values,
        showSymbol: true,
        symbolSize: 7,
        lineStyle: { width: 2, color: p.totD },
        itemStyle: { color: p.totD }
      }]
    };
  }

  function buildYearlyPeakOption(labels, values, dates) {
    var p = palette();
    return {
      backgroundColor: "transparent",
      textStyle: { color: p.text },
      tooltip: {
        trigger: "axis",
        formatter: function (params) {
          var i = params[0].dataIndex;
          var v = values[i];
          var d = dates[i];
          return params[0].axisValueLabel + "<br/>" +
            (v != null && v >= 0 ? v + " ore" : "nessun dato") +
            (d ? " (" + d + ")" : "");
        }
      },
      grid: { left: 56, right: 24, top: 24, bottom: 36, containLabel: true },
      xAxis: Object.assign({ type: "category", data: labels }, axisCommon(p)),
      yAxis: Object.assign({ type: "value", name: "Ore" }, axisCommon(p)),
      series: [{
        type: "line",
        data: values.map(function (v) { return v >= 0 ? v : 0; }),
        showSymbol: true,
        symbolSize: 7,
        lineStyle: { width: 2, color: p.totD },
        itemStyle: { color: p.totD }
      }]
    };
  }

  function buildDayHoursHistogramOption(buckets) {
    var labels = [];
    for (var i = 1; i <= 24; i++) labels.push(String(i));
    return buildSimpleBarOption(labels, buckets, "Ore totali nel giorno");
  }

  function buildHourOfDayHistogramOption(buckets) {
    var labels = [];
    for (var i = 0; i < 24; i++) labels.push(String(i));
    return buildSimpleBarOption(labels, buckets, "Ora del giorno");
  }

  global.PscanCharts = {
    CATEGORIES: CATEGORIES,
    palette: palette,
    colorForCategory: colorForCategory,
    colorForProject: colorForProject,
    defaultZoomWindow: defaultZoomWindow,
    buildWeeklyOption: buildWeeklyOption,
    buildPctOption: buildPctOption,
    buildProjectTotalsOption: buildProjectTotalsOption,
    buildTotaliPieOption: buildTotaliPieOption,
    buildYearlyLineOption: buildYearlyLineOption,
    buildYearlyPeakOption: buildYearlyPeakOption,
    buildDayHoursHistogramOption: buildDayHoursHistogramOption,
    buildHourOfDayHistogramOption: buildHourOfDayHistogramOption
  };

})(window);
