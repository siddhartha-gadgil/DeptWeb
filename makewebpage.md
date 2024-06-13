---
title: Make your web page
---
### Creating a webpage hosted on the maths server

#### Designing your web page

* [Mozilla](https://developer.mozilla.org/en-US/docs/Learn) has a nice set of resources for learning web development (and it caters to all appetites, including novices).
* Alternatively, one can browse through standard resources such as [W3schools](https://www.w3schools.com/html/).
* One can also simply copy your favourite faculty member's webpage design by right clicking anywhere on that webpage and clicking on view source.

#### Making a page on our server

* Your web page is the file `index.html` in the directory `public_html` in your home folder on the server `math.iisc.ac.in`, i.e., for example `/home/mickeymouse/public_html`

* __NOTE:__ For your web page to be seen, you must set permissions for it to be readable (see below for how to do this).

* To make a web page, `ssh` into your mathematics account. (See [this page for details.](https://math.iisc.ac.in/sshinfo.html)) create a folder called `public_html` and give it the correct permissions.
```bash
mkdir public_html
chmod go+rX public_html
```

* Create your webpage (i.e., file named `index.html`) locally, i.e., on your PC and upload all the files (including index.html which will be your main page) to the `public_html` folder on math using an sftp client such as [psftp](https://www.chiark.greenend.org.uk/~sgtatham/putty/latest.html).

* Your webpage should be active. The address is "http://www.math.iisc.ac.in/~yourusername".
