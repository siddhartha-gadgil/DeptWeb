---
date: 2015-6-24
speaker: "Sivaram Ambikasaran (NYU)"
title: "Fast algorithms for data analysis and elliptic partial differential equations"
venue: ""
---
Large-dense matrices arise in numerous applications: boundary
integral formulation for elliptic partial differential equations,
covariance matrices in statistics, inverse problems, radial basis function
interpolation, multi frontal solvers for sparse linear systems, etc. As
the problem size increases, large memory requirements, scaling as O(N^2),
and extensive computational time to perform matrix algebra, scaling as
O(N^2) or O(N^3), make computations impractical. I will discuss some novel
methods for handling these computationally intense problems. In the first
half of the talk, I will discuss my contributions to some of the new
developments in handling large dense covariance matrices in the context of
computational statistics and Bayesian data assimilation. More
specifically, I will be discussing how fast dense linear algebra (O(N)
algorithms for inversion, determinant computation, symmetric
factorisation, etc.) enables us to handle large scale Gaussian processes,
thereby providing an attractive approach for big data applications. In the
second half of the talk, I will focus on a new algorithm termed Inverse
Fast Multipole Method, which permits solving singular integral equations
arising out of elliptic PDE's at a computational cost of O(N).
