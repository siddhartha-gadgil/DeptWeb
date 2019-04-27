package server

import scala.util.Try

object ServerRoutes extends cask.Routes{
  @cask.get("/")
  def hello() = {
    "Hello World"
  }

  val tarfile = os.pwd / "tarball" / "dept.tgz"

  def mkTar() = os.proc("bash", "mktar.sh").call()

  @cask.post("/dept.tgz")
  def tarBall(request: cask.Request) = {
    mkTar()
    os.read(tarfile)
  }

  initialize()
}

object Server extends cask.Main(ServerRoutes){
  override def port = 6060
  override def host = Try(sys.env("IP")).getOrElse("localhost")
}
