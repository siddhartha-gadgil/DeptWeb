---
speaker: Rahul Biswas (IISc Mathematics)
date: 21st October 2022
time: 12 pm
venue: LH-1, Mathematics Department
title: "Local Projection Stabilization Methods for the Oseen Problem"
series: Thesis
series-prefix: PhD
series-suffix: colloquium
---

Convection dominated fluid flow problems show spurious oscillations when solved using
the usual Galerkin finite element method (FEM). To suppress these un-physical solutions
we use various stabilization methods. In this thesis, we discuss the Local Projection
Stabilization (LPS) methods for the Oseen problem.  

This thesis mainly focuses on three different finite element methods each serving a
purpose of its own. First, we discuss the a priori analysis of the Oseen problem using
the Crouzeix-Raviart (CR1) FEM. The CR1/P0 pair is a well-known choice for solving
mixed problems like the Oseen equations since it satisfies the discrete inf-sup
condition. Moreover, the CR1 elements are easy to implement and offer a smaller
stencil compared with conforming linear elements (in the LPS setting). We also
discuss the CR1/CR1 pair for the Oseen problem to achieve a higher order of convergence. 

Second, we discuss the a posteriori analysis for the Oseen problem using the CR1/P0 pair
using a dual norm approach. We define an error estimator and prove that it is reliable
and discuss an efficiency estimate that depends on the diffusion coefficient. 

Next, we focus on formulating an LPS scheme that can provide globally divergence free
velocity. To achieve this, we use the $H(div;\Omega)$ conforming Raviart-Thomas (${\rm RT}^k$)
space of order $k \geq 1$. We show a strong stability result under the SUPG norm by enriching
the ${\rm RT}^k$ space using tangential bubbles. We also discuss the a priori error analysis
for this method. 

Finally, we develop a hybrid high order (HHO) method for the Oseen problem under a generalized
local projection setting. These methods are known to allow general polygonal meshes. We show
that the method is stable under a "SUPG-like" norm and prove a priori error estimates for the same.
