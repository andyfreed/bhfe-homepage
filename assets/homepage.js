/* ============================================================
   BHFE homepage — below-hero redesign · VARIATION A · interactivity
   ------------------------------------------------------------
   • Front-page only (guarded by presence of .bhfe-finder)
   • No inline <script> — enqueue this file via functions.php
     (Autoptimize strips inline script, so it MUST live here)
   • Progressive enhancement:
       - Credential drawers: the option links are real anchors; JS
         provides the accordion show/hide + active-tile state.
       - CPA ethics + multi-license are native GET <form>s that submit
         correctly with JS OFF. JS only adds the live summary + guards.
   PASTE/APPEND: add this IIFE to
   wp-content/themes/bhfe/min/js/homepage.js
   ============================================================ */
(function () {
  "use strict";

  function initFinder(finder) {
    var tiles = Array.prototype.slice.call(finder.querySelectorAll(".bhfe-cred[aria-controls]"));
    if (!tiles.length) return;

    function closeAll(except) {
      tiles.forEach(function (t) {
        if (t === except) return;
        t.setAttribute("aria-expanded", "false");
        var d = document.getElementById(t.getAttribute("aria-controls"));
        if (d) d.hidden = true;
      });
    }

    tiles.forEach(function (tile) {
      tile.addEventListener("click", function () {
        var drawer = document.getElementById(tile.getAttribute("aria-controls"));
        var open = tile.getAttribute("aria-expanded") === "true";
        closeAll(tile);
        tile.setAttribute("aria-expanded", open ? "false" : "true");
        if (drawer) drawer.hidden = open;
      });
    });

    // CPA ethics: require a state before submit (native form still works w/o JS)
    Array.prototype.slice.call(finder.querySelectorAll(".bhfe-state")).forEach(function (form) {
      var sel = form.querySelector(".bhfe-state__select");
      var btn = form.querySelector(".bhfe-state__go");
      var lbl = form.querySelector(".bhfe-state__go-label");
      if (!sel || !btn) return;
      function sync() {
        var ready = !!sel.value;
        btn.disabled = !ready;
        if (lbl) lbl.textContent = ready
          ? "View " + sel.options[sel.selectedIndex].text + " ethics"
          : "Select a state to continue";
      }
      sel.addEventListener("change", sync);
      sync();
    });
  }

  function initMulti(multi) {
    var form = multi.querySelector(".bhfe-multi__form");
    if (!form) return;
    var boxes = Array.prototype.slice.call(form.querySelectorAll(".bhfe-chip__input"));
    var names = multi.querySelector(".bhfe-multi__rnames");
    var go = multi.querySelector(".bhfe-multi__go");
    var resultRow = multi.querySelector(".bhfe-multi__row");
    var hint = multi.querySelector(".bhfe-multi__hint");

    function sync() {
      var picked = boxes.filter(function (b) { return b.checked; });
      var has = picked.length > 0;
      if (resultRow) resultRow.hidden = !has;
      if (hint) hint.hidden = has;
      if (go) go.disabled = !has;
      if (names) {
        names.textContent = picked.map(function (b) {
          return b.getAttribute("data-short");
        }).join("  +  ");
      }
    }
    boxes.forEach(function (b) { b.addEventListener("change", sync); });
    sync();
  }

  function init() {
    var finder = document.querySelector(".bhfe-finder");
    if (finder) initFinder(finder);
    var multi = document.querySelector(".bhfe-multi-band");
    if (multi) initMulti(multi);
  }

  if (document.readyState !== "loading") init();
  else document.addEventListener("DOMContentLoaded", init);
})();
