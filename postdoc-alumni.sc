import $ivy.`com.github.tototoshi::scala-csv:1.3.7`
import com.github.tototoshi.csv._
val reader1 = CSVReader.open(new java.io.File("Postdocalumnilist-1.csv"))
val lines1 = reader1.all()
val data1 = lines1.tail.filter(_(7) == "Yes").map(_.init)
val keys = List("name", "position", "phd", "area", "start", "end", "present")
val dataStrings1 = data1.map(cols => cols.zipWithIndex.map{case (d, j) => s"""${keys(j)}:${if (d.nonEmpty) s""" "${d.trim()}" """ else ""} """}) 
val blob1 = dataStrings1.map(_.mkString("- ", "\n  ", "\n")).mkString("","\n", "\n")
os.write.over(os.pwd / "_data"/ "postdoc-alumni.yaml", blob1 )

val reader = CSVReader.open(new java.io.File("Postdocalumnilist.csv"))
val lines = reader.all()
val data = lines.tail.filter(_(7) == "Yes").map(_.init)
val dataStrings = data.map(cols => cols.zipWithIndex.map{case (d, j) => s"""${keys(j)}:${if (d.nonEmpty) s""" "$d" """ else ""} """}) 
val blob= dataStrings.map(_.mkString("- ", "\n  ", "\n")).mkString("","\n", "\n")
os.write.append(os.pwd / "_data"/ "postdoc-alumni.yaml", blob )
