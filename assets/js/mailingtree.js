(function () {
  'use strict';
  var year = new Date().getFullYear();
  // Refresh the bounded cohort windows even if the site has not been rebuilt.
  Array.prototype.forEach.call(document.querySelectorAll('.mailingtree-cohorts'), function (group) {
    if (Number(group.dataset.year) === year) return;
    var template = group.querySelector('.mailingtree-cohort');
    var pattern = group.querySelector('.mailingtree-pattern');
    var fragment = document.createDocumentFragment();
    for (var offset = 0; offset < Number(group.dataset.count); offset++) {
      var joiningYear = year - offset;
      var item = document.createElement('li');
      var button = template.cloneNode(true);
      var exact = group.dataset.prefix + String(joiningYear).slice(-2) + group.dataset.suffix;
      button.dataset.list = exact;
      button.dataset.scope = 'All ' + group.dataset.audience + ' who joined the Department in ' + joiningYear;
      button.querySelector('.mailingtree-node-label').textContent = 'Joined in ' + joiningYear;
      button.querySelector('code').textContent = exact;
      item.appendChild(button);
      fragment.appendChild(item);
    }
    fragment.appendChild(pattern);
    group.textContent = '';
    group.appendChild(fragment);
    group.dataset.year = String(year);
  });
  var nodes = Array.prototype.slice.call(document.querySelectorAll('.mailingtree-node'));
  var result = document.getElementById('mailingtree-result');
  var answer = document.getElementById('mailingtree-answer');
  var prompt = document.getElementById('mailingtree-prompt');
  var copy = document.getElementById('mailingtree-copy');
  var clear = document.getElementById('mailingtree-clear');
  var copyStatus = document.getElementById('mailingtree-copy-status');
  var activeNode = null;
  var byList = {};
  function emailFor(node) {
    return node.dataset.list + '@iisc.ac.in';
  }
  function clearSelection(restoreFocus) {
    var previous = activeNode;
    activeNode = null;
    nodes.forEach(function (button) {
      button.setAttribute('aria-pressed', 'false');
      button.classList.remove('is-selected', 'is-active', 'is-path');
    });
    answer.hidden = true;
    prompt.hidden = false;
    document.getElementById('mailingtree-list').textContent = '';
    document.getElementById('mailingtree-scope').textContent = '';
    document.getElementById('mailingtree-path').textContent = '';
    copyStatus.textContent = '';
    copy.disabled = true;
    clear.disabled = true;
    if (restoreFocus && previous) previous.focus({ preventScroll: true });
  }
  nodes.forEach(function (node) { byList[node.dataset.list] = node; });
  nodes.forEach(function (node) {
    node.disabled = false;
    node.setAttribute('aria-pressed', 'false');
    node.addEventListener('click', function () {
      if (activeNode === node) {
        clearSelection(false);
        return;
      }
      activeNode = node;
      var path = [];
      var current = node;
      while (current) {
        path.unshift(current.dataset.list);
        current = byList[current.dataset.parent];
      }
      nodes.forEach(function (button) {
        var selected = node.parentElement.contains(button);
        button.setAttribute('aria-pressed', String(selected));
        button.classList.toggle('is-selected', selected);
        button.classList.toggle('is-active', button === node);
        button.classList.toggle('is-path', !selected && path.indexOf(button.dataset.list) !== -1);
      });
      prompt.hidden = true;
      answer.hidden = false;
      copy.disabled = false;
      clear.disabled = false;
      copyStatus.textContent = '';
      document.getElementById('mailingtree-list').textContent = emailFor(node);
      document.getElementById('mailingtree-scope').textContent = node.dataset.scope;
      document.getElementById('mailingtree-path').textContent = path.join(' → ');
    });
  });
  clear.addEventListener('click', function () { clearSelection(true); });
  document.addEventListener('click', function (event) {
    if (activeNode && !event.target.closest('.mailingtree-node') && !result.contains(event.target)) {
      clearSelection(false);
    }
  });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && activeNode) {
      event.preventDefault();
      clearSelection(true);
    }
  });
  copy.addEventListener('click', function () {
    if (!activeNode) return;
    var exact = emailFor(activeNode);
    var copying = navigator.clipboard && navigator.clipboard.writeText
      ? navigator.clipboard.writeText(exact) : Promise.reject();
    copying.then(function () {
      if (activeNode && emailFor(activeNode) === exact) copyStatus.textContent = 'Email copied';
    }).catch(function () {
      if (!activeNode || emailFor(activeNode) !== exact) return;
      var range = document.createRange();
      range.selectNodeContents(document.getElementById('mailingtree-list'));
      var selection = window.getSelection();
      selection.removeAllRanges();
      selection.addRange(range);
      copyStatus.textContent = 'Press Ctrl+C (⌘C on Mac) to copy.';
    });
  });
  document.getElementById('mailingtree-legend').hidden = false;
  result.hidden = false;
}());
