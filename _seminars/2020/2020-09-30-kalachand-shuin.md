---
speaker: Kalachand Shuin (IISER Bhopal)
title: Bilinear spherical maximal functions of product type
date: 30 September 2020
time: 4 pm
venue: Microsoft Teams (online)
series: "APRG Seminar"
website: http://math.iisc.ac.in/~aprg/index.php?id=seminar20-21
---

The spherical averages often make their appearance in partial differential
equations. For instance, the solution of the wave equation
\begin{equation*}
u_{tt}=\Delta u,\ \ u(x,0)=0,\ \ u_{t}(x,0)=f(x),\ \ in\ \ \mathbb{R}^{3}\ \ is
\end{equation*}
\begin{equation*}
u(x,t)=\frac{t}{4\pi}\int_{\mathbb{S}^{2}}f(x-ty)d\sigma(y),
\end{equation*}

where $d\sigma$ is the rotation invariant, normalized surface measure
on the sphere $\mathbb{S}^{2}$. In [Proc. Natl. Acad. Sci. USA (1976)],
Stein proved the following result:

**Theorem.** _Let $n \geq 3$. Then_
$$\Vert \sup_{t>0} \int_{\mathbb{S}^{n-1}}f(x-ty)d\sigma(y) \Vert_{L^{p}(\mathbb{R}^{n})}
\leq C_{p}\Vert f\Vert_{L^{p}(\mathbb{R}^{n})}$$

_if, and only if $\frac{n}{n-1}<p\leq\infty$._

The above result was extended to dimension $n=2$, by Bourgain in [J. d'Anal. Math. (1986)].
Later, in [J. d'Anal. Math. (2019)] Lacey proved sparse domination for both lacunary and
full spherical maximal functions.

In this talk, I shall talk about the bilinear spherical maximal functions of product type,
which is defined in the spirit of bilinear Hardy--Littlewood maximal function. The lacunary
and full bilinear spherical maximal functions are defined by
$$\mathcal{M}_{lac}(f_{1},f_{2})(x):= \sup_{j\in\mathbb{Z}} \prod_{i=1,2} \int_{\mathbb{S}^{n-1}}
f_{i}(x-2^{j}y_{i})d\sigma(y_{i}),$$

$$\mathcal{M}_{full}(f_{1},f_{2})(x):= \sup_{r>0} \prod_{i=1,2} \int_{\mathbb{S}^{n-1}}
f_{i}(x-ry_{i})d\sigma(y_{i}),$$

where $f_{1},f_{2}\in\mathcal{S}(\mathbb{R}^{n})$, the Schwartz class. We have investigated
the sparse domination and weighted boundedness of both the operators $\mathcal{M}\_{lac}$ and
$\mathcal{M}\_{full}$ with respect to the bilinear Muckenhoupt weights $A_{\vec{p},\vec{r}}$.

