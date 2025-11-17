---
speaker: Tattwamasi Amrutam (IMPAN, Warsaw, Poland)
title: "Intermediate factor theorems: from dynamics to operator algebras"
date: 19 Sep, 2025
time: 10 am
start_time: 10:00
venue:  LH-1, Mathematics Department
series: "APRG Seminar"
website: https://math.iisc.ac.in/~aprg/index.php?id=seminar25-26
header-includes: |
    \usepackage{circuitikz}
---

Let $X$ and $Y$ be $G$-spaces, where $G$ is a second-countable, locally compact group, and 
$G \curvearrowright X$ and $Y$ by homeomorphisms. A very well-studied question from the dynamics 
is to understand all intermediate factors of the form
<svg xmlns="http://www.w3.org/2000/svg" width="420" height="120" viewBox="0 0 420 120">
  <!-- Nodes -->
  <text x="40" y="60" font-family="serif" font-size="16">X × Y</text>
  <text x="160" y="60" font-family="serif" font-size="16">Z</text>
  <text x="280" y="60" font-family="serif" font-size="16">X</text>

  <!-- Straight arrows -->
  <line x1="95" y1="55" x2="150" y2="55" stroke="black" marker-end="url(#arrow)"/>
  <line x1="190" y1="55" x2="270" y2="55" stroke="black" marker-end="url(#arrow)"/>

  <!-- Curved arrow (shifted left, higher arc) -->
  <path d="M60,35 C150,0 240,0 280,35" stroke="black" fill="none" marker-end="url(#arrow)"/>
  <text x="165" y="10" font-family="serif" font-size="14">Projₓ</text>

  <!-- Arrowhead -->
  <defs>
    <marker id="arrow" markerWidth="10" markerHeight="10" refX="10" refY="5"
            orient="auto" markerUnits="strokeWidth">
      <path d="M0,0 L10,5 L0,10 z" fill="black"/>
    </marker>
  </defs>
</svg>
In particular, to find conditions on $G$, $X$, and $Y$ such that every such intermediate factor splits as 
a product of the form $X \times Y'$, where there is a continuous $G$-map $Y \to Y'$. 

Of course, we can also ask such a question in the measurable setup, where we replace topological compact spaces by measurable spaces.

<svg xmlns="http://www.w3.org/2000/svg" width="480" height="120" viewBox="0 0 480 120">
  <!-- Nodes -->
  <text x="30" y="60" font-family="serif" font-size="16">(X, μ) × (Y, ν)</text>
  <text x="210" y="60" font-family="serif" font-size="16">(Z, η)</text>
  <text x="350" y="60" font-family="serif" font-size="16">(X, μ)</text>

  <!-- Straight arrows -->
  <!-- Start earlier (closer to the first text) -->
  <line x1="135" y1="55" x2="200" y2="55" stroke="black" marker-end="url(#arrow)"/>
  <line x1="270" y1="55" x2="340" y2="55" stroke="black" marker-end="url(#arrow)"/>

  <!-- Curved arrow (only slightly shifted right compared to original) -->
  <path d="M110,35 C210,0 300,0 350,35" stroke="black" fill="none" marker-end="url(#arrow)"/>
  <text x="220" y="10" font-family="serif" font-size="14">Projₓ</text>

  <!-- Arrowhead -->
  <defs>
    <marker id="arrow" markerWidth="10" markerHeight="10" refX="10" refY="5"
            orient="auto" markerUnits="strokeWidth">
      <path d="M0,0 L10,5 L0,10 z" fill="black"/>
    </marker>
  </defs>
</svg>


When is $(Z,\eta) \cong (X,\mu) \times (Y',\nu')$, where there is a $G$-map $(Y,\nu)\to (Y',\nu')$?

Such results, known as _Intermediate Factor Theorems_, have been at the heart of rigidity results, 
starting from Margulis to Nevo--Stuck--Zimmer to Bader--Shalom.

During this talk, we shall reformulate this problem in the $C^*$ and von Neumann algebraic language 
and then give some new results in this direction. Parts of them are based on joint work with 
Yongle Jiang and Shuoxing Zhou, respectively. 

No prior knowledge of any of these will be assumed during the talk.
