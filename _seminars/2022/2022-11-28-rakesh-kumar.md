---
speaker: Rakesh Kumar (IISER, Thiruvananthapuram)
title: "Colloquium: Higher order accurate numerical schemes for hyperbolic conservation laws"
date: 28 Nov, 2022
time: 4 pm
venue: LH-1, Mathematics Department
website: 
---


The system of hyperbolic conservation laws is the first order partial differential equations of the form
\begin{equation}
\frac{\partial \mathbf{u}}{\partial t}+\sum_{\alpha=1}^d
\frac{\partial \mathbf{f}\_{\alpha}(\mathbf{u})}{\partial x_{\alpha}} =0,,~~~~~~ 
(\mathbf{x},t)\in \Omega \times (0,T],  \qquad \qquad \qquad (1)
\end{equation}
subject to initial data
\begin{equation}
\mathbf{u}(\mathbf{x},0)=\mathbf{u}\_0(\mathbf{x}),
\end{equation}
where $\mathbf{u}=(u_1,u_2,\ldots, u_m)\in \mathbb{R}^m$ are the conserved
variables and $\mathbf{f}\_{\alpha}:\mathbb{R}^m \rightarrow \mathbb{R}^m$,
$\alpha=1,2,\ldots,d$ are the Cartesian components of flux. It is
well-known that the classical solution of (1) may cease to exist in
finite time, even the initial data is sufficiently smooth. The appearance
of shocks, contact discontinuities and rarefaction waves in the solution
profile make difficult to devise higher-order accurate numerical schemes
because numerical schemes may develop spurious oscillations or sometimes
blow up of the solution may occur. 

In this talk, we will discuss recently developed Weighted Essentially
Non-oscillatory (WENO) and hybrid schemes  for hyperbolic conservation
laws. These schemes compute the solution accurately while maintaining the
high resolution near the discontinuities in a non-oscillatory manner.
