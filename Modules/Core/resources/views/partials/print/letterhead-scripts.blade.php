<script>
  'use strict';

  // ── A4 sheet pagination ──
  // Long documents overflow one sheet. Instead of letting the browser
  // fragment one long container, each .page's content is distributed into
  // discrete A4-sized .sheet divs — like printing onto physical pads. The
  // screen preview shows exactly the stacked pages that will print: the
  // items table splits across sheets with its header re-cloned, and every
  // sheet carries the letterhead header (.page-head) at its top.
  // Supports multiple .page documents on one screen (bulk invoice print).
  function paginateIntoSheets() {
    if (document.querySelector('.sheet')) return;

    // One sheet's height in px (mm-accurate).
    var probe = document.createElement('div');
    probe.style.height = '297mm';
    probe.style.position = 'absolute';
    probe.style.visibility = 'hidden';
    document.body.appendChild(probe);
    var sheetPx = probe.offsetHeight;
    document.body.removeChild(probe);
    if (!sheetPx) return;

    var pages = [];
    document.querySelectorAll('.page').forEach(function (p) { pages.push(p); });
    pages.forEach(function (srcPage) { paginateOne(srcPage, sheetPx); });
  }

  function paginateOne(srcPage, sheetPx) {
    var srcContent = srcPage.querySelector('.content');
    if (!srcContent) return;
    var pageHead = srcContent.querySelector('.page-head');
    var usable = sheetPx - 60; // bottom breathing room on every sheet

    // Flatten the document into atoms: normal blocks move whole; the items
    // table moves row by row so it can split across sheets.
    var atoms = [];
    var children = [];
    for (var i = 0; i < srcContent.children.length; i++) children.push(srcContent.children[i]);
    children.forEach(function (el) {
      if (el === pageHead) return; // repeated per sheet, not flowed
      if (el.classList.contains('table-items') && el.tBodies.length) {
        var rows = [];
        for (var r = 0; r < el.tBodies[0].rows.length; r++) rows.push(el.tBodies[0].rows[r]);
        rows.forEach(function (row) { atoms.push({ row: row, table: el }); });
      } else {
        atoms.push({ block: el });
      }
    });

    var wrap = document.createElement('div');
    srcPage.parentNode.insertBefore(wrap, srcPage);
    var curContent = null, curTableFor = null, curTable = null;

    function newSheet() {
      var s = document.createElement('div');
      s.className = 'page sheet';
      s.style.height = '297mm';
      s.style.minHeight = '297mm';
      s.style.overflow = 'hidden';
      curContent = document.createElement('div');
      curContent.className = 'content';
      if (pageHead) curContent.appendChild(pageHead.cloneNode(true));
      s.appendChild(curContent);
      wrap.appendChild(s);
      curTable = null;
      curTableFor = null;
    }

    function targetFor(atom) {
      if (atom.block) { curTable = null; curTableFor = null; return { parent: curContent, node: atom.block }; }
      if (curTableFor !== atom.table || !curTable) {
        // Start (or continue on a new sheet) the items table with its header.
        curTable = atom.table.cloneNode(false);
        var head = atom.table.tHead ? atom.table.tHead.cloneNode(true) : null;
        if (head) curTable.appendChild(head);
        curTable.appendChild(document.createElement('tbody'));
        curContent.appendChild(curTable);
        curTableFor = atom.table;
      }
      return { parent: curTable.tBodies[0], node: atom.row };
    }

    newSheet();
    atoms.forEach(function (atom) {
      var t = targetFor(atom);
      t.parent.appendChild(t.node);
      if (curContent.offsetHeight > usable && (curContent.children.length > 1 || t.parent.rows && t.parent.rows.length > 1)) {
        // Doesn't fit — move this atom to a fresh sheet.
        t.node.parentNode.removeChild(t.node);
        var carryTable = atom.row ? atom.table : null;
        newSheet();
        if (carryTable) curTableFor = null; // re-clone table + header on the new sheet
        var t2 = targetFor(atom);
        t2.parent.appendChild(t2.node);
      }
    });

    srcPage.parentNode.removeChild(srcPage);
  }

  window.onload = function () {
    paginateIntoSheets();
    window.print();
  };
</script>
