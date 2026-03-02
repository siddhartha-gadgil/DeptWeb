---
speaker: Purba Banerjee (IISc Mathematics)
date: 7 January 2026
time: 2:15 pm
venue: Hybrid - Microsoft Teams (online) and LH-1, Department of Mathematics
title: "Advanced numerical techniques for pricing and hedging of exotic options"
series: Thesis
series-prefix: PhD
series-suffix: colloquium
---

The thesis is divided into two broad categories. The first dedicated to numerical schemes of pricing Bermudan options and the second to static hedging under one-factor
Markovian and model-independent scenarios. The two main important parts of this thesis address one of the most mathematically challenging and widely traded class of
financial derivatives known as “options” and delve into the practical techniques required to price and hedge such options.

In the first work, we present a computationally efficient technique based on the Method of Lines for the approximation of the Bermudan option values via the associated
partial differential equations. The method of lines converts the Black Scholes partial differential equation to a system of ordinary differential equations. The solution of
the system of ordinary differential equations so obtained only requires spatial discretization and avoids discretization in time. Additionally, the exact solution of the
ordinary differential equations can be obtained efficiently using the exponential matrix operation, making the method computationally attractive and straightforward to
implement. An essential advantage of the proposed approach is that the associated Greeks can be computed with minimal additional computations. We illustrate, through
numerical experiments, the efficacy of the proposed method in pricing and computation of the sensitivities for a European call, cash-or-nothing, powered option, and Bermudan
put option.

For the second work, we consider the hedging of European options when the price of the underlying asset follows a single-factor Markovian framework. By working in such a
setting, [1] derived a spanning relation between a given option and a continuum of shorter-term options written on the same asset. In this work, we have extended their
approach to simultaneously include options over multiple short maturities. We then demonstrate a practical implementation of this extension with a finite set of shorter-term
options to determine the hedging error using a Gaussian Quadrature method. A wide range of experiments are performed for both the _Black--Scholes_ and
_Merton Jump Diffusion_ models, illustrating the comparative performance of the two methods.

As the final work, we consider an investor who wishes to hedge a path-dependent option with maturity $T$ using a static hedging portfolio comprising cash, the underlying,
and vanilla options written on the same underlying with maturity $t_1$. We propose a model-free approach to construct such a portfolio. The framework is inspired by the
_primal-dual_ Martingale Optimal Transport (MOT) problem, which was pioneered by [2]. The dual super hedge could be expensive as the traded price is not always the maximum
price. Contrary to the approach in [3], we formulate the robust hedging problem as an optimization problem that determines a portfolio that minimizes the expected worst-case hedging error at $t_1$ (which coincides with the maturity of the options in the hedging portfolio). For a given portfolio, the worst-case scenario corresponds to the
martingale measure with specified marginals at times $t_1, T$ that yields the maximum expected hedging error. The marginals are determined by the vanilla option prices
with maturities $t_1$ and $T$. This formulation leads to a _min-max_ problem. We provide a numerical scheme for solving this problem when a finite number of vanilla
option prices are available. Numerical results on the hedging performance of this model-free approach are presented. We also provide theoretical bounds on the hedging
error at $T$, the maturity of the target option.
