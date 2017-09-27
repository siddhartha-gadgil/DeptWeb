---
speaker: Anirban Basak (Weizmann Institute of Science, Israel)
title: "Invertibility and condition number of sparse random matrices"
date: October 5, 2017
time: 4pm
venue: LH-1, Mathematics Department
---
I will describe our work that establishes (akin to) von Neumann's
conjecture on condition number, the ratio of the largest and the
smallest singular values, for sparse random matrices. Non-asymptotic
bounds on the extreme singular values of large matrices have numerous
uses in the geometric functional analysis, compressed sensing, and
numerical linear algebra. The condition number often serves as a
measure of stability for matrix algorithms. Based on simulations
von Neumann and his collaborators conjectured that the condition
number of a random square matrix of dimension $n$ is $O(n)$. During
the last decade, this conjecture was proved for dense random matrices. 

Sparse matrices are abundant in statistics, neural networks, financial
modeling, electrical engineering, and wireless communications. Results
for sparse random matrices have been unknown and requires completely
new ideas due to the presence of a large number of zeros. We consider
a sparse random matrix with entries of the form $\xi\_{i,j} \delta\_{i,j},
\, i,j=1,\ldots,n$, such that $\xi\_{i,j}$ are i.i.d. with zero mean and 
unit variance and $\delta\_{i,j}$ are i.i.d. Ber$(p_n)$, where $p_n
\downarrow 0$ as $n \to \infty$. For $p_n < \frac{\log n}{n}$, this
matrix becomes non-invertible, and hence its condition number equals
infinity, with probability tending to one. In this talk, I will describe
our work showing that the condition number of such sparse matrices (under
certain assumptions on the moments of $\\{\xi\_{i,j}\\}$) is
$O(n^{1+o(1)})$ for all $p_n > \frac{\log n}{n}$, with probability
tending to one, thereby establishing the optimal analogous version of
the von Neumann's conjecture on condition number for sparse random
matrices.

This talk is based on a sequence of joint works with Mark Rudelson.
