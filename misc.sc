def md(f: Path) = {
  val targ = pwd / (f.name.dropRight(5)+".md")
  val raw = %%("html2text", "-style", "pretty", f.name)
  val out = raw.out.lines map (_.replace("- title", "-\ntitle").replace(" code", "\ncode").replace(" ---", "\n---"))
  out.foreach((l) => write.append(targ, l + "\n"))
  }
