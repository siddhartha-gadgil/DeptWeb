import ammonite.ops._
val f = pwd / "_data" / "publications.bib"
val outf = pwd / "_data" / "pubs.csv"

val yf = pwd / "_data" / "pubs.yaml"

def rawtxt = read.lines(f)

@annotation.tailrec
def mergeLines(l : Vector[String], accum: Vector[String] = Vector(), closed: Boolean = true) : Vector[String] = l match {
  case Vector() => accum
  case head +: tail =>
    val cl = head.contains("},") || head == "}" || head == "" || head.contains("@article")
    if (closed)
      mergeLines(tail, accum :+ head, cl)
    else
      mergeLines(tail, accum.init :+ (accum.last.trim + " " + head.trim), cl)
}

def txt = mergeLines(rawtxt)

def entries(lines: Vector[String]) : Vector[Vector[String]]  =
  if (lines.isEmpty) Vector() else {
    val head = lines.takeWhile(_ != "")
    val tail = lines.drop(head.length + 1)
    head +: entries(tail)
  }
def getField(name: String, ent: Vector[String]) =
  {
    val trimmed = ent map (_.trim)
    trimmed.find(_.startsWith(name.toUpperCase)). map (_.drop(name.length + 4).dropRight(2)).getOrElse("")
  }

val topVec = Vector("author", "title", "journal", "volume", "pages", "year")

def bioVec(ent: Vector[String]) = {
  topVec. map ((s) => getField(s, ent))
}

def csvRow(cs: Vector[String]) = (cs map ((x) => if (x.contains(",")) "\""+x + "\"" else x)).mkString(", ")+"\n"

def writeRow(cs: Vector[String]) = write.append(outf, csvRow(cs))

def writeAll = {
  writeRow(topVec)
  val rows = entries(txt) map (bioVec)
  rows.foreach(writeRow)
}

def writeYAML = entries(txt).foreach{ (ent) =>
  write.append(yf, "- ")
  write.append(yf, s"${topVec.head}: ${getField(topVec.head, ent).replace(":", "\':\'").replace("}", "").replace("{", "")}\n")
  topVec.tail.foreach{
    (name) => write.append(yf, s"  $name: ${getField(name, ent).replace(":", "\':\'").replace("}", "").replace("{", "")}\n")
  }
  write.append(yf, "\n")
}


def bioPubs(lines: Vector[String]) = {
  entries(lines).zipWithIndex.map {
    case (ent, n) => (n + 2).toString +: bioVec(ent)
  }
}
