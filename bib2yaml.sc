#!/usr/bin/env amm

import $ivy.`org.jbibtex:jbibtex:1.0.5`
import org.jbibtex._
import scala.collection.JavaConversions._

import java.io._
val parser = new BibTeXParser()
def reader= new java.io.FileReader("_data/publications.bib")

def db = parser.parse(reader)

def ok(c: Char) = {
  val value = c.toInt
  (value == 0x09 || value == 0x0A || value == 0x0D ||
     (value >= 0x20 && value <= 0x7E) ||
     (value == 0x85))
  }

def makeOk(s: String) = s.filter(ok).mkString("")

val regex = "[^a-zA-Z0-9 =<>*+-/#\\-,.\\\\$\\{\\}\\(\\)_\\``\'\"^]".r
def fix(s: String) =
  {
    val purged =
      makeOk(s).
      // regex.
      //   replaceAllIn(s, "").
        replace("""\"{o}""", """\"o""").
        replace("""\"{a}""", """\"a""").
        replace("\\'e", "&eacute;").
        replace("\\'{e}", "&eacute;").
        replace("\\`e", "&egrave;").
        replace("\\\"o", "&ouml;").
        replace("\\`a", "&agrave;").
        replace("\\'a", "&aacute;").
        replace("\\`o", "&ograve;").
        replace("\\'o", "&oacute;").
        replace("\\`O", "&Ograve;").
        replace("\\'O", "&Oacute;").
        replace("\\\"u", "&uuml;").
        replace("\\'E", "&Eacute;").
        replace("\\`E", "&Egrave;").
        replace("\\\"O", "&Ouml;").
        replace("\\\"A", "&Auml;").
        replace("\\\"a", "&auml;").
        replace("\\\"\\i", "&iuml;").
        replace("\\c", "&ccedil;").
        replace("\\`A", "&Agrave;").
        replace("\\'A", "&Aacute;").
        replace("\\\"U", "&Uuml;").
        replace("\\o", "&oslash;").
        replace("\\v s", "&#353;").
        replace("\\v c", "&#263;").
        replace("\\v d", "&#273;").
        replace("""\~n""", "&ntilde;").
        replace("\\`{e}", "&egrave;").
        replace("\\\"{o}", "&ouml;").
        replace("\\`{a}", "&agrave;").
        replace("\\'{a}", "&aacute;").
        replace("\\`{o}", "&ograve;").
        replace("\\'{o}", "&oacute;").
        replace("\\`{O}", "&Ograve;").
        replace("\\'{O}", "&Oacute;").
        replace("\\\"{u}", "&uuml;").
        replace("\\'{E}", "&Eacute;").
        replace("\\`{E}", "&Egrave;").
        replace("\\\"{O}", "&Ouml;").
        replace("\\\"{A}", "&Auml;").
        replace("\\\"{a}", "&auml;").
        replace("\\\"\\i", "&iuml;").
        replace("\\c", "&ccedil;").
        replace("\\`{A}", "&Agrave;").
        replace("\\'{A}", "&Aacute;").
        replace("\\\"{U}", "&Uuml;").
        replace("\\o", "&oslash;").
        replace("\\v s", "&#353;").
        replace("\\v c", "&#263;").
        replace("\\v d", "&#273;").
        // replace("\\", "\\\\").
        replace("--", "-").
        replace("\\'", "&#39;").
        replace("'", "&#39;").
        replace("\\ssf", "\\mathrm").
        replace("\\Cal", "\\mathcal")
        // replace("\"", "\\\"")
    val debraceVec = purged.split('$').toVector.zipWithIndex.map {
      case (x, n) => if (n % 2 == 0) x.replace("{", "").replace("}", "") else x
    }
    debraceVec.mkString("$")
  }
def mp = db.getEntries.toMap.values.map(_.getFields.toMap.map{
  case (k, v) => k.getValue.toLowerCase -> v.toUserString})
def out = mp.map((h) => h.map {
  case (k, v) if k == "year" || k == "url" => s"$k: '$v'"
  case (k, v) => s"""$k: '${fix(v.replace("\n", " "))}'"""
  // case (k, v)  => s"""$k: "${fix(v.replace("\n", " "))}""""
  }.mkString("- ", "\n  ", "\n")).mkString("\n")

import ammonite.ops._
def run = {
  write.over(pwd / "_data" / "pubs.yaml", out)
  val extra = read(pwd / "_data" / "extrapubs.yaml")
  write.append(pwd / "_data" / "pubs.yaml", "\n"+extra)
}

run
