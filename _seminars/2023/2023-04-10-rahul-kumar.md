---
speaker: Rahul Kumar (CIC biomaGUNE, San Sebastian, Spain)
title: "Mathematics for Nanomedicine: From Accelerated acquisitions, Advance Image Processing, to Patient Specific Models"
date: 10 Apr, 2023
time: 3 pm
venue: Microsoft Teams (Online)
series: "APRG Seminar"
website: http://math.iisc.ac.in/~aprg/index.php?id=seminar22-23
---

Nanomedicine is an offshoot of nanotechnology that involves many disciplines, including the manipulation and manufacturing
of materials, imaging, diagnosis, monitoring, and treatment.  An efficient iterative reconstruction algorithm,together with
Total Variation (TV), and a good mathematical model, can be used to enhance the spatial resolution and predictive capabilities.
In this webinar, I will start with our current results using integrated approach for predicting efficient biomarkers for Acute
respiratory distress syndrome (ARDS) and then move to PDE based (Total variation flow) approach for Image denoising which can
have promising applications in denoising medical images from different modalities. In principle, I will be discussing the
below-mentioned topics and their important concepts in dealing with the main markers of cardiovascular diseases, specifically
Pulmonary Hypertension. 

__1. 4D FlowMRI Data Assimilation: Integrated approach reveals new biomarkers for Experimental ARDS conditions.__
The purpose of this study is to characterize flow patterns and several other hemodynamic parameters (WSS, OSI, Helicity) using
computational fluid dynamics model by combining imaging data from 4D-Flow MRI with hemodynamic pressure and flow waveforms from
control and hypertensive subjects (related to acute respiratory distress syndrome).
__This work mainly concerns how to facilitate bench-bedside approach using integrated approach by combining CFD and AI.__

__2. An adaptive $C^0$ interior penalty discontinuous galerkin approximation of second order total variation problems.__
Singular nonlinear fourth order boundary value problems have significant applications in image processing and material science.
We consider an adaptive $C^0$ Interior Penalty Discontinuous Galerkin (C0IPDG) method for the numerical solution of singular
nonlinear fourth order boundary value problems arising from the minimization of functionals involving the second order total
variation. The mesh adaptivity will be  based on an aposteriori error estimator that can be derived by duality arguments. The
fourth order elliptic equation reads as follows:
`\begin{align}
 u + \lambda \nabla \cdot \nabla \cdot \frac{D^2 u}{|D^2 w|} = & \ 0 \quad \mbox{in} \ Q := \Omega, \\
  u = & \ 0 \quad \mbox{on} \ \Gamma,\\
 n_{\Gamma} \cdot\frac{D^2 u} {n_{\Gamma}}  = & \ 0 \quad \mbox{on} \ {\Gamma}.
\end{align}`
