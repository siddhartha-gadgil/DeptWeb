#!/usr/bin/env amm

import collection.JavaConverters._
import scala.util.Try
import scala.collection.mutable.{Map => mMap}

import $ivy.`org.jsoup:jsoup:1.12.1`, org.jsoup._

val memo: mMap[String, nodes.Document] = mMap()
val mcheck: mMap[String, Boolean] = mMap()

def trm(s: String) = if (s.startsWith("/DeptWeb/")) s.drop(9) else if (s.startsWith("/")) s.drop(1) else  s
def get(s: String) = memo.get(s).getOrElse{
   val result = if (s.startsWith("http")) Jsoup.connect(s).get() else Jsoup.parse(os.read(os.pwd / "_site" / os.RelPath(trm(s)))) 
   memo += s -> result
   result
}

def subLinks(s: String) = get(s).select("a").asScala.toVector.map{el => el.attr("href").trim.replace("\n", " ").replace(" ", "%20")}.flatMap(_.split("#").headOption).filter(_ != "") 
def localLinks(s: String) = subLinks(s).filterNot(l => l.startsWith("#") || l.startsWith("http"))

def contents(s: String) = if (s.startsWith("http")) requests.get(s) else os.read(os.pwd / "_site" / os.RelPath(trm(s)))
def isMissing(s: String) = {
    import java.net._
    Try{val url = new URL(s)
    val huc = url.openConnection.asInstanceOf[HttpURLConnection]
    huc.getResponseCode == HttpURLConnection.HTTP_NOT_FOUND
    }.getOrElse(false)
}
def isBroken(l: String) = 
    mcheck.get(l).getOrElse{
        // println(s"Checking $l")
        val result =
        if (l.startsWith("mailto:") || l.startsWith("https://outlook.office.com/calendar") || l.startsWith("https://calendar.google.com/calendar")) {
        // println("skipped")
        false}
        else         
        if (l.startsWith("http")) isMissing(l)
        else Try(contents(l)).isFailure
        mcheck += l -> result
        result
    } 
def brokenLinks(s: String) = subLinks(s).filter(l => isBroken(l) && isBroken(s+"/../"+l) )
def childLinks(s: String) = localLinks(s).filter(_.endsWith(".html")).filterNot(l => isBroken(l)) 
def offspring(ls: Set[String]) : Set[String] = {
      val next = ls union ls.flatMap(childLinks)
      println(s"pages in site found: ${next.size}")
      if (ls == next) ls else offspring(next)
} 

val allLocal = offspring(Set("index.html")).toVector.filterNot(s => s == "pubs.html" || s.contains("fpsac")).toIterator
def checkAll() = {    
    allLocal.foreach{
        s =>
            val links = subLinks(s)
            if (links.size < 500) {
                val br = brokenLinks(s)
                if (br.nonEmpty) {
                    println(s"* start page: $s")
                    println(s"  broken links: ${br.mkString("\n    * ","\n    * ", "\n")}")}
                    // println("")
            }
            
    }
}

// checkAll()

def testAll() = {    
    allLocal.zipWithIndex.foreach{
        case (s, n) =>
            // println(s"checking links from:$s")
            val links = subLinks(s)
            if (links.size < 500) {
                val br = brokenLinks(s)
                if (br.nonEmpty){ 
                    Console.err.println("Broken link:")
                    Console.err.println(s"* source page: $s")
                    Console.err.println(s"  broken links: ${br.mkString("\n    * ","\n    * ", "\n")}")
                    throw new Exception(s"Broken link in $s")
                    }
            }
            if (n % 50 == 0) println(s"checked links from $n pages")
            
    }
}

testAll()