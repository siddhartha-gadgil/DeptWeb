import ammonite.ops._, ImplicitWd._

val semlist = ls.rec(pwd / "m-seminar").filter(_.ext == "eml" )

def files(d: Path) = ls.rec(d).filter(_.ext == "eml")
def contents(f: Path) = read(f).replace("\r", "").replace("\n>", "\n").replace("<br>", "").replace("\u00a0", " ").replace("\ufffd", "")
lazy val byYear = years.map((y) => (y, files(pwd / "m-seminar" / y.toString).map((f) => contents(f))))

lazy val dat = semlist.map((f) => read(f).replace("\r", "").replace("\n>", "\n").replace("<br>", "").replace("\u00a0", " ").replace("\ufffd", "")).toVector
lazy val chunks = dat.map (_.split("\n\n").toVector)
def t(s: String) = !(s.contains("Message-I")) && !(s.contains("Forwarded")) && !(s.contains("<div")) && (s.contains("Title"))
def d(s: String) =
  !(s.contains("Message-I")) &&
  !(s.contains("Forwarded")) &&
  !(s.contains("From:")) &&
  (s.contains("Date"))
def tt(s: String) = !(s.contains("Message-I")) && !(s.contains("Forwarded")) && !(s.contains("<div")) && !(s.contains("=A0")) && !(("(Title|TITLE)[\\s]*:").r.findFirstIn(s).isEmpty)
def sp(s: String) = !(s.contains("Message-I")) && !(s.contains("Forwarded")) && !(s.contains("<div")) && !(s.contains("=A0")) && !(("Speaker[\\s]*:").r.findFirstIn(s).isEmpty)

def data(f: Path) =
  read(f).
  replace("\r", "").
  replace("\n>", "\n").
  replace("<br>", "").replace("\u00a0", " ").replace("\ufffd", "")
def chunk(s: String) = s.split("\n\n").toVector
def title(s: String) = chunk(s).dropWhile(!tt(_)).headOption
def date(s: String) = chunk(s).dropWhile(!d(_)).headOption

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

import fastparse.all._

val digit = CharIn('0' to '9')

val punc = (" " | "\t" | "," | ".").rep

val nc = CharPred(_ != ':')

val word = CharIn(('a' to 'z') ++ ('A' to 'Z')).rep(1).!
val wrd = CharIn(('a' to 'z') ++ ('A' to 'Z')).rep(1)

val day = ((digit ~ digit | digit).!)~(("st" | "th" | "nd").?)
val year = (P("20")~digit~digit).!
val mdy = nc.rep ~ ":" ~  punc ~ word ~ punc ~ day ~ punc ~ year
val dmy = nc.rep ~ ":" ~  punc ~ day ~ punc ~ word ~ punc ~ year
val md = nc.rep ~ ":" ~  punc ~ word ~ punc ~ day
val dm = nc.rep ~ ":" ~  punc ~ day ~ punc ~ word
val wmdy = nc.rep ~ ":" ~ punc ~ wrd ~  punc ~ word ~ punc ~ day ~ punc ~ year
val wmd = nc.rep ~ ":" ~  punc ~ wrd ~ punc   ~ word ~ punc ~ day
val wdm = nc.rep ~ ":" ~  punc ~ wrd ~ punc ~ day ~ punc ~ word
val wdmy = nc.rep ~ ":" ~  punc ~ wrd ~ punc ~ day ~ punc ~ word ~ punc ~ year

val sl = day ~ ("/"| "-") ~ day ~ ("/"| "-") ~ year
val wsl = nc.rep ~ ":" ~  punc ~ wrd ~ punc ~sl
val ssl = nc.rep ~ ":" ~  punc ~sl

val test = mdy  | dmy | wmdy | wdmy | ssl | wsl | md | dm | wmd | wdm


val dts = dat.map(date).collect{case Some(x) => x}
def parsed(d: String) = test.parse(d).fold((_, _, _) => false ,(_,_)=> true )
def result(d: String) = test.parse(d).fold((_, _, _) => None ,(x,_)=> Some(x) )



