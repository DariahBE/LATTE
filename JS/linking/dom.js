/**
 * Lightweight DOM builder and HTML escaping utilities for the entity linking UI.
 */
(function (global) {
  'use strict';

  function escapeHtml(str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  /**
   * Create a DOM element with attributes, event handlers, and children.
   */
  function el(tag, attrs, children) {
    const node = document.createElement(tag);
    if (attrs && typeof attrs === 'object') {
      Object.entries(attrs).forEach(([key, val]) => {
        if (val === undefined || val === null || val === false) return;
        if (key === 'className' || key === 'class') {
          node.className = String(val);
        } else if (key === 'style' && typeof val === 'object') {
          Object.assign(node.style, val);
        } else if (key === 'dataset' && typeof val === 'object') {
          Object.entries(val).forEach(([dk, dv]) => {
            node.dataset[dk] = String(dv);
          });
        } else if (key.startsWith('on') && typeof val === 'function') {
          node.addEventListener(key.slice(2).toLowerCase(), val);
        } else if (key === 'checked' || key === 'disabled' || key === 'selected' || key === 'required') {
          node[key] = Boolean(val);
        } else if (key === 'value') {
          node.value = String(val);
        } else if (key === 'html') {
          node.innerHTML = String(val);
        } else if (key === 'text') {
          node.textContent = String(val);
        } else {
          node.setAttribute(key, val === true ? '' : String(val));
        }
      });
    }

    if (children !== undefined && children !== null) {
      const list = Array.isArray(children) ? children : [children];
      list.forEach(child => {
        if (child === undefined || child === null || child === false) return;
        if (typeof child === 'string' || typeof child === 'number') {
          node.appendChild(document.createTextNode(String(child)));
        } else if (child instanceof Node) {
          node.appendChild(child);
        }
      });
    }
    return node;
  }

  global.LinkingDom = { el, escapeHtml };
})(window);
