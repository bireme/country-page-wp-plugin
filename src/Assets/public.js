(function () {
  'use strict';

  function parseInt10(value, fallback) {
    var n = parseInt(value, 10);
    return isNaN(n) ? fallback : n;
  }

  function countPages(n, visible) {
    if (n <= 0) {
      return 0;
    }
    if (visible < 1) {
      visible = 1;
    }
    return Math.floor((n - 1) / visible) + 1;
  }

  function offsetForPage(page, n, visible) {
    var maxStart = Math.max(0, n - visible);
    var ideal = page * visible;
    return Math.min(ideal, maxStart);
  }

  function initSlider(root) {
    var visible = parseInt10(root.getAttribute('data-visible'), 1);
    if (visible < 1) {
      visible = 1;
    }
    var loop = root.getAttribute('data-loop') === '1';
    var viewport = root.querySelector('.cp-country-slider__viewport');
    var track = root.querySelector('[data-cp-slider-track]');
    if (!viewport || !track) {
      return;
    }
    var slides = track.children;
    var n = slides.length;
    var numPages = countPages(n, visible);
    var page = 0;

    var prevBtn = root.querySelector('[data-cp-slider-prev]');
    var nextBtn = root.querySelector('[data-cp-slider-next]');

    function itemWidth() {
      return viewport.offsetWidth / visible;
    }

    function applyWidths() {
      var w = itemWidth();
      var i;
      for (i = 0; i < slides.length; i++) {
        slides[i].style.flex = '0 0 ' + w + 'px';
        slides[i].style.width = w + 'px';
        slides[i].style.maxWidth = w + 'px';
      }
    }

    function updateTransform() {
      var iw = itemWidth();
      var start = offsetForPage(page, n, visible);
      var tx = -start * iw;
      track.style.transform = 'translate3d(' + tx + 'px,0,0)';
    }

    function setNavState() {
      if (!prevBtn || !nextBtn) {
        return;
      }
      if (loop || numPages <= 1) {
        prevBtn.disabled = false;
        nextBtn.disabled = false;
        prevBtn.classList.remove('is-disabled');
        nextBtn.classList.remove('is-disabled');
        return;
      }
      var atFirst = page <= 0;
      var atLast = page >= numPages - 1;
      prevBtn.disabled = atFirst;
      nextBtn.disabled = atLast;
      prevBtn.classList.toggle('is-disabled', atFirst);
      nextBtn.classList.toggle('is-disabled', atLast);
    }

    function go(delta) {
      if (numPages <= 1) {
        return;
      }
      if (loop) {
        page = (page + delta + numPages) % numPages;
      } else {
        page = Math.max(0, Math.min(numPages - 1, page + delta));
      }
      updateTransform();
      setNavState();
    }

    applyWidths();
    updateTransform();
    setNavState();

    if (prevBtn) {
      prevBtn.addEventListener('click', function () {
        go(-1);
      });
    }
    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        go(1);
      });
    }

    if (typeof ResizeObserver !== 'undefined') {
      var ro = new ResizeObserver(function () {
        applyWidths();
        updateTransform();
      });
      ro.observe(viewport);
    } else {
      window.addEventListener('resize', function () {
        applyWidths();
        updateTransform();
      });
    }
  }

  function bootSliders() {
    var nodes = document.querySelectorAll('[data-cp-slider]');
    var i;
    for (i = 0; i < nodes.length; i++) {
      initSlider(nodes[i]);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootSliders);
  } else {
    bootSliders();
  }
})();

