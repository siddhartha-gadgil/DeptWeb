---
speaker: Sruthi Sekar (IISc Mathematics)
title: "Near-optimal Non-malleable Codes and Leakage Resilient Secret Sharing Schemes"
date: 11 March, 2022
time: 9:30 am
venue: Microsoft Teams (online)
series: Thesis
series-prefix: PhD
series-suffix: defence
---

Non-malleable codes (NMCs) are coding schemes that help in protecting crypto-systems under
tampering attacks, where the adversary tampers the device storing the secret and observes
additional input-output behavior on the crypto-system. NMCs give a guarantee that such
adversarial tampering of the encoding of the secret will lead to a tampered secret, which
is either same as the original or completely independent of it, thus giving no additional
information to the adversary. The specific tampering model that we consider in this work,
called the "split-state tampering model", allows the adversary to tamper two parts of the
codeword arbitrarily, but independent of each other. Leakage resilient secret sharing
schemes help a party, called a dealer, to share his secret message amongst n parties in
such a way that any $t$ of these parties can combine their shares to recover the secret, but
the secret remains hidden from an adversary corrupting $< t$ parties to get their complete
shares and additionally getting some bounded bits of leakage from the shares of the
remaining parties.

For both these primitives, whether you store the non-malleable encoding of a message on some
tamper-prone system or the parties store shares of the secret on a leakage-prone system,
it is important to build schemes that output codewords/shares that are of optimal length and
do not introduce too much redundancy into the codewords/shares. This is, in particular,
captured by the rate of the schemes, which is the ratio of the message length to the codeword
length/largest share length. This thesis explores the question of building these primitives
with optimal rates.

The focus of this talk will be on taking you through the journey of non-malleable codes
culminating in our near-optimal NMCs with a rate of 1/3.
