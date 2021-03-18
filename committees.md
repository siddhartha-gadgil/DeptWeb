---
title: Chair and Committees
---

{% for fac in site.data.faculty %}
{% if fac.chair %}

### Department Chair : [{{ fac.name }}]({% if fac.website %}{{ fac.website }}{% else %}http://math.iisc.ac.in/~{{fac.user-id}}/{% endif %})

* __Phone :__ +91-80 2293 {{ fac.phone-ext}}
* __Office :__ Room {{ fac.office}}
* __Email :__ {{fac.user-id}} AT iisc DOT ac DOT in, chair AT iisc DOT ac DOT in 
{% endif %}
{%  endfor %}

---

### Course and Degree Programme committees (DCC/DC/UG-DCC)

By tradition, these committees have the same members but different members are chairs. The __DCC__ is in charge of courses and of research students, the __DC__ is in charge of the integrated PhD programme, and the __UG-DCC__ of the mathematics component and major of the undergraduate programme.

The present members and chairs are as follows:

* Soumya Das (Chair, DCC)
* Thirupathi Gudi (Chair, DC)
* Arvind (Chair, UG-DCC)
* Vamsi Pritham Pingali
* R. Venkatesh
* Subhojoy Gupta
* S. Thangavelu (ex-officio as department chair)

---

### Postdoctoral fellows committee

* Purvi Gupta (Chair)
* R. Venkatesh

---

### Computer committee

* Arvind Ayyer
* Thirupathi Gudi

---

### Library committee

