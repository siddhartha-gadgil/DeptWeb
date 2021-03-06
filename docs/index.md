---
title: Web page code
---

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

* [People](people.html)
* [Seminars](seminars.html)
* [Courses](courses.html)
* [Publications](publications.html)
* [News and Events](news.html)
* [Navigation, Header and Footer](nav.html)
* [Home](home.html)
