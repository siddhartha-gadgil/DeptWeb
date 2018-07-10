import ammonite.ops._
val sheet = read.lines(pwd / "course-prerequisites.tsv")
val prereqs = sheet.map(_.split("\t").toVector).filter((v) => v.size > 1 && v.head.startsWith("MA"))
import $ivy.`net.jcazevedo::moultingyaml:0.4.0`
import net.jcazevedo.moultingyaml._
import net.jcazevedo.moultingyaml.DefaultYamlProtocol._
import ammonite.ops._
val txt = read(pwd / "_data" / "courses.yaml").replace("\t", " ")
val yamlAst = txt.parseYaml
val data = yamlAst.convertTo[Map[String, Map[String, List[Map[String, String]]]]]

def preug(v: Vector[String]) = if (v.size > 3) v(3) else ""
def precors(v: Vector[String]) = if (v.size > 2) v(2) else ""
def addPrereqs(m: Map[String, String]) = {
  val row = prereqs.find(_.head == m("code")).get
  m + ("prereq-courses" -> precors(row)) + ("ug-prereq-courses" -> preug(row))
  }

val withPrereqs =
  data.map{case (sem, semdata) =>
     sem -> semdata.map{case (group, groupData) =>
         group -> groupData.map((course) => addPrereqs(course))
       }
     }
val yaml = withPrereqs toYaml

def sch()= {write.over(pwd / "_data" / "courses.yaml", yaml.prettyPrint )}

def addHead(name: Path) = {
    val code = "MA " + name.name.slice(2, 5)
    val pieces = read(name).split("---").toVector
    val row = prereqs.find(_.head == code).get
    val oldHead =
      pieces(1).split("\n").filterNot((x) => x.startsWith("prereq-courses") || x.startsWith("ug-prereq-courses")).mkString("\n")
    val newHead = oldHead + s"prereq-courses: ${precors(row)}\n" + s"ug-prereq-courses: ${preug(row)}"
    "---\n"+newHead+"\n---\n"+pieces(2)
  }

import scala.util.Try

def allHeads() =
    ls(pwd / "_all-courses").foreach((f) => Try(write.over(f, addHead(f))))
