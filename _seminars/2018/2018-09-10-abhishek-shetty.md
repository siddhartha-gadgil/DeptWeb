---
speaker: Abhishek Shetty (Microsoft Research, Bangalore)
title: "Non-Gaussian Component Analysis using Entropy Methods"
date: 10 September, 2018
time: 3:30 pm
venue: LH-1, Mathematics Department
series: "Bangalore Probability Seminar"
website: http://math.iisc.ernet.in/~manju/Seminar/seminar.html
---

Non-Gaussian component analysis (NGCA) is a problem in
multidimensional data analysis. Since its formulation in 2006,
NGCA has attracted considerable attention in statistics and
machine learning. In this problem, we have a random variable X
in n-dimensional Euclidean space. There is an unknown subspace U
of the n-dimensional Euclidean space such that the orthogonal
projection of X onto U is standard multidimensional Gaussian and
the orthogonal projection of X onto V, the orthogonal complement
of U, is non-Gaussian, in the sense that all its one-dimensional
marginals are different from the Gaussian in a certain metric
defined in terms of moments. The NGCA problem is to approximate
the non-Gaussian subspace V given samples of X. Vectors in V
correspond to "interesting" directions, whereas vectors in U
correspond to the directions where data is very noisy. The most
interesting applications of the NGCA model is for the case when
the magnitude of the noise is comparable to that of the true signal,
a setting in which traditional noise reduction techniques such as
PCA don't apply directly. NGCA is also related to dimensionality
reduction and to other data analysis problems such as ICA. NGCA-like
problems have been studied in statistics for a long time using
techniques such as projection pursuit.

In this talk, we introduce NGCA and related problems. We will
survey a few of the existing approaches and algorithms. Finally,
we will present an algorithm that recovers the entire non-Gaussian
subspace in polynomial time and using polynomially many samples.
 
This is joint work with Navin Goyal.
