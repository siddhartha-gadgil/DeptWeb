---
published: false
---

# Email tree

Page: `/email-tree.html`, under **Resources → Utilities → Email tree**.

## Files

All page files are in `email-tree/`:

- `index.html`: page content and list data in its YAML front matter.
- `_email-tree-node.html`: renders the hierarchy using `include_relative`.
- `email-tree.css`: styles scoped to the page.
- `email-tree.js`: selection, email popup, copying, and annual cohort refresh.
- `README.md`: this file.

The shared navigation link and expiry of its seven-day `new` badge remain in
`_includes/navbar.html`.

## Updating lists

Edit `email_tree` in `index.html`'s front matter. Keep `all.math` first. Each other entry has a
`list`, `parent`, and `audience`. A parent must match another entry's list name.
Use lowercase list names without `@iisc.ac.in`; JavaScript adds the domain.

For a student program, `cohort_prefix`, `cohort_suffix`, and `cohort_years`
generate its joining-year lists. For example, `bsc` + `26` + `.math` gives
`bsc26.math`. The number is the year students joined the Department.

The chart shows the latest 6 PhD, 8 Integrated PhD, and 4 B.Sc. and B.Tech
joining years, including the current year. Jekyll generates them at build time;
JavaScript refreshes them from the browser's year. No annual data edit is needed.
`XX` labels are naming examples, not selectable lists.

## Interaction

- Click a box to select it and highlight its included groups.
- The popup shows the full email address. Only its copy icon copies the address.
- Click the selected box again, click outside, or press Escape to clear.
- Without JavaScript, the hierarchy remains readable; add `@iisc.ac.in` to a list name.

## Check changes

From the repository root:

```sh
bundle exec jekyll build --destination /tmp/email-tree-build
git diff --check
```

Check the page at desktop and mobile widths. Verify parent highlighting,
cohort names, popup addresses, copying, keyboard access, and deselection.

The page keeps its `/email-tree.html` permalink. The node template's leading
underscore and this README's `published: false` keep both out of the built site.
