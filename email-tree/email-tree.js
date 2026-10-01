(function () {
  'use strict';
  var year = new Date().getFullYear();
  // Refresh the bounded cohort windows even if the site has not been rebuilt.
  Array.prototype.forEach.call(document.querySelectorAll('.email-tree-cohorts'), function (group) {
    if (Number(group.dataset.year) === year) return;
    var template = group.querySelector('.email-tree-cohort');
    var pattern = group.querySelector('.email-tree-pattern');
    var fragment = document.createDocumentFragment();
    for (var offset = 0; offset < Number(group.dataset.count); offset++) {
      var joiningYear = year - offset;
      var item = document.createElement('li');
      var button = template.cloneNode(true);
      var exact = group.dataset.prefix + String(joiningYear).slice(-2) + group.dataset.suffix;
      button.dataset.list = exact;
      button.dataset.scope = 'all ' + group.dataset.audience + ' who joined the Department in ' + joiningYear;
      button.querySelector('.email-tree-node-label').textContent = 'Joined in ' + joiningYear;
      button.querySelector('code').textContent = exact;
      item.appendChild(button);
      fragment.appendChild(item);
    }
    fragment.appendChild(pattern);
    group.textContent = '';
    group.appendChild(fragment);
    group.dataset.year = String(year);
  });
  var nodes = Array.prototype.slice.call(document.querySelectorAll('.email-tree-node'));
  var result = document.getElementById('email-tree-result');
  var headingText = document.getElementById('email-tree-heading-text');
  var selectHint = document.getElementById('email-tree-select-hint');
  var popup = document.getElementById('email-tree-popup');
  var popupAddress = document.getElementById('email-tree-popup-address');
  var popupStatus = document.getElementById('email-tree-popup-status');
  var popupCopy = document.getElementById('email-tree-popup-copy');
  var popupCopyIcon = popupCopy.querySelector('i');
  var feedbackTimer;
  var copyRequest = 0;
  var activeNode = null;
  var byList = {};
  function emailFor(node) {
    return node.dataset.list + '@iisc.ac.in';
  }
  function hidePopup() {
    clearTimeout(feedbackTimer);
    if (popup.contains(document.activeElement) && activeNode) activeNode.focus({ preventScroll: true });
    popup.hidden = true;
  }
  function resetCopyFeedback() {
    popupStatus.textContent = '';
    popupCopyIcon.className = 'fa fa-copy';
    popupCopy.setAttribute('aria-label', 'Copy email address to clipboard');
    popupCopy.title = 'Copy email address';
  }
  function showPopup(node, email) {
    hidePopup();
    copyRequest++;
    popupAddress.textContent = email;
    resetCopyFeedback();
    popup.hidden = false;
    var box = node.getBoundingClientRect();
    var popupRect = popup.getBoundingClientRect();
    var isRoot = node.dataset.list === 'all.math';
    var fitsRight = box.right + popupRect.width + 26 <= window.innerWidth;
    var below = isRoot || box.top - popupRect.height - 10 < 16;
    var left = isRoot && fitsRight
      ? box.right + 10
      : Math.max(16, Math.min(box.left + (box.width - popupRect.width) / 2, window.innerWidth - popupRect.width - 16));
    var top = isRoot && fitsRight
      ? box.top + (box.height - popupRect.height) / 2
      : below ? box.bottom + 10 : box.top - popupRect.height - 10;
    popup.style.left = left + 'px';
    popup.style.top = Math.max(16, Math.min(top, window.innerHeight - popupRect.height - 16)) + 'px';
    popupCopy.focus({ preventScroll: true });
  }
  function copyEmail() {
    var node = activeNode;
    if (!node || popup.hidden) return;
    var email = emailFor(node);
    var request = ++copyRequest;
    clearTimeout(feedbackTimer);
    resetCopyFeedback();
    var copying = navigator.clipboard && navigator.clipboard.writeText
      ? navigator.clipboard.writeText(email) : Promise.reject();
    copying.then(function () {
      if (request !== copyRequest || activeNode !== node || popup.hidden) return;
      popupCopyIcon.className = 'fa fa-check';
      popupCopy.setAttribute('aria-label', 'Email address copied');
      popupCopy.title = 'Copied';
      popupStatus.textContent = 'Email address copied.';
      feedbackTimer = setTimeout(resetCopyFeedback, 1500);
    }).catch(function () {
      if (request !== copyRequest || activeNode !== node || popup.hidden) return;
      var range = document.createRange();
      range.selectNodeContents(popupAddress);
      var selection = window.getSelection();
      selection.removeAllRanges();
      selection.addRange(range);
      popupStatus.textContent = 'Select the address and copy it.';
      popupCopy.setAttribute('aria-label', 'Select the address and copy it');
      popupCopy.title = 'Select the address and copy it';
    });
  }
  function clearSelection(restoreFocus) {
    var previous = activeNode;
    activeNode = null;
    copyRequest++;
    hidePopup();
    nodes.forEach(function (button) {
      button.setAttribute('aria-pressed', 'false');
      button.classList.remove('is-selected', 'is-active', 'is-path');
    });
    headingText.textContent = 'Who are you writing to?';
    headingText.classList.remove('email-tree-heading-selection');
    selectHint.hidden = false;
    result.textContent = '';
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
      var resultText = 'Use ' + emailFor(node) + ' to write to ' + node.dataset.scope;
      headingText.textContent = '';
      headingText.appendChild(document.createTextNode('Use '));
      var address = document.createElement('code');
      address.className = 'email-tree-exact-list';
      address.textContent = emailFor(node);
      headingText.appendChild(address);
      headingText.appendChild(document.createTextNode(' to write to ' + node.dataset.scope));
      headingText.classList.add('email-tree-heading-selection');
      selectHint.hidden = true;
      result.textContent = resultText;
      showPopup(node, emailFor(node));
    });
  });
  document.addEventListener('click', function (event) {
    if (activeNode && !event.target.closest('.email-tree-node') && !result.contains(event.target) && !popup.contains(event.target)) {
      clearSelection(false);
    }
  });
  popupCopy.addEventListener('click', copyEmail);
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && activeNode) {
      event.preventDefault();
      clearSelection(true);
    }
  });
  window.addEventListener('scroll', hidePopup, { passive: true });
  window.addEventListener('resize', hidePopup);
  document.getElementById('email-tree-legend').hidden = false;
}());
