
package ammonite
package $file
import _root_.ammonite.interp.api.InterpBridge.{
  value => interp
}
import _root_.ammonite.interp.api.InterpBridge.value.{
  exit
}
import _root_.ammonite.interp.api.IvyConstructor.{
  ArtifactIdExt,
  GroupIdExt
}
import _root_.ammonite.runtime.tools.{
  browse,
  grep,
  time,
  tail
}
import _root_.ammonite.repl.tools.{
  desugar,
  source
}
import _root_.ammonite.main.Router.{
  doc,
  main
}
import _root_.ammonite.repl.tools.Util.{
  pathScoptRead
}


object build{
/*<script>*/import mill._, scalalib._

object server extends ScalaModule{
  def scalaVersion = "2.13.0"

  def ivyDeps = Agg(
    ivy"com.lihaoyi::cask:0.1.9",
    ivy"com.lihaoyi::os-lib:0.2.7"
  )
}
/*</script>*/ /*<generated>*/
def $main() = { scala.Iterator[String]() }
  override def toString = "build"
  /*</generated>*/
}
