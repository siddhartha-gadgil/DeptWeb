val sems = ls.rec(pwd / "m-seminar").filter(_.ext == "eml" )
lazy val dat = sems.map((f) => read(f).replace("\r", "").replace("\n>", "\n").replace("<br>", "").replace("\u00a0", " ").replace("\ufffd", "")).toVector
lazy val chunks = dat.map (_.split("\n\n").toVector)
def t(s: String) = !(s.contains("Message-I")) && !(s.contains("Forwarded")) && !(s.contains("<div")) && (s.contains("Title"))
def d(s: String) = !(s.contains("Message-I")) && !(s.contains("Forwarded")) && (s.contains("Date"))
def tt(s: String) = !(s.contains("Message-I")) && !(s.contains("Forwarded")) && !(s.contains("<div")) && !(s.contains("=A0")) && !(("Title[\\s]*:").r.findFirstIn(s).isEmpty) 
def sp(s: String) = !(s.contains("Message-I")) && !(s.contains("Forwarded")) && !(s.contains("<div")) && !(s.contains("=A0")) && !(("Speaker[\\s]*:").r.findFirstIn(s).isEmpty)
