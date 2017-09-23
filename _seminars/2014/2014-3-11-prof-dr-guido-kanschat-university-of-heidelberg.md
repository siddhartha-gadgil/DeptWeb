---
date: 2014-3-11
speaker: "Prof. Dr. Guido Kanschat University of Heidelberg"
title: "Discontinuous Galerkin Methods for Diffusion-Dominated Radiative Transfer Problems"
time: "3:30 - 4:30 p.m."
venue: "Lecture Hall I, Department of Mathematics"
---
While discontinuous Galerkin (DG) methods had been developed
and analyzed in the 1970s and 80s with applications in radiative
transfer and neutron transport in mind, it was pointed out later in
the nuclear engineering community, that the upwind DG discretization
by Reed and Hill may fail to produce physically relevant
approximations, if the scattering mean free path length is smaller
than the mesh size. Mathematical analysis reveals, that in this case,
convergence is only achieved in a continuous subspace of the finite
element space. Furthermore, if boundary conditions are not chosen
isotropically, convergence can only be expected in relatively weak
topology. While the latter result is a property of the transport
model, asymptotic analysis reveals, that the forcing into a continuous
subspace can be avoided. By choosing a weighted upwinding, the
conditions on the diffusion limit can be weakened. It has been known
for long time, that the so called diffusion limit of radiative
transfer is the solution to a diffusion equation; it turns out, that
by choosing the stabilization carefully, the DG method can yield
either the LDG method or the method by Ern and Guermond in its
diffusion limit. Finally, we will discuss an efficient and robust
multigrid method for the resulting discrete problems.
