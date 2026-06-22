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

  // Combined "Find Your Courses — Bundle & Save" picker.
  // Multi-select credential buttons -> single CTA. One pick links to that
  // credential's dedicated page (data-page); several link to the AND-filtered
  // /courses/?credit_type[]=… catalog built from each button's data-slug.
  function initCourses(root) {
    var grid = root.querySelector(".bhfe-cf-grid");
    var cta = root.querySelector(".bhfe-cf-cta");
    var summary = root.querySelector(".bhfe-cf-summary");
    var note = root.querySelector(".bhfe-cf-note");
    if (!grid || !cta) return;
    var btns = Array.prototype.slice.call(grid.querySelectorAll(".bhfe-cf-license"));
    var selected = [];

    function btnById(id) {
      for (var i = 0; i < btns.length; i++) {
        if (btns[i].getAttribute("data-id") === id) return btns[i];
      }
      return null;
    }

    function hrefFor(ids) {
      if (ids.length === 0) return "#";
      if (ids.length === 1) {
        var b = btnById(ids[0]);
        return b ? b.getAttribute("data-page") : "#";
      }
      var slugs = ids.map(function (id) {
        var b = btnById(id);
        return b ? b.getAttribute("data-slug") : "";
      }).filter(Boolean);
      return "/courses/?credit_type[]=" + slugs.join("&credit_type[]=");
    }

    function render() {
      var n = selected.length;
      btns.forEach(function (b) {
        b.setAttribute("aria-pressed", selected.indexOf(b.getAttribute("data-id")) !== -1 ? "true" : "false");
      });
      if (summary) {
        var strong = n > 0 ? (n + " ") : "";
        var text;
        if (n === 0) text = "Select the credentials you hold to begin.";
        else if (n === 1) text = "credential selected.";
        else text = "credentials selected — showing courses approved for all of them.";
        summary.innerHTML = "<strong>" + strong + "</strong>" + text;
      }
      if (n === 0) {
        cta.textContent = "Select a credential";
        cta.classList.add("is-disabled");
        cta.setAttribute("href", "#");
        cta.setAttribute("aria-disabled", "true");
      } else {
        cta.classList.remove("is-disabled");
        cta.removeAttribute("aria-disabled");
        cta.setAttribute("href", hrefFor(selected));
        if (n === 1) {
          var b = btnById(selected[0]);
          cta.textContent = "Browse " + (b ? b.getAttribute("data-label") : "") + " courses  →";
        } else {
          cta.textContent = "Show courses for all " + n + "  →";
        }
      }
      if (note) note.classList.toggle("is-on", n > 1);
    }

    grid.addEventListener("click", function (e) {
      var btn = e.target.closest(".bhfe-cf-license");
      if (!btn) return;
      var id = btn.getAttribute("data-id");
      var i = selected.indexOf(id);
      if (i === -1) selected.push(id); else selected.splice(i, 1);
      render();
    });
    render();
  }

  function init() {
    var finder = document.querySelector(".bhfe-finder");
    if (finder) initFinder(finder);
    var multi = document.querySelector(".bhfe-multi-band");
    if (multi) initMulti(multi);
    var courses = document.querySelector(".bhfe-cf-courses");
    if (courses) initCourses(courses);
  }

  if (document.readyState !== "loading") init();
  else document.addEventListener("DOMContentLoaded", init);
})();