val res = dts.map((s) => (s, result(s)))

val wspc = (P(" ") | "\t" | "\n").rep

def kvParse(k: String) =
  wspc ~ P(k)~wspc~":"~wspc~((AnyChar.rep).!)

def kv(k: String, s: String) : Option[String] =
  kvParse(k).parse(s).fold((_, _,_) => None, (a, _) => Some(a))

def kvc(k: String, s: String) =
    s.split("\n\n").map(kv(k, _)).fold[Option[String]](None){case (x, y) => x.orElse(y)}

val monthMap = Vector("jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec").zipWithIndex.toMap

def month(m: String) = monthMap(m.toLowerCase.take(3)) + 1

val mdyParse = (wmdy | mdy).map {case (m, d, y) => (d.toInt, month(m), y.toInt)}
val dmyParse = (wdmy | dmy).map {case (d, m, y) => (d.toInt, month(m), y.toInt)}
val ssParse  = (ssl | wsl).map{case (d, m, y) => (d.toInt, m.toInt, y.toInt)}

val mdParse = (wmd | md).map {case (m, d) => (d.toInt, month(m))}
val dmParse = (wdm | dm).map {case (d, m) => (d.toInt, month(m))}

val withYear = mdyParse | dmyParse | ssParse
def useYear(y: Int) = (mdParse | dmParse).map{case (d, m) => (d, m, y)}


import scala.util.Try
def getDate(s: String, y: Int)=  Try((withYear | useYear(y)).parse(s).fold((_, _, _) => None ,(x,_)=> Some(x))).toOption.flatten


case class Seminar(
  date: (Int, Int, Int),
  speaker: String,
  title: String,
  venueOpt: Option[String],
  timeOpt: Option[String],
  absOpt: Option[String]
){
  val abs = absOpt.getOrElse("").replace("\n ", "\n\n").replace("\n\t", "\n\n")
  val venueSt = venueOpt.map((v) => s"""time: "${trim(v)}"""").getOrElse("")
  val timeSt = timeOpt.map((t) => s"""time: "${trim(t).toLowerCase}" """).getOrElse("")
  val year = date._3
  val out =
s"""---
date: ${year}-${date._2}-${date._1}
speaker: "$speaker"
title: "$title"
$timeSt
$venueSt
---
${abs.trim.replace("\\", "\\\\")}
"""

  val filename=
    s"${year}-${date._2}-${date._1}-${safe(speaker.split(",").head)}.md"

  def save(d: Path) = write.over(d / year.toString / filename, out)
}

def trim(s: String) = "[\\s]+".r.replaceAllIn(s.replace("\"", "").replace("\\", "\\\\"), " ")

def mline(s: String) = trim(s.replace("\n", ","))

def safe(s: String) = "[^a-z\\-]".r.replaceAllIn(s.toLowerCase.replace(" ","-"), "")

def getSem(s: String, y: Int) =
    for {
      dt <- date(s)
      d <- getDate(dt, y)
      sp <- kvc("Speaker", s)
      t <- kvc("Title", s)
    } yield Seminar(d, trim(sp), trim(t), kvc("Venue", s).map(mline), kvc("Time", s), kvc("Abstract", s))

lazy val finl = dat.map(getSem(_, 2017))

lazy val sf = finl.collect{case Some(x) => x}

val years = ls(pwd /"m-seminar").filter(_.isDir).map(_.last.toInt)

lazy val allSems = byYear.map{case (y, sems) => sems.map(getSem(_, y)).collect{case Some(x) => x}}.flatten

def saveAll(d: Path = pwd / "_auto-seminars") = allSems.foreach(_.save(d))

def findDate(ss: Vector[String]) = ss.map(getDate(_, 2017)).foldLeft[Option[(Int, Int, Int)]](None){case (x, y) => x.orElse (y)}
// saveAll()
