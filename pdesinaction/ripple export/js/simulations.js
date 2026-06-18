/* ─────────────────────────────────────────────────────────────
   PDE Simulations — A. K. Nandakumaran research areas
   Each canvas is a live numerical / analytical rendering of an
   equation from his work. The hero is an interactive wave field;
   the research-page cards expose a parameter slider per equation.

   No dependencies. Accurate special functions. Theme-aware.
   ───────────────────────────────────────────────────────────── */
(function () {
  'use strict';

  var REDUCED = window.matchMedia &&
                window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Grapher destinations for each simulation (opened from the caption link) */
  var LINKS = {
    wave: 'https://www.desmos.com/calculator/xlnvhdvqaz',   // wave superposition
    heat: 'https://www.desmos.com/calculator/sp7psbnqlq',   // heat kernel
    homo: 'https://www.desmos.com/calculator/xnbrlxotjm',   // oscillating coefficient
    hji:  'https://www.desmos.com/calculator/s3wlbylkfy',   // level sets / eikonal
    helm: 'https://www.desmos.com/calculator/b1lpzjnb1k',   // Helmholtz radial
    diff: 'https://www.desmos.com/calculator/eik8aghzxl',   // diffusion / optical
  };

  /* ════════════════════════════════════════════════════════════
     PALETTES — every sim renders in two contexts:
       'dark'  → over the navy hero / dark surface
       'light' → over white research-page cards
     Colours echo the site palette (navy accent, amber gold).
     ════════════════════════════════════════════════════════════ */
  var PAL = {
    dark: {
      ink:    'rgba(232,240,248,',
      faint:  'rgba(150,195,235,',
      blue:   'rgba(150,200,235,',
      gold:   'rgba(201,146,42,',
      heat:   'rgba(224,150,120,',
      green:  'rgba(96,206,150,',
      label:  'rgba(255,255,255,0.30)',
      grid:   'rgba(150,195,235,0.05)',
    },
    light: {
      ink:    'rgba(20,40,60,',
      faint:  'rgba(40,92,138,',
      blue:   'rgba(27,61,91,',
      gold:   'rgba(160,105,11,',
      heat:   'rgba(176,72,40,',
      green:  'rgba(20,130,86,',
      label:  'rgba(27,61,91,0.42)',
      grid:   'rgba(27,61,91,0.05)',
    },
  };

  /* ════════════════════════════════════════════════════════════
     SPECIAL FUNCTIONS
     ════════════════════════════════════════════════════════════ */

  /* Bessel function of the first kind, order 0.
     Abramowitz & Stegun 9.4.1 (|x|<=3) and 9.4.3 (x>3).
     Accurate to ~1e-7 for ALL x — the previous 6-term Taylor
     polynomial diverged badly past the canvas centre. */
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

  /* ════════════════════════════════════════════════════════════
     CANVAS HELPER — HiDPI-correct sizing, returns device ctx that
     works in CSS pixels.
     ════════════════════════════════════════════════════════════ */
  function fitCanvas(canvas, ctx) {
    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var w = canvas.offsetWidth, h = canvas.offsetHeight;
    canvas.width  = Math.max(1, Math.round(w * dpr));
    canvas.height = Math.max(1, Math.round(h * dpr));
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    return { w: w, h: h };
  }

  function label(ctx, pal, text, w, h) {
    ctx.font = '11px Georgia, "Times New Roman", serif';
    ctx.fillStyle = pal.label;
    ctx.textAlign = 'right';
    ctx.fillText(text, w - 10, h - 10);
  }

  function watermark(ctx, theme, text, subtext, x, y, angle, size) {
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(angle || -0.11);
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = theme === 'light' ? 'rgb(27,61,91)' : 'rgb(255,255,255)';
    ctx.globalAlpha = 0.5;
    ctx.font = '600 ' + (size || 14) + 'px "Source Sans 3", Arial, sans-serif';
    ctx.fillText(text, 0, 0);
    if (subtext) {
      ctx.globalAlpha = 0.34;
      ctx.font = '500 ' + Math.max(10, (size || 14) - 3) + 'px "Source Sans 3", Arial, sans-serif';
      ctx.fillText(subtext, 0, 17);
    }
    ctx.restore();
  }

  /* ════════════════════════════════════════════════════════════
     1. HERO — interactive wave field
        ∂²u/∂t² = c²∇²u
        Background: superposition of travelling modes.
        Foreground: outward wavefronts seeded by the pointer —
        each click/drag is an impulse whose ring obeys r = c·t.
     ════════════════════════════════════════════════════════════ */
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
    var SRC_SPEED = 110;      // c, px/s
    var SRC_LIFE  = 4.2;      // seconds
    var lastSeed  = 0;

    function seed(x, y, strength) {
      if (sources.length > 24) sources.shift();
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

  /* ════════════════════════════════════════════════════════════
     2. HEAT — ∂u/∂t = κ∇²u
        Analytic Fourier solution of the triangular initial
        profile u₀(x)=2 min(x,1−x):  u = Σ bₙ e^{−κn²π²t} sin(nπx).
        Slider: κ (diffusivity).
     ════════════════════════════════════════════════════════════ */
  function simHeat(canvas, opts) {
    var ctx = canvas.getContext('2d');
    var pal = PAL[opts.theme] || PAL.dark;
    var size = fitCanvas(canvas, ctx);
    var t = 0.0, raf = null, running = false;

    /* Fourier sine coefficients of the symmetric tent u₀(x)=2·min(x,1−x):
       bₙ = (8/π²)·sin(nπ/2)/n²  → only odd n survive, alternating sign. */
    var B = [];
    for (var n = 1; n <= 14; n++) {
      B.push((8 / (Math.PI * Math.PI)) * Math.sin(n * Math.PI / 2) / (n * n));
    }
    function u(x, time, kappa) {
      var s = 0;
      for (var n = 1; n <= B.length; n++) {
        var b = B[n - 1];
        if (b === 0) continue;
        s += b * Math.sin(n * Math.PI * x) *
             Math.exp(-kappa * n * n * Math.PI * Math.PI * time);
      }
      return s;
    }

    function render() {
      var W = size.w, H = size.h;
      var kappa = opts.state.kappa;
      ctx.clearRect(0, 0, W, H);

      /* warm fill under the temperature profile */
      ctx.beginPath();
      ctx.moveTo(0, H);
      for (var px = 0; px <= W; px += 2) {
        var uv = u(px / W, t, kappa);
        ctx.lineTo(px, H * (1 - Math.max(0, Math.min(1, uv)) * 0.86) - 3);
      }
      ctx.lineTo(W, H); ctx.closePath();
      var g = ctx.createLinearGradient(0, 0, 0, H);
      g.addColorStop(0, pal.heat + '0.34)');
      g.addColorStop(1, pal.heat + '0.02)');
      ctx.fillStyle = g; ctx.fill();

      /* profile curve */
      ctx.beginPath();
      for (var px2 = 0; px2 <= W; px2 += 2) {
        var y = H * (1 - Math.max(0, Math.min(1, u(px2 / W, t, kappa))) * 0.86) - 3;
        px2 === 0 ? ctx.moveTo(px2, y) : ctx.lineTo(px2, y);
      }
      ctx.strokeStyle = pal.heat + '0.92)';
      ctx.lineWidth = 1.8; ctx.stroke();

      /* faint t=0 initial tent for reference */
      ctx.beginPath();
      for (var px3 = 0; px3 <= W; px3 += 4) {
        var y0 = H * (1 - Math.max(0, Math.min(1, u(px3 / W, 0, kappa))) * 0.86) - 3;
        px3 === 0 ? ctx.moveTo(px3, y0) : ctx.lineTo(px3, y0);
      }
      ctx.strokeStyle = pal.ink + '0.16)';
      ctx.setLineDash([3, 4]); ctx.lineWidth = 1; ctx.stroke();
      ctx.setLineDash([]);

      watermark(
        ctx,
        opts.theme,
        '∂u/∂t = κ∇²u',
        'diffusion and decay',
        W * 0.55,
        H * 0.34,
        -0.12,
        Math.max(13, Math.min(17, W / 18))
      );

      label(ctx, pal, '∂u/∂t = κ∇²u', W, H);

      t = t > 0.9 ? 0.0 : t + 0.0042;
    }

    function loop() { render(); raf = requestAnimationFrame(loop); }
    return makeController(canvas, ctx, function () { size = fitCanvas(canvas, ctx); },
      function () { if (!running) { running = true; loop(); } },
      function () { running = false; if (raf) cancelAnimationFrame(raf); },
      render);
  }

  /* ════════════════════════════════════════════════════════════
     3. HOMOGENIZATION — −div(A(x/ε)∇uε) = f
        Two-scale expansion uε(x) ≈ u₀(x) + ε u₁(x, x/ε).
        Slider: ε (period). As ε→0 the oscillation collapses onto
        the homogenised solution u₀ (gold dashed).
     ════════════════════════════════════════════════════════════ */
  function simHomo(canvas, opts) {
    var ctx = canvas.getContext('2d');
    var pal = PAL[opts.theme] || PAL.dark;
    var size = fitCanvas(canvas, ctx);
    var raf = null, running = false;

    function render() {
      var W = size.w, H = size.h;
      var eps = opts.state.eps;
      ctx.clearRect(0, 0, W, H);

      function u0(x)  { return 0.5 * x * (1 - x); }         // homogenised (macro) solution
      function du0(x) { return 0.5 * (1 - 2 * x); }         // its macroscopic gradient ∇u0
      /* Two-scale corrector  u1 = χ(x/ε)·∇u0(x),  χ periodic with χ'(y)=cos(2πy).
         Amplitude ∝ ε and modulated by the MACROSCOPIC GRADIENT — so the oscillation
         is largest where ∇u0 is largest (near the boundaries) and vanishes at the
         interior maximum, which is the physically correct picture. */
      function u1(x) { return eps * 0.5 * Math.cos(2 * Math.PI * x / eps) * du0(x); }

      /* oscillating uε */
      ctx.beginPath();
      for (var px = 0; px <= W; px += 1) {
        var x = px / W;
        var y = H * (0.86 - (u0(x) + u1(x)) * 1.45);
        px === 0 ? ctx.moveTo(px, y) : ctx.lineTo(px, y);
      }
      ctx.strokeStyle = pal.blue + '0.85)';
      ctx.lineWidth = 1.4; ctx.stroke();

      /* homogenised u₀ */
      ctx.beginPath();
      for (var px2 = 0; px2 <= W; px2 += 2) {
        var x2 = px2 / W;
        var y2 = H * (0.86 - u0(x2) * 1.45);
        px2 === 0 ? ctx.moveTo(px2, y2) : ctx.lineTo(px2, y2);
      }
      ctx.strokeStyle = pal.gold + '0.9)';
      ctx.lineWidth = 1.6; ctx.setLineDash([5, 4]); ctx.stroke();
      ctx.setLineDash([]);

      watermark(
        ctx,
        opts.theme,
        '−div(A(x/ε)∇uε) = f',
        'uε ≈ u0 + εu1(x, x/ε)',
        W * 0.58,
        H * 0.35,
        -0.10,
        Math.max(12, Math.min(16, W / 19))
      );

      label(ctx, pal, '−div(A(x/ε)∇u)=f,  ε=' + eps.toFixed(3), W, H);
    }

    function loop() { render(); raf = requestAnimationFrame(loop); }
    return makeController(canvas, ctx, function () { size = fitCanvas(canvas, ctx); },
      function () { if (!running) { running = true; loop(); } },
      function () { running = false; if (raf) cancelAnimationFrame(raf); },
      render);
  }

  /* ════════════════════════════════════════════════════════════
     4. HJI / EIKONAL — H(x,∇u)=0,  |∇u|=1
        Viscosity solution u(x)=dist(x, source): level sets are
        circles expanding at unit speed; characteristics are rays.
        Slider: front speed.
     ════════════════════════════════════════════════════════════ */
  function simHJI(canvas, opts) {
    var ctx = canvas.getContext('2d');
    var pal = PAL[opts.theme] || PAL.dark;
    var size = fitCanvas(canvas, ctx);
    var t = 0, raf = null, running = false;

    function render() {
      var W = size.w, H = size.h;
      var speed = opts.state.speed;
      ctx.clearRect(0, 0, W, H);

      var cx = W * 0.42, cy = H * 0.54;
      var maxR = Math.hypot(W, H) * 0.72;
      var fronts = 7;

      /* characteristics (rays from the source) */
      for (var a = 0; a < 12; a++) {
        var ang = a / 12 * Math.PI * 2;
        ctx.beginPath();
        ctx.moveTo(cx, cy);
        ctx.lineTo(cx + Math.cos(ang) * maxR, cy + Math.sin(ang) * maxR);
        ctx.strokeStyle = pal.gold + '0.08)';
        ctx.lineWidth = 1; ctx.stroke();
      }

      /* expanding level sets */
      for (var i = 0; i < fronts; i++) {
        var r = ((t * speed + i / fronts * maxR) % maxR);
        var alpha = 0.34 * (1 - r / maxR);
        ctx.beginPath();
        ctx.arc(cx, cy, r, 0, Math.PI * 2);
        ctx.strokeStyle = pal.blue + alpha.toFixed(3) + ')';
        ctx.lineWidth = 1 + (1 - r / maxR) * 1.3;
        ctx.stroke();
      }

      /* glowing source */
      var grd = ctx.createRadialGradient(cx, cy, 0, cx, cy, 15);
      grd.addColorStop(0, pal.gold + '0.75)');
      grd.addColorStop(1, pal.gold + '0)');
      ctx.beginPath(); ctx.arc(cx, cy, 15, 0, Math.PI * 2);
      ctx.fillStyle = grd; ctx.fill();

      watermark(
        ctx,
        opts.theme,
        '|∇u| = 1',
        'eikonal / viscosity solution',
        W * 0.60,
        H * 0.32,
        -0.13,
        Math.max(13, Math.min(17, W / 18))
      );

      label(ctx, pal, '|∇u| = 1   (HJI)', W, H);
      t += 0.018;
    }

    function loop() { render(); raf = requestAnimationFrame(loop); }
    return makeController(canvas, ctx, function () { size = fitCanvas(canvas, ctx); },
      function () { if (!running) { running = true; loop(); } },
      function () { running = false; if (raf) cancelAnimationFrame(raf); },
      render);
  }

  /* ════════════════════════════════════════════════════════════
     5. HELMHOLTZ — Δu + k²u = 0
        Radial standing wave u(r)=J₀(kr) (now an accurate Bessel J₀).
        Rendered as a colour field + the cross-section profile.
        Slider: k (wavenumber).
     ════════════════════════════════════════════════════════════ */
  function simHelm(canvas, opts) {
    var ctx = canvas.getContext('2d');
    var pal = PAL[opts.theme] || PAL.dark;
    var light = (opts.theme === 'light');
    var size = fitCanvas(canvas, ctx);
    var t = 0, raf = null, running = false;

    function render() {
      var W = size.w, H = size.h;
      var k = opts.state.k;
      ctx.clearRect(0, 0, W, H);

      var cx = W * 0.5, cy = H * 0.5;
      var img = ctx.createImageData(canvas.width, canvas.height);
      var dpr = canvas.width / W;
      var step = 2;

      for (var py = 0; py < canvas.height; py += step) {
        for (var px = 0; px < canvas.width; px += step) {
          var r = Math.hypot(px / dpr - cx, py / dpr - cy);
          var val = besselJ0(k * r);           // −1 … 1
          var pos = (val + 1) * 0.5;
          var R, G, Bc, Al;
          if (light) {
            /* navy ↔ gold diverging on white */
            R = Math.round(40 + pos * 130);
            G = Math.round(70 + pos * 40);
            Bc = Math.round(130 - pos * 80);
            Al = Math.round(Math.abs(val) * 95);
          } else {
            R = Math.round(pos * 30 + 10);
            G = Math.round(pos * 80 + 20);
            Bc = Math.round(pos * 160 + 40);
            Al = Math.round(Math.abs(val) * 120 + 12);
          }
          for (var dy = 0; dy < step; dy++) {
            for (var dx = 0; dx < step; dx++) {
              var idx = ((py + dy) * canvas.width + (px + dx)) * 4;
              img.data[idx] = R; img.data[idx + 1] = G;
              img.data[idx + 2] = Bc; img.data[idx + 3] = Al;
            }
          }
        }
      }
      ctx.putImageData(img, 0, 0);

      watermark(
        ctx,
        opts.theme,
        'Δu + k²u = 0',
        'u(r) = J0(kr)',
        W * 0.57,
        H * 0.34,
        -0.11,
        Math.max(12, Math.min(16, W / 19))
      );

      /* cross-section profile through the centre */
      ctx.beginPath();
      for (var x = 0; x <= W; x += 2) {
        var val2 = besselJ0(k * Math.abs(x - cx));
        var y = cy - val2 * cy * 0.78;
        x === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
      }
      ctx.strokeStyle = pal.blue + '0.85)';
      ctx.lineWidth = 1.6; ctx.stroke();

      label(ctx, pal, 'Δu + k²u = 0', W, H);
      t += 0.016;
    }

    function loop() { render(); raf = requestAnimationFrame(loop); }
    return makeController(canvas, ctx, function () { size = fitCanvas(canvas, ctx); },
      function () { if (!running) { running = true; loop(); } },
      function () { running = false; if (raf) cancelAnimationFrame(raf); },
      render);
  }

  /* ════════════════════════════════════════════════════════════
     6. DIFFUSION (optical / DCT) — −∇·(D∇φ) + μₐφ = S
        Steady point-source fluence φ(r) ∝ e^{−μ_eff r}/r with
        iso-fluence contours. Slider: μ_eff (effective attenuation).
     ════════════════════════════════════════════════════════════ */
  function simDiff(canvas, opts) {
    var ctx = canvas.getContext('2d');
    var pal = PAL[opts.theme] || PAL.dark;
    var size = fitCanvas(canvas, ctx);
    var raf = null, running = false;

    function render() {
      var W = size.w, H = size.h;
      var mu = opts.state.mu;
      ctx.clearRect(0, 0, W, H);

      var cx = W * 0.5, cy = H * 0.5;
      var img = ctx.createImageData(canvas.width, canvas.height);
      var dpr = canvas.width / W;
      var step = 2;

      for (var py = 0; py < canvas.height; py += step) {
        for (var px = 0; px < canvas.width; px += step) {
          var r = Math.max(1, Math.hypot(px / dpr - cx, py / dpr - cy));
          var phi = Math.min(1, Math.exp(-mu * r) / r * 28);
          for (var dy = 0; dy < step; dy++) {
            for (var dx = 0; dx < step; dx++) {
              var idx = ((py + dy) * canvas.width + (px + dx)) * 4;
              img.data[idx]     = Math.round(phi * 70 + 5);
              img.data[idx + 1] = Math.round(phi * 195 + 10);
              img.data[idx + 2] = Math.round(phi * 120 + 10);
              img.data[idx + 3] = Math.round(phi * 175 + 6);
            }
          }
        }
      }
      ctx.putImageData(img, 0, 0);

      watermark(
        ctx,
        opts.theme,
        '−∇·(D∇φ) + μₐφ = S',
        'φ(r) ∝ e^{−μ_eff r}/r',
        W * 0.57,
        H * 0.34,
        -0.10,
        Math.max(12, Math.min(16, W / 19))
      );

      /* iso-fluence contours at multiples of the e-folding length 1/μ_eff */
      var L = 1 / mu;
      [0.4, 0.9, 1.5, 2.2].forEach(function (f) {
        var r = L * f;
        if (r > 4 && r < Math.min(W, H) * 0.72) {
          ctx.beginPath();
          ctx.arc(cx, cy, r, 0, Math.PI * 2);
          ctx.strokeStyle = pal.green + (0.42 - f * 0.1).toFixed(3) + ')';
          ctx.lineWidth = 1; ctx.stroke();
        }
      });

      label(ctx, pal, '−∇·(D∇φ)+μₐφ = S', W, H);
    }

    function loop() { render(); raf = requestAnimationFrame(loop); }
    return makeController(canvas, ctx, function () { size = fitCanvas(canvas, ctx); },
      function () { if (!running) { running = true; loop(); } },
      function () { running = false; if (raf) cancelAnimationFrame(raf); },
      render);
  }

  /* ════════════════════════════════════════════════════════════
     CONTROLLER — wires resize + reduced-motion + visibility.
     onResize: recompute size.  start/stop: rAF control.  once: one frame.
     ════════════════════════════════════════════════════════════ */
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

  /* Default parameters per simulation key */
  var DEFAULTS = {
    heat: { kappa: 0.05 },
    homo: { eps: 0.10 },
    hji:  { speed: 30 },
    helm: { k: 0.060 },
    diff: { mu: 0.030 },
  };

  var FN = {
    wave: initHero, heat: simHeat, homo: simHomo,
    hji: simHJI, helm: simHelm, diff: simDiff,
  };

  /* ════════════════════════════════════════════════════════════
     BOOT
     ════════════════════════════════════════════════════════════ */
  function boot() {
    var controllers = [];

    /* hero */
    var hero = document.getElementById('hero-canvas');
    if (hero) {
      var c = initHero(hero, { theme: 'dark', state: {} });
      c.start();
      controllers.push({ el: hero, ctrl: c });
    }

    /* research-page sim cards */
    document.querySelectorAll('[data-sim]').forEach(function (canvas) {
      if (canvas.id === 'hero-canvas') return;
      var key = canvas.dataset.sim;
      if (!FN[key]) return;

      var theme = canvas.dataset.theme || 'light';
      var state = {};
      var defs = DEFAULTS[key] || {};
      Object.keys(defs).forEach(function (p) { state[p] = defs[p]; });

      /* bind sliders inside the enclosing .sim-card */
      var host = canvas.closest('.sim-card') || canvas.parentNode;
      if (host) {
        host.querySelectorAll('input[type="range"][data-param]').forEach(function (input) {
          var p = input.dataset.param;
          if (input.value !== '') state[p] = parseFloat(input.value);
          var out = host.querySelector('[data-out="' + p + '"]');
          function sync() {
            state[p] = parseFloat(input.value);
            if (out) out.textContent = (+state[p]).toFixed(input.dataset.dp ? +input.dataset.dp : 2);
            if (REDUCED && canvas.__sim) canvas.__sim.once();  // static repaint
          }
          input.addEventListener('input', sync);
          sync();
        });
      }

      var ctrl = FN[key](canvas, { theme: theme, state: state });
      canvas.__sim = ctrl;
      controllers.push({ el: canvas, ctrl: ctrl });
    });

    /* run only what's on screen */
    if ('IntersectionObserver' in window && !REDUCED) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          var rec = controllers.filter(function (r) { return r.el === e.target; })[0];
          if (!rec) return;
          if (e.isIntersecting) rec.ctrl.start(); else rec.ctrl.stop();
        });
      }, { threshold: 0.05 });
      controllers.forEach(function (r) { io.observe(r.el); });
    } else {
      controllers.forEach(function (r) { r.ctrl.start(); });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  /* expose for the caption "interactive" links */
  window.SIM_LINKS = LINKS;
})();
