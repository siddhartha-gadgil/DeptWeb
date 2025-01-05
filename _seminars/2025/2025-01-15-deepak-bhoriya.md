---
speaker: Deepak Bhoriya (University of Notre Dame, USA)
title: "General purpose alternative finite difference WENO schemes (AFD-WENO) for hyperbolic conservative systems and systems with non-conservative products (with PCP and Div-preserving)"
date: 15 Jan, 2025
time: 4 pm
venue: Microsoft Teams (online)
series: "APRG Seminar"
website: https://math.iisc.ac.in/~aprg/index.php?id=seminar24-25
---

Classical FD-WENO (and AFD-WENO) schemes have been available for conservation laws since the early papers by Shu and Osher [1988,1989]. Until very recently, all
variants of the Finite Difference WENO scheme have indeed been restricted to treating only hyperbolic systems that are in the conservation form. The recent
emergence of several classes of hyperbolic systems with non-conservative products exposes a dire need for a new class of finite difference WENO schemes that can
handle such systems. To fulfill this need, we present an AFD-WENO (Alternative Finite Difference WENO) algorithm for hyperbolic systems with non-conservative products.

We present the methodology in a fluctuation form that is carefully engineered to retrieve the flux form when warranted and nevertheless extends to non-conservative
products. The method is flexible because it allows any Riemann solver to be used. The formulation we arrive at is such that when non-conservative products are absent,
it reverts exactly to the formulation in the citation above, which is in the exact flux conservation form. The speeds of our new AFD-WENO schemes are compared to the
speed of the classical FD-WENO algorithm. At all orders, AFD-WENO outperforms FD-WENO. We also show a very desirable result that higher-order variants of AFD-WENO
schemes do not cost that much more than their lower-order variants. This is because the larger number of floating point operations associated with larger stencils
is almost very efficiently amortized by the CPU when the AFD-WENO code is designed to be cache-friendly. This should have great and very beneficial implications for
the role of our AFD-WENO schemes in Peta- and Exascale computing.

We apply the method to several stringent test problems drawn from the Baer-Nunziato system, two-layer shallow water equations, and the multicomponent debris flow.
The method meets its design accuracy for smooth flow and can handle stringent problems in one and multiple dimensions. Because of the pointwise nature of its update,
AFD-WENO for hyperbolic systems with non-conservative products has also been shown to be a very efficient performer in solving problems with stiff source terms.
