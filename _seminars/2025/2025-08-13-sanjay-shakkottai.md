---
speaker: Sanjay Shakkottai, University of Texas, Austin
title: "Training-Free Approaches for Image Inversion and Editing Using Latent Generative Models"
date: 13 August, 2025
time: 5:30 pm
start_time: 17:30
venue: LH-1, Mathematics Department
series: "Lean & Math-AI Seminar"
---

Diffusion-based generative models transform random noise into images; their inversion aims to transform images back to structured noise that is suitable for recovery and editing. In this talk, we present Reference-Based Modulation (RB-Modulation), a plug-and-play solution for training-free editing and stylization of diffusion models. Existing training-free approaches exhibit difficulties in (a) style extraction from reference images in the absence of additional style or content text descriptions, (b) unwanted content leakage from reference style images, and (c) effective composition of style and content. RB-Modulation is built on a novel stochastic optimal controller where a style descriptor encodes the desired attributes through a terminal cost. The resulting drift not only overcomes the difficulties above, but it also ensures high fidelity to the reference style and adheres to the given text prompt. We also introduce a cross-attention-based feature aggregation scheme that allows RB-Modulation to decouple content and style from the reference image. Next, we study inversion and image editing using Rectified Flow (RF) models (such as Flux, the current state-of-art model for image generation). We present RF-Inversion using dynamic optimal control derived via a linear quadratic regulator. We show that the resulting vector field is equivalent to a rectified stochastic differential equation. Additionally, we extend our framework to design a stochastic sampler for Flux. Our inversion method allows for state-of-art performance in zero-shot inversion and editing, outperforming prior works in stroke-to-image synthesis and semantic image editing, with large-scale human evaluations confirming user preference. Our work is being productionized within Google across several platforms including Pixel and YouTube. 

Projects: (ICLR 2025 — https://rb-modulation.github.io/ ), (ICLR 2025 — https://rf-inversion.github.io/ ), (CVPR 2024 — https://stsl-inverse-edit.github.io/ ), (Tutorial on diffusion models: https://www.youtube.com/watch?v=NJ72iEPRXFk )

#### Biography

Sanjay Shakkottai received his Ph.D. from the ECE Department at the University of Illinois at Urbana-Champaign in 2002. He is with The University of Texas at Austin, where he is a Professor in the Chandra Family Department of Electrical and Computer Engineering, and holds the Cockrell Family Chair in Engineering #15. He is also the Director of the Center for Generative AI, which hosts a campus-wide GPU cluster at UT Austin. He received the NSF CAREER award in 2004 and was elected as an IEEE Fellow in 2014. He was a co-recipient of the IEEE Communications Society William R. Bennett Prize in 2021. He has served as the Editor in Chief of IEEE/ACM Transactions on Networking. His current research interests are in Generative AI, with applications to language models, image editing, and network decision-making. 