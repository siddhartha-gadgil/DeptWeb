import ammonite.ops._, ImplicitWd._

val semlist = ls.rec(pwd / "m-seminar").filter(_.ext == "eml" )

def files(d: Path) = ls.rec(d).filter(_.ext == "eml")
def contents(f: Path) = read(f).replace("\r", "").replace("\n>", "\n").replace("<br>", "").replace("\u00a0", " ").replace("\ufffd", "")
lazy val byYear = years.map((y) => (y, files(pwd / "m-seminar" / y.toString).map((f) => contents(f))))

lazy val dat = semlist.map((f) => read(f).replace("\r", "").replace("\n>", "\n").replace("<br>", "").replace("\u00a0", " ").replace("\ufffd", "")).toVector

val bl = "\\n[ \\t]*\\n".r
lazy val chunks = dat.map (bl.split(_).toVector)
def t(s: String) = !(s.contains("Message-I")) && !(s.contains("Forwarded")) && !(s.contains("<div")) && (s.contains("Title"))
def d(s: String) =
  !(s.contains("Message-I")) &&
  !(s.contains("Forwarded")) &&
  !(s.contains("From:")) &&
  (s.contains("Date") || s.contains("DATE"))
def tt(s: String) = !(s.contains("Message-I")) && !(s.contains("Forwarded")) && !(s.contains("<div")) && !(s.contains("=A0")) && !(("(Title|TITLE)[\\s]*:").r.findFirstIn(s).isEmpty)
def sp(s: String) = !(s.contains("Message-I")) && !(s.contains("Forwarded")) && !(s.contains("<div")) && !(s.contains("=A0")) && !(("Speaker[\\s]*:").r.findFirstIn(s).isEmpty)

def data(f: Path) =
  read(f).
  replace("\r", "").
  replace("\n>", "\n").
  replace("<br>", "").replace("\u00a0", " ").replace("\ufffd", "")
def chunk(s: String) = bl.split(s).toVector
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

val day = ((digit ~ digit | digit).!)~(("st" | "th" | "nd" | "rd").?)
val year = (P("20")~digit~digit).!
val mdy = nc.rep ~ ":" ~  punc ~ word ~ punc ~ day ~ punc ~ year
val dmy = nc.rep ~ ":" ~  punc ~ day ~ punc ~ word ~ punc ~ year
val md = nc.rep ~ ":" ~  punc ~ word ~ punc ~ day
val dm = nc.rep ~ ":" ~  punc ~ day ~ punc ~ word
val wmdy = nc.rep ~ ":" ~ punc ~ wrd ~  punc ~ word ~ punc ~ day ~ punc ~ year
val wmd = nc.rep ~ ":" ~  punc ~ wrd ~ punc   ~ word ~ punc ~ day
val wdm = nc.rep ~ ":" ~  punc ~ wrd ~ punc ~ day ~ punc ~ word
val wdmy = nc.rep ~ ":" ~  punc ~ wrd ~ punc ~ day ~ punc ~ word ~ punc ~ year

val sl = day ~ ("/"| "-" | ".") ~ day ~ ("/"| "-" | ".") ~ (year | day.map("20" + _))
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
    bl.split(s).map(kv(k, _)).fold[Option[String]](None){case (x, y) => x.orElse(y)}

def getAbs(s: String) = {
  val ch = bl.split(s)
  val abs = kvc("Abstract", s).map(trim(_))
  if (abs == Some("")) ch.zip(ch.tail).find((x) => x._1.contains("Abstract") || x._1.contains("ABSTRACT")).map(_._2)
  else abs
}

val monthMap = Vector("jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec").zipWithIndex.toMap

def month(m: String) = monthMap(m.toLowerCase.take(3)) + 1

val mdyParse = (wmdy | mdy).map {case (m, d, y) => (d.toInt, month(m), y.toInt)}
val dmyParse = (wdmy | dmy).map {case (d, m, y) => (d.toInt, month(m), y.toInt)}
val ssParse  = (ssl | wsl).map{case (d, m, y) => (d.toInt, m.toInt, y.toInt)}

