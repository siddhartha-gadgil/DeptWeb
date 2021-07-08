---
speaker: Nidhi Rathi (IISc Mathematics)
title: "Algorithmic and Hardness Results for Fundamental Fair-Division Problems"
date: 14 July, 2021
time: 4 pm
venue: Microsoft Teams (online)
series: Thesis
series-prefix: PhD
series-suffix: defence
---


The theory of fair division addresses the fundamental problem of dividing a set of
resources among the participating agents in a _satisfactory_ or _meaningfully fair_
manner. This thesis examines the key computational challenges that arise in various
settings of fair-division problems and complements the existential (and non-constructive)
guarantees and various hardness results by way of developing efficient (approximation)
algorithms and identifying computationally tractable instances.

- Our work in **fair cake division** develops several algorithmic results for allocating
a divisible resource (i.e., the cake) among a set of agents in a _fair/economically efficient_
manner. While strong existence results and various hardness results exist in this setup,
we develop a polynomial-time algorithm for dividing the cake in an approximately fair and
efficient manner. Furthermore, we identify an encompassing property of agents' value
densities (over the cake)—the _monotone likelihood ratio property_ (MLRP)—that enables us
to prove strong algorithmic results for various notions of fairness and (economic) efficiency.

- Our work in **fair rent division** develops a fully polynomial-time approximation scheme (FPTAS)
for dividing a set of discrete resources (the rooms) and splitting the money (rents) among
agents having general utility functions (continuous, monotone decreasing, and piecewise-linear),
in a _fair_ manner. Prior to our work, efficient algorithms for finding such solutions were know
n only for a specific set of utility functions. We complement the algorithmic results by proving
that the fair rent division problem (under genral utilities) lies in the intersection of the
complexity classes, PPAD (Polynomial Parity Arguments on Directed graphs) and PLS (Polynomial
Local Search).

- Our work respectively addresses fair division of rent, cake (divisible), and discrete
(indivisible) goods in a **partial information setting**. We show that, for all these settings
and under appropriate valuations, a fair (or an approximately fair) division among $n$ agents
can be efficiently computed using only the valuations of $n-1$ agents. The $n$th (secretive)
agent can make an arbitrary selection after the division has been proposed and, irrespective of
her choice, the computed division will admit an overall fair allocation.
