import $ivy.`com.github.tototoshi::scala-csv:1.3.7`
import com.github.tototoshi.csv._
def blob(filename: String, keys: List[String]): String = {
  val reader = CSVReader.open(new java.io.File(filename))
  val lines = reader.all()
  val data = lines.tail.filter(_(7) == "Yes").map(_.init)
  val dataStrings = data.map(cols =>
    cols.zipWithIndex.map { case (d, j) =>
      s"""${keys(j)}:${if (d.nonEmpty) s""" "${d.trim}" """ else ""} """
    }
  )
  dataStrings.map(_.mkString("- ", "\n  ", "\n")).mkString("", "\n", "\n")
}
