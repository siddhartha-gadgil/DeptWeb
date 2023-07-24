---
---

# DeptWeb
[![](https://github.com/siddhartha-gadgil/DeptWeb/workflows/jekyll%20test/badge.svg)](https://github.com/siddhartha-gadgil/DeptWeb/actions)

The sources for the web site for Department of Mathematics, IISc

This site is made in Jekyll with formatting using bootstrap.

## Overview


Welcome to the documentation of the Department of Mathematics webppage. This is meant for those maintaining the web page, but you may find it helpful for making suggestions or just out of curiousity. If you just have a correction/suggestion/update, please instead send us an [e-mail](mailto:webmaster.math.iisc.ac.in).

## Ingredients:

The website is built using:

* __Jekyll__ : [Jekyll](https://jekyllrb.com/) is a _static site generator_, and the key technology used. This builds the web page from templates, data and fragments of pages. Details are below.
* __Bootstrap__ : The design is based on Twitter's [Bootstrap](https://getbootstrap.com/), which gives pleasant styling and makes the site mobile optimized.

For the rest of this document, we focus on how the website is built from various files, all on the [github repository](https://github.com/siddhartha-gadgil/DeptWeb). In the other documentation pages, we see details of specific kinds of pages - courses, seminars, people etc.

## Jekyll static sites

The Department web site is a _static site_, this means that it is compiled in advance (like pdf files from latex sources) from source files and data, and simply copied to the server. Briefly, the components are:

* __page__ : this is either an _html_ or a [markdown](https://en.wikipedia.org/wiki/Markdown) file, with some top matter (at the top of the file, between lines  that are just "---") that gives data asssociated to the page (such as its title). A markdown file is essentially a text file with a little formatting, such as __bold__ text, lists and headings.
* __layouts__ : the [layout files](https://github.com/siddhartha-gadgil/DeptWeb/tree/master/_layouts) give the layout for a class of pages. Which layout is used for a page is specified in the top matter of a page, or may be based on defaults. Three layouts are used - the _default_, for a _course_ and for a _seminar_.
* __includes__ : these are components of a web page, which can be included in a specific page, a layout or another include. For instance, the head and foot of web pages are includes, as is the navigation bar.
* __data__ : [data files](https://github.com/siddhartha-gadgil/DeptWeb/tree/master/_data) contain details of people, courses taught by semester etc. This is in a format called [yaml](https://en.wikipedia.org/wiki/YAML).
* __collections__ : These are folders containing groups of source files. For example, [course descriptions](https://github.com/siddhartha-gadgil/DeptWeb/tree/master/_all-courses) form a collection (as do seminars).

The overall configuration is in the [\_config.yml](https://github.com/siddhartha-gadgil/DeptWeb/blob/master/_config.yml) file (for the sake of deployment, there is a slight complication which you can look up in the deployment documentation).

Details of how these are combined to give the various kinds of pages are documented in the details of specific classes of pages listed below.

* [People](#people)
* [Seminars](#seminars)
* [Courses](#courses)
* [Publications](#publications)
* [News and Events](#news-and-events)
* [Navigation](#navigation)
* [Header and Footer](#header-and-footer)
* [Home](#home)

## People

### Data

The data of various groups of people - faculty, students, staff, etc.
are in \_data files\_ in a format called
[YAML](https://en.wikipedia.org/wiki/YAML). For example, below is the
beginning of the file
[faculty.html](https://github.com/siddhartha-gadgil/DeptWeb/blob/master/_data/faculty.yaml).
This is the file that you must edit to update or correct data etc.

```yaml
- name: Arvind Ayyer
  user-id: arvind
  research-areas: Probability, combinatorics, statistical mechanics, mathematical physics, experimental mathematics
  phd: Rutgers
  phone-ext: 3215
  office: X15

- name: Abhishek Banerjee
  user-id: abhishek
  research-areas: Algebraic geometry, noncommutative geometry
  phd: Johns Hopkins
  phone-ext: 3326
  office: X05
  website: https://sites.google.com/site/abhishekb1313/
```

Each faculty member has a separate entry beginning with a hyphen, with
all fields directly below the first (i.e., the format is based on
indentation). The order of the fields does not matter.

**Warning:** The colon has a special meaning, so if an entry has a
colon, enclose it in quotation marks.

Note that if a **website** is not specified for a faculty member, then
it is assumed to be the standard website with url
`https://math.iisc.ac.in/~"user-id"`.

The other groups of people have similar pages, with the data in a *YAML*
file in the [data
folder](https://github.com/siddhartha-gadgil/DeptWeb/tree/master/_data)
and the page itself using this data. Usually only the data file is
updated, with the html file using this edited only for global changes.
However, note that the defaults are different for different groups of
people: for example, if a student\'s entry does not mention a website,
it is assumed that the student does not have a website, so even the
standard website if present should be given.

### Layout

The actual [Faculty web
page](https://github.com/siddhartha-gadgil/DeptWeb/blob/master/faculty.html)
is an *html* page with some templating (using Jekyll\'s \_liquid\_
templating language). Edit this if you want a change affecting all
faculty members or the layout of the page. 

### Common content

The header, footer and navigation bar are included as the page uses the
[default
layout](https://github.com/siddhartha-gadgil/DeptWeb/blob/master/_layouts/default.html).

## Seminars

### Seminar data

Seminars form a **collection**, with each seminar a separate file in the
[\_seminars](https://github.com/siddhartha-gadgil/DeptWeb/tree/master/_seminars)
folder. We follow some conventions for the name and location of a
seminar file.

-   There is a folder for each year. Please put the file in the folder
    corresponding to the year of the seminar (create the folder if it
    does not exist).
-   The filename is of the form `yyyy-mm-dd-speakers-name.md` with
    `yyyy-mm-dd` the date of the seminar. In case this cannot be
    followed (e.g. two talks by a speaker on the same day), please give
    a reasonable name close to this. To avoid mangling the alphabetical
    order, you should begin with the date in the above format.

A seminar file is a markdown file similar to the below sample (which you
may wish to view in **raw** form).

```markdown
---
speaker: Siddhartha Gadgil and Apoorva Khare (IISc Mathematics)
title: "Eigenfunctions Seminar: Homogeneous length functions on Groups: A polymath adventure"
date: 15 January, 2018
time: 4 pm
venue: LH-1, Mathematics Department
---

Terence Tao posted on his blog a question of Apoorva Khare, asking whether the free group on two generators has a 
length function $l: F\_2 \\to\\mathbb{R}$ (i.e., satisfying the triangle inequality) which is _homogeneous_, i.e., 
such that $l(g^n) = nl(g)$. A week later, the problem was solved by an active collaboration of several mathematicians 
(with a little help from a computer) through Tao's blog. In fact a more general result was obtained, 
namely that any homogeneous length function on a group $G$ factors through its abelianization $G/[G, G]$.

I will discuss the proof of this result and also the  process of discovery (in which I had a minor role).
```

-   The **top-matter** (i.e., the part between lines with just three
    hyphens) should contain the date, time, title, speaker, venue.
    Fields other than the sate can be omitted if unknown.
-   **Warning:** The colon has a special meaning, so if an entry has a
    colon, enclose it in quotation marks.
-   As seminars are sorted by date, you must have a valid date. It is
    best to specify this as **yyyy-mm-dd**, but most common formats
    (like that in the above example) work too.
-   The body (i.e., after the topmatter) of the page should have the
    abstract and any other details. You should generally not include the
    title here as it is picked up from the topmatter. Also avoid adding
    the word \"Abstract\".
-   You may use latex formulas. However due to processing by markdown
    (which also uses slash as a special character), use a double-slash
    in place of slash for **inline** latex, as in the above example. For
    displayed latex (i.e., enclosed by double dollars), **do not** use
    double slashes in place of single ones, just use normal latex.

## Courses

There are two different aspects to course listings: the
[course details](#details) and [courses offered by semester](#schedule).
We first look at course details.

### Course Details {#details}

The coures in the catalogue form a *collection*, with one source file
for each courses. These are the files in the
[\_all-courses](https://github.com/siddhartha-gadgil/DeptWeb/tree/841aef95fdd0a790fde35afb9e2e052492f30bf8/_all-courses)
folder. By convention, these have names of the form `ma123.md` for a
course with code \"123\".

A typical course file is as below (you may want to see this in **raw**
form).

```markdown
---
title: Algebraic Number Theory
code: MA 313
books:
  - author: Artin, E. 
    title: Galois Theory
    publ: University of Notre Dame Press, 1944
  - author: Borevich, Z. and Shafarevich, I.
    title: Number Theory
    publ: Academic Press, New York, 1966
  - author: Cassels, J.W. and Frohlich, A. 
    title: Algebraic Number Theory
    publ: Academic Press, New York, 1948
  - author: Hasse, H.  
    title: Zahlentheorie
    publ: Akademie Verlag, Berlin, 1949
  - author: Hecke, E.  
    title: Vorlesungen uber die Theorie der algebraischen Zahlen
    publ: Chelsea, New York, 1948
  - author: Samuel, P.   
    title: Algebraic Theory of Numbers
    publ: Hermann, 1970
prereqs: 
  - Linear algebra (MA 219 or equivalent)
  - 'Basic algebra : Groups, rings, modules (MA 212 or equivalent), and algebraic field extensions'
---
Algebraic preliminaries: Algebraic field extensions: Normal, separable and
Galois extensions. Euclidean rings, principal ideal domains and factorial
rings. Quadratic number fields. Cyclotomic number fields.
Algebraic integers:
Integral extensions: Algebraic number fields and algebraic integers. Norms and
traces. Resultants and discriminants. Integral bases.
Class numbers:Lattices and Minkowski theory. Finiteness of class number.
Dirichlet's unit theorem.
Ramification Theory: Discriminants.
Applications to cryptography.
```

The top-matter, i.e, the part between the lines with just three hyphens,
should include the course code, title and references and prerequisites
as appropriate. Note that this is formatted by indentation (i.e.,
spaces), so ensure that spacing is as in the above example.

**Note:** You must include a course code - if it is not available, use a
placeholder. Courses are sorted by their codes in the catalogue, so the
site will not compile if a course file has no code.

For each course file, the information is automatically included in the
catalogue and a separate page is created for each course. If you wish to
edit the catalogues layout, you should edit its
[source](https://github.com/siddhartha-gadgil/DeptWeb/blob/841aef95fdd0a790fde35afb9e2e052492f30bf8/catalogue.html).
The separate pages for the courses are based on the [course
layout](https://github.com/siddhartha-gadgil/DeptWeb/blob/841aef95fdd0a790fde35afb9e2e052492f30bf8/_layouts/course.html).

### Courses offered by semester {#schedule}

Details of courses offered by semester are in the YAML data file
[courses.yaml](https://github.com/siddhartha-gadgil/DeptWeb/blob/841aef95fdd0a790fde35afb9e2e052492f30bf8/_data/courses.yaml).
An extract from this is as below.

```yaml
jan2018:
  core1:
    - code: MA 213
      name: Algebra II
      instructor: Abhishek Banerjee
      timing: "Tue, Thu: 3:30-5:00"
      credits: "3:1"

    - code: MA 222
      name: Measure and Integration
      instructor: Manjunath Krishnapur
      timing: "Tue, Thu: 2:00-3:30"
      credits: "3:1"

    electives:
    - code: MA 305
      name: Analysis on Lie Groups
      instructor: S. Thangavelu
      timing: "Mon, Wed, Fri: 11:00-12:00"

    - code: MA 341
      name: Matrix Analysis and positivity
      instructor: Apoorva Khare
      timing: "Wed, Fri: 11:00-12:30"
      webpage: http://www.math.iisc.ac.in/~khare/teaching.html

aug2017:
  core1:
    - code: MA 200
      name: Multivariable Calculus
      instructor: Thirupathi Gudi
      credits: "3:1"
```

**Note:**

-   This is grouped by semester.
-   Within each semester, courses are further grouped as *core1*,
    *core2* and *electives*.
-   If there is a webpage for that specific semester, this should be
    given as the website. Otherwise the course description page is
    linked.
-   If credits are not specified, they are taken as \"3:0\".

This data is rendered according to the
[course-list](https://github.com/siddhartha-gadgil/DeptWeb/blob/841aef95fdd0a790fde35afb9e2e052492f30bf8/course-list.html)
source file as [current and
upcoming](math.iisc.ac.in/course-list.html) courses.

## Publications


Publications are displayed in the [Publications
page](math.iisc.ac.in/pubs.html) only, but the data
processing is more complicated than other cases, so is outlined below
(as is the code for the publications page).

### Data and pre-processing

The publications page uses the YAML data file
[pubs.yaml](https://github.com/siddhartha-gadgil/DeptWeb/blob/master/_data/pubs.yaml),
but this is **not** supposed to be directly edited. Instead it is
generated by a script from two sources:

-   [publications.bib](https://github.com/siddhartha-gadgil/DeptWeb/blob/master/_data/publications.bib)
    : this is a BibTeX file generated from MathSciNet primarily by
    searching by Institution \"6-IIS\"
-   [extrapubs.yaml](https://github.com/siddhartha-gadgil/DeptWeb/blob/master/_data/extrapubs.yaml)
    : this has publications that are for whatever reason not (yet) on
    MathSciNet. We see some details of this below.

The BibTeX file is translated to yaml and combined with the extra-pubs
file by the custom script
[bib2yaml](https://github.com/siddhartha-gadgil/DeptWeb/blob/master/bib2yaml.sc).
This is a scala script based on the [Ammonite
REPL](http://ammonite.io/). This has to be run from the base directory
of the repository after updated a source file.

#### The extrapubs file

Below is an extract from the `extrapubs.yaml` file, which is in the yaml
format.

```yaml
- author: "Gupta, Subhojoy and Wolf, Michael"
  title: "Meromorphic quadratic differentials with complex residues and spiralling foliations"
  booktitle: "In the Tradition of Ahlfors--Bers, VII, Contemporary  Mathematics"
  year: 2017
  volume: 696
  pages: 153-181

- author: "Rangarajan, G."
  title: "Symplectic integration of nonlinear Hamiltonian systems"
  journal: "Pramana -- Journal of Physics"
  year: 1997
  volume: 48
  pages: 129
```

Each publication has a separate entry beginning with a hyphen, with all
fields directly below the first (i.e., the format is based on
indentation). The order of the fields does not matter.

**Warning:** The colon has a special meaning, so if an entry has a
colon, enclose it in quotation marks.

### The publications page

file. Edit the [source](https://github.com/siddhartha-gadgil/DeptWeb/blob/master/pubs.html) of the publications page to show how publications are rendered. This
shows the publications for the latest 15 years by year sorted
alphabetically, and then the earlier publications.



## News and Events

&#128679;
## Navigation

&#128679;
## Header and Footer

&#128679;

## Home

&#128679;