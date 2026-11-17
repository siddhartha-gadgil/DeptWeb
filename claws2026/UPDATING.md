# Updating the website

The participant list and programme live in two plain Markdown files. Open them in any text editor, make your changes, save, and preview the site. No CSV or spreadsheet export is needed.

## Participants

Edit `_data/participants.md`. Each line after the two headings is one person:

```text
Name | Institution
--- | ---
Asha Rao | Indian Institute of Science, Bengaluru
Sam Patel | IIT Delhi
```

Add, remove, or change a line. Keep ` | ` (a space, a vertical bar, and a space) between the name and institution. If the institution is unknown, leave it blank after the separator. Avoid vertical bars inside a name or institution. Add only information intended for the public website.

## Programme

Edit `_data/programme.md`. The four columns are time in IST, 16 December, 17 December, and 18 December:

```text
Time (IST) | 16 December | 17 December | 18 December
--- | --- | --- | ---
09:30–10:00 | Registration | Speaker, Institution | Speaker, Institution
10:30–11:00 | Coffee Break | Coffee Break | Coffee Break
```

Keep each time slot on one line and separate its four columns with ` | `, even if a day is empty. For more than one item in a cell, put `<br>` between them; this displays a line break on the website. Avoid vertical bars within the content. Rows appear in the same order as in the file. If days have different times within a row, include the exact time next to each event as in the existing entries.

The initial programme was copied from the workbook tab **Formatted Draft 16 Nov**. Check it against the confirmed schedule when making updates.

## Preview and publish

From this folder, run:

```sh
jekyll serve
```

Open http://127.0.0.1:4000/ and check the changes. Stop the server with Ctrl+C. Open the local URL, not the source `index.html`: Jekyll needs to build the page first.

When the preview looks right, commit and push the changed Markdown files through the usual website publishing workflow. The original local workbooks and changes document are excluded from the built site; do not commit private source files.
