import ammonite.ops._
implicit val wd = pwd

def md(f: Path, dir: Path = pwd) = {
  val targ = dir / (f.name.dropRight(5)+".md")
  val raw = %%("html2text", "-style", "pretty", f)
  val out = raw.out.lines map (_.replace("- title", "-\ntitle").replace(" code", "\ncode").replace(" ---", "\n---"))
  out.foreach((l) => write.append(targ, l + "\n"))
  }
