---
speaker: T.E.S. Raghavan (University of Illinois at Chicago, USA)
title: "On the applications of scaling the entries of a matrix"
date: 8 December, 2025
time: 3 pm
venue: LH-1, Mathematics Department
series: "APRG Seminar"
website: https://math.iisc.ac.in/~aprg/index.php?id=seminar25-26
---

Various biological, statistical, and social science data come in the form of cross-classified counts tables commonly known as contingency tables. Scaling the cell entries
of such multidimensional matrices involves both mathematically and statistically well-posed problems of practical interest. In this talk, we first describe several
situations where scaling can be useful.

We also describe a natural scaling algorithm in the problem of scaling a nonnegative matrix to obtain the prescribed row and column sums. We consider the problem of maximum
likelihood estimation in contingency tables. This area of statistics, which forms part of discrete multivariate analysis, is of considerable interest to researchers at
present. We will motivate this topic of scaling with examples chosen from 1. Budget allocations 2. Infra structure planning, 3. Gaussian elimination and problems of
accuracy. 4. Arriving at approximate maximum likelihood estimates in say a 2 by 2 by 2 contingency table.

The last part of the talk is to give the basic steps of the formal proof of the following general theorem based on Kronecker’s surjective mapping theorem and the duality
theorem of linear programming. which subsumes all such problems. We finally discuss several intuitive algorithms to handle some of these problems.

*Theorem.* Let $K$ be a bounded non-empty polyhedron given by:
\begin{equation}
K = \\{ \pi ∈ \mathbb{R}^n : \pi \geq 0, C \pi = b \\},
\end{equation}
where $C = (c_{ij})$ is an $m \times n$ matrix with real entries and $b \in \mathbb{R}^m$ is a non-zero vector. Let $y \in K$. Then for any $x \geq 0$ with the same
zero-nonzero pattern as $y$, there exist $z_{i} > 0$, $i = 1, \dots ,m$ and there exists a $\pi \in K$ such that
\begin{equation}
\pi_{j} = x_{j} \prod_{i=1}^m z^{c_{ij}}, \quad j=1,\dots, n.
\end{equation}