val mdParse = (wmd | md).map {case (m, d) => (d.toInt, month(m))}
val dmParse = (wdm | dm).map {case (d, m) => (d.toInt, month(m))}

val withYear = mdyParse | dmyParse | ssParse
def useYear(y: Int) = (dmParse | mdParse).map{case (d, m) => (d, m, y)}


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
  val venueSt = venueOpt.map((v) => s"""venue: "${trim(v)}"""").getOrElse("")
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
    (for {
      dt <- date(s)
      d <- getDate(dt, y)
      sp <- kvc("Speaker", s).orElse(kvc("SPEAKER", s))
      t <- kvc("Title", s).orElse(kvc("Topic", s)).orElse(kvc("TITLE", s))
    } yield Seminar(
      d, trim(sp), trim(t), kvc("Venue", s).orElse(kvc("Location", s)).map(mline),
      kvc("Time", s), getAbs(s))).orElse(imiSem(s))

lazy val finl = dat.map(getSem(_, 2017))

lazy val sf = finl.collect{case Some(x) => x}

val years = ls(pwd /"m-seminar").filter(_.isDir).map(_.last.toInt)

lazy val allSems = byYear.map{case (y, sems) => sems.map(getSem(_, y)).collect{case Some(x) => x}}.flatten

lazy val failedSems = byYear.map{case (y, sems) => (y, sems.filter(getSem(_, y) == None))}

def saveAll(d: Path = pwd / "_auto-seminars") = allSems.foreach(_.save(d))

def findDate(ss: Vector[String]) = ss.map(getDate(_, 2017)).foldLeft[Option[(Int, Int, Int)]](None){case (x, y) => x.orElse (y)}

def writeFailed =
  for {
  (y, ss) <- failedSems
  ssz = ss.zipWithIndex
  (c, n) <- ssz
  file = pwd / "unparsed-seminars" / y.toString / n.toString
} write.over(file, c)

def purgeParsed(f: Path) = if (!getSem(contents(f), 2017).isEmpty) rm(f)
// saveAll()

def prepLines(s: String) = {
   val ch = chunk(s).map(trim(_))
   ch.map((s) => trim(s).replace(" ", "")) .zip(ch.tail).filter((p) => Set("by", "at", "on").contains(p._1))
}

val imiYp = (wspc ~ word ~ punc ~ day   ~ punc ~ year | wspc ~ wrd ~ punc ~ word ~ punc ~ day ~ punc ~ year)

def imiYear(s: String) = imiYp.parse(s).fold((_, _, _) => None, (x, _) => Some(x)).map{case (m, d, y) => (d.toInt, month(m), y.toInt)}


def imiSem(s: String) : Option[Seminar] = {
     val pl = prepLines(s)
     val sp = pl.find(_._1 == "by").map(_._2)
     val dt = pl.filter(_._1 == "on").map((p) => imiYear(p._2)).collect{case Some(x) => x}.headOption
     val tit = pl.filter(_._1 == "on").filter((p) => imiYear(p._2).isEmpty).headOption
     val ch = chunk(s)
     val absOpt = ch.zip(ch.tail).find(_._1.toLowerCase.contains("abstract")).map(_._2)
     val atOpt = pl.find(_._1 == "at").map((s) => s._2.trim)
     val timeOpt = atOpt.map(_.split("[, ]").head)
     val venueOpt = atOpt.map(_.split(",").tail.mkString(","))
     for {d <- dt; s <- sp ; t <- tit} yield Seminar(d, trim(s), trim(t._2), timeOpt, venueOpt, absOpt)
  }


val ymr = (year ~ "-"~ (digit.!) ~ "-"~ (AnyChar.rep.!)).map{case (y, m, rest) => s"$y-0$m-$rest"}

def newName(f: Path) = ymr.parse(f.last).fold((_, _, _) => None, (x, _) => Some(x))

def newPath(f: Path) = newName(f).map(f.up / _)

def addMonthZero(f: Path) = newPath(f).foreach((g) => mv(f, g))
