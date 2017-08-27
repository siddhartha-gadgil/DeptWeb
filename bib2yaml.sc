import $ivy.`org.jbibtex:jbibtex:1.0.5`
import org.jbibtex._
import scala.collection.JavaConversions._

import java.io._
val parser = new BibTeXParser()
val reader= new java.io.FileReader("_data/publications.bib")

val db = parser.parse(reader)

val regex = "[^a-zA-Z0-9 \\-,.\\\\$\\{\\}\\(\\)_\\^]".r
def fix(s: String) =
  regex.replaceAllIn(s, "").replace("\\", "\\\\")
val mp = db.getEntries.toMap.values.map(_.getFields.toMap.map{
  case (k, v) => k.getValue.toLowerCase -> v.toUserString})
val out = mp.map((h) => h.map {
  case (k, v) if k == "year" => s"$k: $v"
  case (k, v)  => s"""$k: "${fix(v.replace("\n", " "))}""""
  }.mkString("- ", "\n  ", "\n")).mkString("\n")

import ammonite.ops._
def run = write.over(pwd / "_data" / "pubs.yaml", out)

run
