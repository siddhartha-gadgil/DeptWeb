package server

object Server extends cask.MainRoutes{
  @cask.get("/")
  def hello() = {
    "Hello World"
  }

  val tarfile = os.pwd / "tarball" / "dept.tgz"

  def mkTar() = os.proc("bash", "mktar.sh").call()

  @cask.get("/dept.tgz")
  def tarBall(request: cask.Request) = {
    mkTar()
    os.read(tarfile)
  }

  initialize()
}
