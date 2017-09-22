import ammonite.ops._, ImplicitWd._

val semlist = ls.rec(pwd / "m-seminar").filter(_.ext == "eml" )
lazy val dat = semlist.map((f) => read(f).replace("\r", "").replace("\n>", "\n").replace("<br>", "").replace("\u00a0", " ").replace("\ufffd", "")).toVector
lazy val chunks = dat.map (_.split("\n\n").toVector)
def t(s: String) = !(s.contains("Message-I")) && !(s.contains("Forwarded")) && !(s.contains("<div")) && (s.contains("Title"))
def d(s: String) = !(s.contains("Message-I")) && !(s.contains("Forwarded")) && (s.contains("Date"))
def tt(s: String) = !(s.contains("Message-I")) && !(s.contains("Forwarded")) && !(s.contains("<div")) && !(s.contains("=A0")) && !(("(Title|TITLE)[\\s]*:").r.findFirstIn(s).isEmpty)
def sp(s: String) = !(s.contains("Message-I")) && !(s.contains("Forwarded")) && !(s.contains("<div")) && !(s.contains("=A0")) && !(("Speaker[\\s]*:").r.findFirstIn(s).isEmpty)

def data(f: Path) =
  read(f).
  replace("\r", "").
  replace("\n>", "\n").
  replace("<br>", "").replace("\u00a0", " ").replace("\ufffd", "")
def chunk(s: String) = s.split("\n\n").toVector
def title(s: String) = chunk(s).dropWhile(!tt(_)).headOption

import scala.io.StdIn._
def purge(f: Path) = {
  %("clear")
  println(read(f))
  println("\nDelete file?")
  val ans = readLine()
  if (ans.contains("y")) rm(f)
}

val nottitle = semlist.filter((p) => title(data(p)).isEmpty)

def imi(f: Path) =
  {val s = read(f)
    s.contains("Initiative") || s.contains("INITIATIVE") || s.contains("PRINCIPAL INVESTIGATOR") || s.contains("INITIAITVE")
  }

lazy val ims = semlist.filter(imi)

lazy val suspect = nottitle filter (!imi(_))
