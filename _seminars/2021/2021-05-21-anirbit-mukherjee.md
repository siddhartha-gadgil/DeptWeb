---
speaker: Anirbit Mukherjee (Wharton, University of Pennsylvania, USA)
title: "Recent developments in the mathematics of neural nets"
date: 21 May, 2021
time: 6 pm
venue: Microsoft Teams (online)
series: "APRG Seminar"
website: http://math.iisc.ac.in/~aprg/index.php?id=seminar20-21
---

A profound mathematical mystery of our times is to be able to explain the phenomenon
of training neural nets i.e "deep-learning". The dramatic progress of this approach
in the last decade has gotten us the closest we have ever been to achieving "artificial
intelligence". But trying to reason about these successes - for even the simplest of
nets - immediately lands us into a plethora of extremely challenging mathematical questions,
typically about discrete stochastic processes. In this talk we will describe the various
themes of our work in provable deep-learning.

We will start with a brief introduction to neural nets and then see glimpses of our
initial work on understanding neural functions, loss functions for autoencoders and
algorithms for exact neural training. Next, we will explain our recent result about
how under mild distributional conditions we can construct an iterative algorithm which
can be guaranteed to train a ReLU gate in the realizable setting in linear time while
also keeping track of mini-batching - 

and its provable graceful degradation of performance under a data-poisoning attack. We
will show via experiments the intriguing property that our algorithm very closely mimics
the behaviour of Stochastic Gradient Descent (S.G.D.), for which similar convergence
guarantees are still unknown. 

Lastly, we will review this very new concept of "local elasticity" of a learning process
and demonstrate how it appears to reveal certain universal phase transitions during neural
training. Then we will introduce a mathematical model which reproduces some of these key
properties in a semi-analytic way. We will end by delineating various exciting future
research programs in this theme of macroscopic phenomenology with neural nets.
