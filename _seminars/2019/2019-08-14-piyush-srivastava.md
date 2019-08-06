---
speaker: Piyush Srivastava (TIFR, Mumbai)
title: "Deterministic counting using complex zeros (with some help from probability)"
date: 14 August, 2019
time: 2:30 pm
venue: LH-1, Mathematics Department
series: "APRG Seminar"
website: http://math.iisc.ac.in/~aprg/index.php?id=seminar19-20
---

Problems associated with the algorithmic counting of combinatorial structures arise
naturally in many applications and also constitute an interesting sub-field of
computational complexity theory.  In this talk, we consider one of the most studied
of such problems: that of counting proper colourings of bounded degree graphs.  Here,
a constant maximum degree $\Delta$ and a set of $q$ colours are fixed.  The input is
a graph $G$ of maximum degree at most $\Delta$, and a parameter $\varepsilon > 0$.  The
problem is to output "efficiently", i.e. in time that grows polynomially in $1/\varepsilon$
and the size $n$ of the graph, and up to a multiplicative error of at most $(1+\varepsilon)$,
the number of proper colourings of $G$ with the given set of $q$ colours.

The problem has been attacked using randomized Markov chain methods, and a delightfully
simple analysis using the path coupling method gives an efficient randomized algorithm
when $q \geq 2\Delta + 1$.  The best currently known Markov chain analysis, due to Vigoda,
requires $q \geq 11 \Delta / 6$.  However, the condition required for efficient deterministic
algorithms (i.e., those that do not use any randomness and do not have a probability of error)
was until recently $q \geq 2.58 \Delta$, which lagged behind even the simple path coupling
analysis.  In this work, we improve this to $q \geq 2 \Delta$, thus finally matching at least
the path coupling bound for deterministic algorithms.  Our method, based on a paradigm proposed
by Alexander Barvinok, uses information on the _complex_ zeros of the associated Potts model
partition function.  Perhaps surprisingly, the information we need about the complex zeros of
this partition function is obtained using methods inspired from previous analyses of phenomena
related to Markov chain algorithms for the problem.

Part of the talk will also be a general survey of Barvinok's paradigm and the growing body
of work connecting it to more probabilistic notions.

[Joint work](https://arxiv.org/abs/1906.01228) with Singcheng Liu and Alistair Sinclair.
