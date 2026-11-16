// Holds back a seminar whose hall is already taken:  node _scripts/seminar-venue-check.js FILE...
//
// Run by .github/workflows/seminar-venue.yml on every push that touches _seminars.
// A seminar in a lecture hall that clashes with a booking, a class or another
// seminar gets NOTE as the first line of its front matter and `published: false`,
// so Jekyll leaves it out. Once the venue or time is fixed the next push re-checks
// it and takes both lines out again.
//
// The hall and the hours are read by the calendar page's own code (the <test>
// block), so this and lecture-hall-calendar.html cannot disagree about a seminar.
var fs = require("fs"), path = require("path");
var root = path.join(__dirname, "..");
var NOTE = "# The Venue you wanted has already been booked. Please visit " +
           "https://math.iisc.ac.in/lecture-hall-calendar.html and change venue accordingly.";
var BOOKINGS = "https://math.iisc.ac.in/booking.php";

var page = fs.readFileSync(path.join(root, "lecture-hall-calendar.html"), "utf8");
eval(page.match(/\/\/ <test>([\s\S]*?)\/\/ <\/test>/)[1]);
var SEMINAR_MINS = +page.match(/var SEMINAR_MINS = (\d+)/)[1];
var LONG_SEMINAR_MINS = +page.match(/var LONG_SEMINAR_MINS = (\d+)/)[1];

// The timetable as booking.php has it, and the date it stops at.
var php = fs.readFileSync(path.join(root, "booking.php"), "utf8");
var CLASSES_UNTIL = php.match(/const CLASSES_UNTIL = '([\d-]+)'/)[1];
var CLASSES = [];
php.replace(/'room' => '([^']+)', 'days' => \[([\d, ]+)\], 'start' => (\d+), 'end' => (\d+), 'code' => '([^']+)'/g,
  function (_, room, days, start, end, code) {
    CLASSES.push({ room: room, days: days.split(/\s*,\s*/).map(Number), start: +start, end: +end, what: code });
  });

// Just the flat `key: value` lines the calendar reads; nothing nested is needed.
function frontMatter(file) {
  var text = fs.readFileSync(file, "utf8"), fm = {};
  var m = text.match(/^---\r?\n([\s\S]*?)\r?\n---/);
  if (!m) return null;
  m[1].split(/\r?\n/).forEach(function (line) {
    var kv = line.match(/^([\w-]+):\s*(.*?)\s*$/);
    if (kv) fm[kv[1]] = kv[2].replace(/^(["'])([\s\S]*)\1$/, "$2");
  });
  fm.date = path.basename(file).slice(0, 10);   // the page dates seminars by filename too
  return { text: text, fm: fm };
}

function slotOf(fm) { return toSeminars([fm], SEMINAR_MINS, LONG_SEMINAR_MINS)[0]; }

function allSeminars(dir) {
  return fs.readdirSync(dir, { withFileTypes: true }).reduce(function (acc, e) {
    var p = path.join(dir, e.name);
    if (e.isDirectory()) return acc.concat(allSeminars(p));
    return /\.md$/.test(e.name) ? acc.concat(p) : acc;
  }, []);
}

function clash(s, file, bookings, others) {
  var wd = fromYmd(s.date).getDay();
  var hit = function (b) { return b.room === s.room && s.start < b.end && s.end > b.start; };
  var c = s.date <= CLASSES_UNTIL && CLASSES.filter(function (c) { return c.days.indexOf(wd) >= 0; }).find(hit);
  return c ||
    bookings.filter(function (b) { return b.date === s.date; }).find(hit) ||
    others.filter(function (o) { return o.file !== file && o.date === s.date; }).find(hit);
}

function setNote(file, text, on) {
  var bare = text.replace(NOTE + "\npublished: false\n", "");
  var next = on ? bare.replace(/^---\r?\n/, "$&" + NOTE + "\npublished: false\n") : bare;
  if (next !== text) fs.writeFileSync(file, next);
}

async function main(files) {
  var today = ymd(new Date());
  var res = await fetch(BOOKINGS);                // fail the run rather than pass a clash unseen
  if (!res.ok) throw new Error(BOOKINGS + " answered " + res.status);
  var bookings = (await res.json()).bookings.map(function (b) {
    return { room: b.room, date: b.date, start: minutesOf(b.start), end: minutesOf(b.end), what: b.purpose };
  });

  var others = allSeminars(path.join(root, "_seminars")).reduce(function (acc, f) {
    var r = frontMatter(f);
    if (!r || r.fm.published === "false" || r.fm.date < today) return acc;
    var s = slotOf(r.fm);
    if (s) acc.push({ file: path.resolve(f), room: s.room, date: s.date, start: s.start, end: s.end, what: r.fm.speaker });
    return acc;
  }, []);

  files.forEach(function (f) {
    if (!fs.existsSync(f) || !/\.md$/.test(f)) return;
    var r = frontMatter(f);
    if (!r) return;
    var s = slotOf(r.fm);
    var hit = s && s.date >= today && clash(s, path.resolve(f), bookings, others);
    if (hit) console.log(f + ": " + s.room + " is taken (" + hit.what + ")");
    setNote(f, r.text, !!hit);
  });
}

main(process.argv.slice(2)).catch(function (e) { console.error(e.message); process.exit(1); });
