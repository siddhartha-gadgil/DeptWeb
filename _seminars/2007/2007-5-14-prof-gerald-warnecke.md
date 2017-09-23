---
date: 2007-5-14
speaker: "Prof. Gerald Warnecke, Magdeburg, Germany"
title: "Numerical approximation of population balance equations in process engineering"
time: "4.00 p.m." 
time: "Lecture Hall - I, Dept. of Mathematics"
---
Population balance equations are widely used in many chemical and 
particle process engineering problems involving crystallization, fluidized 
bed granulation, aerosols etc. Analytical solutions are available only for 
a limited number of simplified problems and therefore numerical solutions 
are frequently needed to solve a population balance problem. A general 
population balance equation for simultaneous aggregation, breakage, growth 
and nucleation in a well  mixed system is given as an integro-partial 
differential equation for a particle property distribution function.


Sectional methods are well known for their simplicity and 
conservation properties. Therefore numerical techniques belonging to this 
category are the most commonly used. In these methods, all particles 
within a computational cell, which in some papers is called a class, 
section or interval, are supposed to be of the same size. These methods 
divide the size range into small cells and then apply a balance equation 
for each cell. The continuous population balance equation is then reduced 
to a set of ordinary differential equations. However, it is well known 
that the numerical results by previous sectional methods were rather 
inaccurate. Furthermore, there is a lack of numerical schemes in the 
literature which can be used to solve growth, nucleation, aggregation, and 
breakage processes, i.e. differential and integral terms, simultaneously.


We present a new numerical scheme for solving a general population 
balance equation which assigns particles within the cells more precisely. 
The technique follows a two step strategy. The first is to calculate the 
average size of newborn particles in a cell and the other to assign them 
to neighboring nodes such that important properties of interest are 
exactly preserved. The new technique preserves all the advantages of 
conventional discretized methods and provides a significant improvement in 
predicting the particle size distributions. The technique allows the 
convenience of using geometric- or equal-size cells. The numerical results 
show the ability of the new technique to predict very well the time 
evolution of the second moment as well as the complete particle size 
distribution. Moreover, a special way of coupling the different processes 
has been described. It has been demonstrated that the new coupling makes 
the technique more useful by being not only more accurate but also 
computationally less expensive. Furthermore, a new idea that considers the 
growth process as aggregation of existing particle with new small nuclei 
has been presented. In that way the resulting discretization of the growth 
process becomes very simple and consistent with first two moments. 
Additionally, it becomes easy to combine the growth discretization with 
other processes. Moreover all discretizations including the growth have 
been made consistent with first two moments. The new discretization of 
growth is a little diffusive but it predicts the first two moments exactly 
without any computational difficulties like appearance of negative values 
or instability etc.


The accuracy of the scheme has been assessed partially by 
numerical analysis and by comparing analytical and numerical solutions of 
test problems. The numerical results are in excellent agreement with the 
analytical results and show the ability to predict higher moments very 
precisely.  Additionally, an extension of the proposed technique to higher 
dimensional problems is discussed
