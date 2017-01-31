import ammonite.ops._

import scala.util.Try

def menuTail(sts: Vector[String]) = sts.dropWhile((s) => !s.contains("lnk-conus")).dropWhile((s) => !s.contains("/table")).tail

def simpleTail(sts: Vector[String]) = sts.dropWhile((s) => !s.contains("<body")).tail

def hasMenu(sts: Vector[String]) = sts.exists(_.contains("lnk-conus"))

def beheaded(sts: Vector[String]) =
  if (hasMenu(sts)) "<center>" +: menuTail(sts) else simpleTail(sts)

def rewrite(file: Path) = {
  val outlines = beheaded(read.lines(file))
  write.over(file, "---\n---\n")
  outlines.foreach((l) => write.append(file, s"$l\n"))
}

def shouldWrite(f: Path) = Try(!read.lines(f).head.startsWith("---")).getOrElse(false)

def recLS(base: Path) = {
  val first = ls(base).filter((d) => !d.name.startsWith("_"))
  first ++ (first.flatMap{
    (d) => if (d.isDir) ls.rec(d) else Vector(d)
  })
}

def beheadAll(base: Path) =
  recLS(base).filter(_.ext == "html").filter(shouldWrite).foreach((f) => Try(rewrite(f)))

def choptail(f: Path) =
  {
    val head = read.lines(f).takeWhile((l) => !l.contains ("/body"))
    rm(f)
    head.foreach((l) => write.append(f, l + "\n"))
  }

def chopAll(base : Path) =
  recLS(base).filter(_.ext == "html").foreach((f) => Try(choptail(f)))
