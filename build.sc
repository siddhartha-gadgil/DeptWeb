import mill._, scalalib._

object server extends ScalaModule{
  def scalaVersion = "2.12.8"

  def ivyDeps = Agg(
    ivy"com.lihaoyi::cask:0.1.9",
    ivy"com.lihaoyi::os-lib:0.2.7"
  )
}
