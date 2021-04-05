import $ivy.`com.lihaoyi::requests:0.6.5`
import $file.csvyaml, csvyaml._
val keys = List("name", "position", "phd", "area", "start", "end", "present")

val inspireData = requests.get(
  "https://docs.google.com/spreadsheets/d/e/2PACX-1vT6OmO3uQpA4uoDaqAHuEu5b0z7rbXDdyXEk3Q4nGhTLKHriC5twC8o-GvzAa_uyg2U19JhnzIZwXcc/pub?gid=1446698535&single=true&output=csv"
).text()

os.write.over(os.pwd / "InspireAlumnilist.csv", inspireData)

os.write.over(
  os.pwd / "_data" / "inspire-alumni.yaml",
  blob("InspireAlumnilist.csv", keys, _(7) == "Yes")
)

val postDocData = requests.get("" +
  "https://docs.google.com/spreadsheets/d/e/2PACX-1vT6OmO3uQpA4uoDaqAHuEu5b0z7rbXDdyXEk3Q4nGhTLKHriC5twC8o-GvzAa_uyg2U19JhnzIZwXcc/pub?gid=912804726&single=true&output=csv"
  ).text()

os.write.over(os.pwd / "PostdocAlumnilist.csv", postDocData)


os.write.over(
  os.pwd / "_data" / "postdoc-alumni.yaml",
  blob("PostdocAlumnilist.csv", keys, _(7) == "Yes")
)
