(function (global) {
  "use strict";

  // Tabella dei 40 colori custom di pScan ($PALETTE in inc/globals.php),
  // già quantizzati nel cubo xterm-256 (stessa resa di un vero terminale).
  var PSCAN_TERM_PALETTE = [
    "#000000", "#000087", "#008700", "#008787", "#870000", "#870087", "#878700", "#d7d7d7",
    "#878787", "#0087ff", "#00ff00", "#00ffff", "#ff00ff", "#ff0000", "#ffff00", "#ffffff",
    "#ff8700", "#87ff00", "#0087ff", "#00ff87", "#ff0087", "#8700ff", "#005f87", "#00875f",
    "#87005f", "#5f0087", "#87005f", "#875f00", "#5f8700", "#5f5f5f", "#afafff", "#afffaf",
    "#afffff", "#ffafaf", "#ffafff", "#ffffaf", "#00005f", "#5f005f", "#5f0000", "#d7d700"
  ];

  function pscanColor(idx) {
    var n = PSCAN_TERM_PALETTE.length;
    var i = Math.abs(idx || 0) % n;
    return PSCAN_TERM_PALETTE[i];
  }

  global.PscanPalette = {
    colors: PSCAN_TERM_PALETTE,
    color: pscanColor
  };

})(window);
