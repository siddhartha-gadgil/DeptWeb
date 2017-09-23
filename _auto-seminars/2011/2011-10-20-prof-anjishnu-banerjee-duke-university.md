---
date: 2011-10-20
speaker: "Prof. Anjishnu Banerjee Duke University, USA"
title: "Challenges in high dimensional Bayesian nonparametrics and some possible solutions"
time: "11:30 a.m.-12:30 p.m."
venue: "Lecture Hall III, Department of Mathematics"
---
Large dimensional data presents many challenges for
statistical modeling via Bayesian nonparametrics, both with respect
to theroetical issues and computational aspects. We discuss some
models that can accomodate large dimensional data and have attractive
theoretical properties, specially focussing on kernel partition
processes, which are a generalization of the well known Dirichlet
Processes. We discuss issues of consistency. We then move onto some
typical computational problems in Bayesian nonparametrics, focussing
initially on Gaussian processes (GPs). GPs are widely used in
nonparametric regression, classification and spatio-temporal
modeling, motivated in part by a rich literature on theoretical
properties. However, a well known drawback of GPs that limits their
use is the expensive computation, typically O($n^3$) in performing
the necessary matrix inversions with $n$ denoting the number of data
points. In large data sets, data storage and processing also lead to
computational bottlenecks and numerical stability of the estimates
and predicted values degrades with $n$. To address these problems, a
rich variety of methods have been proposed, with recent options
including predictive processes in spatial data analysis and subset of
regressors in machine learning. The underlying idea in these
approaches is to use a subset of the data, leading to questions of
sensitivity to the subset and limitations in estimating fine scale
structure in regions that are not well covered by the subset.
Partially motivated by the literature on compressive sensing, we
propose an alternative random projection of all the data points onto
a lower-dimensional subspace, which also allows for easy
parallelizability for further speeding computation. We connect this
with a wide class of matrix approximation techniques. We demonstrate
the superiority of this approach from a theoretical perspective and
through the use of simulated and real data examples. We finally
consider extensions of these approaches for dimension reduction in
other non parametric models.
