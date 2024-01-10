#!/usr/bin/env amm

import ammonite.ops._

val pubFile = pwd / "_data" / "publications.bib"

def entries(s: String) =
  s.split("@").tail.map{x => "@"+x}.toVector

val mrReg = "MR[0-9]+".r

def mrNum(s: String) = mrReg.findFirstIn(s).get.drop(2).toInt

def entryMap(s: String) =
  entries(s).map(x => mrNum(x) -> x).toMap

def view(m: Map[Int, String]) =
  m.toVector.sortBy{case (n, _) => -n}.map(_._2).mkString("")

var emap = entryMap(read.lines(pubFile).mkString("\n"))

def update(f: Path = pwd / "tmp" / "newpubs.bib") = {
  val m = entryMap(read.lines(f).mkString("\n"))
  emap = m ++ emap
}

import $file.bib2yaml

def save() = {
  write.over(pubFile, view(emap))
  bib2yaml.run
}

update()
save()

