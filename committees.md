---
title: Chair and Committees
---

{% for fac in site.data.faculty %}
{% if fac.chair %}

### Department Chair : [{{ fac.name }}]({% if fac.website %}{{ fac.website }}{% else %}http://math.iisc.ac.in/~{{fac.user-id}}/{% endif %})

* __Phone :__ +91-80 2293 {{ fac.phone-ext}}
* __Office :__ Room {{ fac.office}}
* __Email :__ {{fac.user-id}} AT iisc DOT ac DOT in, chair DOT math AT iisc DOT ac DOT in 
{% endif %}
{%  endfor %}

---

### Course and Degree Programme committees (DCC/DC/UG-DCC)

By tradition, these committees have the same members but different members are chairs. The __DCC__ is in charge of courses and of research students, the __DC__ is in charge of the integrated PhD programme, and the __UG-DCC__ of the mathematics component and major of the undergraduate programme.

The present members and chairs are as follows:

* Soumya Das (Chair, DCC)
* Thirupathi Gudi (Chair, DC)
* Arvind Ayyer (Chair, UG-DCC)
* Vamsi Pritham Pingali
* R. Venkatesh
* Subhojoy Gupta
* S. Thangavelu (ex-officio as department chair)

---

### Wellness committee

* Radhika Ganapathy
* R. Venkatesh
* Aakanksha Jain
* Mayuresh Londhe

---

### Postdoctoral fellows committee

* Mahesh Kakde
* Purvi Gupta
* Soumya Das
* R. Venkatesh

---

### Computer committee

* Arvind Ayyer
* Thirupathi Gudi

---

### Maintenance committee

* Tirthankar Bhattacharyya
* Harish Seshadri
* Abhishek Banerjee
* S. Thangavelu (ex-officio as department chair)

---

### Library committee

* Apoorva Khare (Convenor)
* Tirthankar Bhattacharyya
* S. Thangavelu

---

### Safety Champion

* Srikanth K. Iyer