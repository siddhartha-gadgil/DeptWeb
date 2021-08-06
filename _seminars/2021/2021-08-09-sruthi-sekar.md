---
speaker: Sruthi Sekar (IISc Mathematics)
title: "Near-Optimal Non-malleable Codes and Leakage Resilient Secret Sharing Schemes"
date: 9 August, 2021
time: 2 pm
venue: Microsoft Teams (online)
series: Thesis
series-prefix: PhD
series-suffix: colloquium
---

Non-malleable codes (NMCs) are coding schemes that help in protecting crypto-systems under
tampering attacks, where the adversary tampers the device storing the secret and observes
additional input-output behavior on the crypto-system. NMCs give a guarantee that such
adversarial tampering of the encoding of the secret will lead to a tampered secret, which
is either same as the original or completely independent of it, thus giving no additional
information to the adversary. Leakage resilient secret sharing schemes help a party, called
a dealer, to share his secret message amongst $n$ parties in such a way that any $t$ of these
parties can combine their shares to recover the secret, but the secret remains hidden from an
adversary corrupting $< t$ parties to get their complete shares and additionally getting some
bounded bits of leakage from the shares of the remaining parties.

For both these primitives, whether you store the non-malleable encoding of a message on some
tamper-prone system or the parties store shares of the secret on a leakage-prone system, it is
important to build schemes that output codewords/shares that are of optimal length and do not
introduce too much redundancy into the codewords/shares. This is, in particular, captured by
the rate of the schemes, which is the ratio of the message length to the codeword length/largest
share length. The research goal of the thesis is to improve the state of art on rates of these
schemes and get near-optimal/optimal rates.

In this talk, I will specifically focus on leakage resilient secret sharing schemes, describe
the leakage model, and take you through the state of the art on their rates. Finally, I will
present a recent construction of an optimal (constant) rate, leakage resilient secret sharing
scheme in the so-called "joint and adaptive leakage model" where leakage queries can be made
adaptively and jointly on multiple shares.
