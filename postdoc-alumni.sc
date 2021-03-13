#!/usr/bin/env ./amm
import $file.csvyaml, csvyaml._
val keys = List("name", "position", "phd", "area", "start", "end", "present")

os.write.over(
  os.pwd / "_data" / "inspire-alumni.yaml",
  blob("InspireAlumnilist.csv", keys, _(7) == "Yes")
)
os.write.over(
  os.pwd / "_data" / "postdoc-alumni.yaml",
  blob("PostdocAlumnilist.csv", keys, _(7) == "Yes")
)
