---
speaker: Arbaz Khan (IIT Roorkee)
title: "Colloquium: Recent advances in mixed finite element approximation for poroelasticity"
date: 10 Jan, 2022
time: 4 pm
venue: Microsoft Teams (online)
website: 
---

Linear poroelasticity models have important applications in biology and geophysics. In particular,
the well-known Biot consolidation model describes the coupled interaction between the linear
response of a porous elastic medium saturated with fluid and a diffusive fluid flow within it,
assuming small deformations. This is the starting point for modeling human organs in computational
medicine and for modeling the mechanics of permeable rock in geophysics. Finite element methods
for Biot's consolidation model have been widely studied over the past four decades.

In the first part of the talk, we discuss a posteriori error estimators for locking-free mixed
finite element approximation of Biot's consolidation model. The simplest of these is a conventional
residual-based estimator. We establish bounds relating the estimated and true errors, and show that
these are independent of the physical parameters. The other two estimators require the solution of
local problems. These local problem estimators are also shown to be reliable, efficient and robust.
Numerical results are presented that validate the theoretical estimates, and illustrate the effectiveness
of the estimators in guiding adaptive solution algorithms.

In the second part of the talk, we discuss a novel locking-free stochastic Galerkin mixed finite element
method for the Biot consolidation model with uncertain Young's modulus and hydraulic conductivity field.
After introducing a five-field mixed variational formulation of the standard Biot consolidation model,
we discuss stochastic Galerkin mixed finite element approximation, focusing on the issue of well-posedness
and efficient linear algebra for the discretized system. We introduce a new preconditioner for use with
MINRES and establish eigenvalue bounds. Finally, we present specific numerical examples to illustrate the
efficiency of our numerical solution approach.

Finally, we discuss some remarks related to non-conforming approximation of Biot's consolidation model.

The [video of this talk](https://www.youtube.com/watch?v=e-_215cJOJY&list=PLQXtaLhI1-1ql_pkG5ro-E5JB8Et9WKMq) is available
on the [IISc Math Department channel](https://www.youtube.com/channel/UCR5Igvq9HScQKlPr-0coSIg/playlists).
