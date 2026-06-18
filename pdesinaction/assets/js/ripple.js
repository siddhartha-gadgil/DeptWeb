/* assets/js/ripple.js */
(function () {
  'use strict';

  // Constants for color palette
  const REDUCED = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const PAL = {
    dark: {
      ink:    'rgba(139, 0, 0,',    // accent-dark
      faint:  'rgba(139, 0, 0,',
      blue:   'rgba(218, 165, 32,', // accent-gold1
      gold:   'rgba(184, 134, 11,', // accent-gold2
      label:  'rgba(139, 0, 0, 0.3)',
    }
  };

  /* Bessel function of the first kind, order 0.
     Abramowitz & Stegun 9.4.1 (|x|<=3) and 9.4.3 (x>3). */
  function besselJ0(x) {
    x = Math.abs(x);
    if (x < 3) {
      var t  = x / 3, t2 = t * t;
      return 1
        - 2.2499997 * t2
        + 1.2656208 * t2 * t2
        - 0.3163866 * Math.pow(t2, 3)
        + 0.0444479 * Math.pow(t2, 4)
        - 0.0039444 * Math.pow(t2, 5)
        + 0.0002100 * Math.pow(t2, 6);
    }
    var z  = 3 / x;
    var f0 = 0.79788456
      - 0.00000077 * z
      - 0.00552740 * z * z
      - 0.00009512 * Math.pow(z, 3)
      + 0.00137237 * Math.pow(z, 4)
      - 0.00072805 * Math.pow(z, 5)
      + 0.00014476 * Math.pow(z, 6);
    var th = x - 0.78539816
      - 0.04166397 * z
      - 0.00003954 * z * z
      + 0.00262573 * Math.pow(z, 3)
      - 0.00054125 * Math.pow(z, 4)
      - 0.00029333 * Math.pow(z, 5)
      + 0.00013558 * Math.pow(z, 6);
    return f0 / Math.sqrt(x) * Math.cos(th);
  }

  function fitCanvas(canvas, ctx) {
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    const w = canvas.offsetWidth, h = canvas.offsetHeight;
    canvas.width  = Math.max(1, Math.round(w * dpr));
    canvas.height = Math.max(1, Math.round(h * dpr));
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    return { w: w, h: h };
  }

  function label(ctx, pal, text, w, h) {
    ctx.font = '12px Georgia, serif';
    ctx.fillStyle = pal.label;
    ctx.textAlign = 'right';
    ctx.fillText(text, w - 20, h - 20);
  }

  function watermark(ctx, text, subtext, x, y, angle, size) {
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(angle || -0.11);
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = 'rgba(139, 0, 0, 0.4)';
    ctx.font = '600 ' + (size || 14) + 'px "Playfair Display", serif';
    ctx.fillText(text, 0, 0);
    if (subtext) {
      ctx.globalAlpha = 0.3;
      ctx.font = '500 ' + Math.max(10, (size || 14) - 3) + 'px "Segoe UI", sans-serif';
      ctx.fillText(subtext, 0, 20);
    }
    ctx.restore();
  }

  function makeController(canvas, ctx, onResize, start, stop, once) {
    var ro = null;
    function resize() { onResize(); if (REDUCED) once(); }
    if ('ResizeObserver' in window) {
      ro = new ResizeObserver(resize); ro.observe(canvas);
    } else {
      window.addEventListener('resize', resize, { passive: true });
    }
    return {
      start: function () { if (REDUCED) { once(); } else { start(); } },
      stop: stop,
      once: once,
    };
  }

  function initHero(canvas, opts) {
    var ctx = canvas.getContext('2d');
    var pal = PAL[opts.theme] || PAL.dark;
    var size = fitCanvas(canvas, ctx);
    var t = 0, raf = null, running = false;

    var modes = [
      { k: 0.016, om: 0.70, A: 38, phi: 0,         w: 1.4, a: 0.07  },
      { k: 0.024, om: 1.10, A: 24, phi: 1.2,       w: 1.1, a: 0.055 },
      { k: 0.040, om: 1.80, A: 14, phi: 2.5,       w: 0.9, a: 0.065 },
      { k: 0.016, om: 0.70, A: 38, phi: Math.PI,   w: 1.0, a: 0.04  },
    ];
    var ellipses = [
      { rx: 0.38, ry: 0.21 }, { rx: 0.29, ry: 0.15 },
      { rx: 0.19, ry: 0.10 }, { rx: 0.10, ry: 0.052 },
    ];

    /* Pointer-seeded impulses → expanding wavefronts (genuine c·t rings) */
    var sources = [];
    var SRC_SPEED = 120;      // c, px/s
    var SRC_LIFE  = 4.0;      // seconds
    var lastSeed  = 0;

    function seed(x, y, strength) {
      if (sources.length > 30) sources.shift();
      sources.push({ x: x, y: y, born: t, s: strength || 1 });
    }
    function pointerPos(e) {
      var r = canvas.getBoundingClientRect();
      var p = e.touches ? e.touches[0] : e;
      return { x: p.clientX - r.left, y: p.clientY - r.top };
    }
    canvas.addEventListener('pointerdown', function (e) {
      var p = pointerPos(e); seed(p.x, p.y, 1.4);
    });
    canvas.addEventListener('pointermove', function (e) {
      if (t - lastSeed < 0.06) return;       // throttle the drag trail
      lastSeed = t;
      var p = pointerPos(e); seed(p.x, p.y, 0.8);
    });

    function drawRay(W, H) {
      ctx.beginPath();
      ctx.moveTo(W * 0.48, H * 0.96);
      ctx.quadraticCurveTo(W * 0.72, H * 0.36, W * 1.0, H * 0.58);
      ctx.strokeStyle = pal.gold + '0.18)';
      ctx.lineWidth = 1.8; ctx.stroke();
      ctx.beginPath();
      ctx.moveTo(W * 0.44, H * 0.99);
      ctx.quadraticCurveTo(W * 0.68, H * 0.30, W * 0.96, H * 0.62);
      ctx.strokeStyle = pal.gold + '0.10)';
      ctx.lineWidth = 1.2; ctx.stroke();
    }

    function render(dt) {
      var W = size.w, H = size.h;
      ctx.clearRect(0, 0, W, H);

      /* travelling background modes */
      modes.forEach(function (m) {
        ctx.beginPath();
        for (var x = 0; x <= W; x += 2) {
          var y = H / 2
            + m.A * Math.sin(m.k * x - m.om * t + m.phi)
            + m.A * 0.4 * Math.sin(m.k * 2.1 * x + m.om * 0.6 * t);
          x === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
        }
        ctx.strokeStyle = pal.faint + m.a + ')';
        ctx.lineWidth = m.w; ctx.stroke();
      });

      /* elliptic level sets (anisotropic fundamental solution) */
      var ex = W * 0.78, ey = H * 0.5;
      ellipses.forEach(function (el, i) {
        ctx.beginPath();
        ctx.ellipse(ex, ey, el.rx * W, el.ry * H, 0, 0, Math.PI * 2);
        ctx.strokeStyle = pal.faint + (0.025 + i * 0.007) + ')';
        ctx.lineWidth = 1; ctx.stroke();
      });

      drawRay(W, H);

      // watermark(
      //   ctx,
      //   '∂²u/∂t² = c²∇²u',
      //   'superposition and propagation',
      //   W * 0.58,
      //   H * 0.30,
      //   -0.14,
      //   Math.max(14, Math.min(19, W / 16))
      // );

      /* pointer wavefronts — concentric rings at radius c·(t−born) */
      for (var i = sources.length - 1; i >= 0; i--) {
        var src = sources[i];
        var age = t - src.born;
        if (age > SRC_LIFE) { sources.splice(i, 1); continue; }
        var decay = 1 - age / SRC_LIFE;
        for (var n = 0; n < 3; n++) {
          var r = SRC_SPEED * age - n * 26;
          if (r <= 0) continue;
          var a = decay * decay * src.s * (0.4 - n * 0.12);
          if (a <= 0.01) continue;
          ctx.beginPath();
          ctx.arc(src.x, src.y, r, 0, Math.PI * 2);
          ctx.strokeStyle = (n === 0 ? pal.gold : pal.blue) + a.toFixed(3) + ')';
          ctx.lineWidth = n === 0 ? 1.6 : 1.0;
          ctx.stroke();
        }
      }

      // label(ctx, pal, '∂²u/∂t² = c²∇²u', W, H);
      t += dt;
    }

    function loop() {
      render(0.016);
      raf = requestAnimationFrame(loop);
    }

    return makeController(canvas, ctx, function () { size = fitCanvas(canvas, ctx); },
      function start() { if (!running) { running = true; loop(); } },
      function stop()  { running = false; if (raf) cancelAnimationFrame(raf); },
      function once()  { render(0); });
  }

  // Bootstrapping the hero canvas simulation
  document.addEventListener('DOMContentLoaded', () => {
    const heroCanvas = document.getElementById('hero-canvas');
    if (heroCanvas) {
      const controller = initHero(heroCanvas, { theme: 'dark', state: {} });
      controller.start();
    }
  });
})();