import $file.csvyaml, csvyaml._
val keys = List("name", "position", "phd", "area", "start", "end", "present")

os.write.over(os.pwd / "_data"/ "postdoc-alumni.yaml", blob("Postdocalumnilist-1.csv", keys) )
os.write.append(os.pwd / "_data"/ "postdoc-alumni.yaml", blob("Postdocalumnilist.csv", keys) )
