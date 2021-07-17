---
speaker: Ramesh Chandra Sau (IISc Mathematics)
title: "Finite Element Analysis of Dirichlet Boundary Control Problems Governed by Certain PDEs"
date: 22 July, 2021
time: 4 pm
venue: Microsoft Teams (online)
series: Thesis
series-prefix: PhD
series-suffix: colloquium
---

The study of the optimal control problems governed by partial differential equations (PDEs) have
been a significant research area in the applied mathematics and its allied areas. The optimal
control problem consists of finding a control variable that minimizes a cost functional subject
to a PDE. In this talk, I will present finite element analysis of Dirichlet boundary optimal
control problems governed by certain PDEs. This talk will be divided into three parts.

In the first part, we study an energy space-based approach for the Dirichlet boundary optimal
control problem governed by the Poisson equation with control constraints. The optimality system
results in a simplified Signorini type problem for control which is coupled with boundary value
problems for state and co-state variables. We propose a finite element-based numerical method using
the linear Lagrange finite element spaces with discrete control constraints at the Lagrange nodes.
We present the analysis for $L^2$ cost functional, but this analysis can also be extended to the
gradient cost functional problem. _A priori_ error estimates of optimal order in the energy
norm are derived up to the regularity of the solution.

In the second part, we discuss the Dirichlet boundary optimal control problem governed by the Stokes
equation. We develop a finite element discretization by using $\mathbf{P}\_1$ elements(in the fine mesh)
for the velocity and control variable and $P_0$ elements (in the coarse mesh) for the pressure variable.
We present a new _a posteriori_ error estimator for the control error. This estimator generalizes
the standard residual type estimator of the unconstrained Dirichlet boundary control problems by adding
terms at the contact boundary that address the non-linearity. We sketch out the proof of the estimator's
reliability and efficiency.

As a continuation of the first part, we extend our ideas to the linear parabolic equation in the
third part of this presentation.  The space discretization of the state and co-state variables is
done using usual conforming finite elements, whereas the time discretization is based on discontinuous
Galerkin methods. We use  $H^1$-conforming 3D finite elements for the control variable. We present a
sketch to demonstrate the existence and uniqueness of the solution; and the error estimates of state,
adjoint state, and control.

