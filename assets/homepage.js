/* ============================================================
   BHFE homepage (bhfe-homepage plugin) — front-page interactivity
   ------------------------------------------------------------
   • No inline <script> — Autoptimize strips inline scripts, so all
     JS MUST ship in this enqueued file.
   • Progressive enhancement only: every option in the course finder
     is a real <a href>, and the CPA state picker is a native GET
     <form> that submits correctly with JS off.
   ============================================================ */
(function () {
  "use strict";

  // Course-finder tiles: hover/focus reveal is pure CSS. This adds
  // tap-to-toggle for touch devices (no hover) — first tap opens a tile's
  // chips, a tap on an option link still navigates. One tile open at a time.
  function initCoursesExpand(root) {
    var tiles = Array.prototype.slice.call(root.querySelectorAll(".bhfe-cf-xtile"));
    if (!tiles.length) return;
    root.addEventListener("click", function (e) {
      // let links + the CPA state form (select/Go/label) work without toggling
      if (e.target.closest("a, select, button, label, option")) return;
      var tile = e.target.closest(".bhfe-cf-xtile");
      if (!tile) return;
      var open = tile.classList.contains("is-open");
      tiles.forEach(function (t) { t.classList.remove("is-open"); });
      if (!open) tile.classList.add("is-open");
    });
  }

  // CPA state ethics: a state can carry a per-state destination (data-url on
  // its <option>, set in Settings → BHFE Homepage) — e.g. straight to that
  // state's course page. On submit, route there instead of the shop filter.
  // With JS off the form submits normally, so every state still works.
  function initStateRouting() {
    Array.prototype.forEach.call(
      document.querySelectorAll(".bhfe-cf-stateform"),
      function (form) {
        form.addEventListener("submit", function (e) {
          var sel = form.querySelector(".bhfe-cf-stateselect");
          if (!sel || sel.selectedIndex < 0) return;
          var url = sel.options[sel.selectedIndex].getAttribute("data-url");
          if (url) {
            e.preventDefault();
            window.location.assign(url);
          }
        });
      }
    );
  }

  // Accreditation logos: hover/focus popovers are pure CSS; this adds
  // tap-to-toggle for touch devices. A tap on a link inside the popover
  // (e.g. nasbaregistry.org) still navigates. One popover open at a time.
  function initAccredNotes() {
    var tiles = Array.prototype.slice.call(
      document.querySelectorAll(".bhfe-accred__logo[aria-describedby]")
    );
    if (!tiles.length) return;
    tiles.forEach(function (tile) {
      tile.addEventListener("click", function (e) {
        if (e.target.closest("a")) return;
        var open = tile.classList.contains("is-open");
        tiles.forEach(function (t) { t.classList.remove("is-open"); });
        if (!open) tile.classList.add("is-open");
      });
    });
  }

  function init() {
    Array.prototype.forEach.call(
      document.querySelectorAll(".bhfe-cf-courses--c"),
      function (el) { initCoursesExpand(el); }
    );
    initStateRouting();
    initAccredNotes();
  }

  if (document.readyState !== "loading") init();
  else document.addEventListener("DOMContentLoaded", init);
})();
