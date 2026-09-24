<?php
/**
 * The lecture hall booking engine, behind lecture-hall-calendar.html.
 *
 * Booking is by code rather than by sign-in: there is no login, no session and no
 * mail to send, which is what makes it work on a machine with no mail relay.
 *
 * HOW A BOOKING HAPPENS
 *   Whoever wants a hall picks the slots, says who it is for and what for, and the
 *   page hands them a booking code that spells the request out:
 *
 *       ak-lh3-21092026-1500-1600-number-theory-seminar
 *       ^  ^   ^        ^    ^    ^ what for
 *       |  |   |        |    ^ until
 *       |  |   |        ^ from
 *       |  |   ^ ddmmyyyy
 *       |  ^ hall
 *       ^ who is booking
 *
 *   They send that to the office, by any means at all -- mail, a message, read out
 *   over the phone. The code carries no authority of its own: the office unlocks
 *   the page first, and then entering the code makes the booking.
 *
 *   What unlocks it is checked HERE, on the server, and never appears in the page
 *   -- which is the whole difference between this and the version that had no
 *   backend. Reading the site's source teaches nobody how to book.
 *
 *   It changes every day, so anyone who watches it being typed has until midnight
 *   to use what they saw and nothing after that.
 *
 *   No mail is needed for any of it, which is the point of trying it this way:
 *   the OTP version cannot work until an SMTP relay exists.
 *
 * RUNNING IT
 *   ./serve.sh     then open the address it prints
 */

declare(strict_types=1);

// ------------------------------------------------------------------ config
// Outside the web root, so nothing in it is ever served. --selftest can be given
// a scratch one instead, which is the only reason this is not a constant: a test
// run must not be able to touch, or create, the real store.
$cli = PHP_SAPI === 'cli' ? ($argv ?? []) : [];
$at  = array_search('--data', $cli, true);
define('DATA_DIR', $at === false ? '/var/lib/lhcal' : (string) ($cli[$at + 1] ?? ''));
const DOMAIN      = 'iisc.ac.in';

// The office code. In the real thing this would come from
// /var/lib/lhcal/config.php, outside the repo; for a playground it sits here so
// there is one less thing to set up. It is still only ever checked on the server.
$LH_CFG = [];
if (is_readable(DATA_DIR . '/config.php')) {
    $loaded = @include DATA_DIR . '/config.php';
    if (is_array($loaded)) $LH_CFG = $loaded;
}
// No default, deliberately: this file is committed to a public repository, so a
// word written here would be a word anyone can read. Set it in
// /var/lib/lhcal/config.php (chmod 600, outside the web root):
//     <?php return ['office_word' => '...'];
// Until that exists nothing can be booked, which is the safe way to fail.
//
// What the office types is the day of the month and then this word, run together
// -- so it is different every day without anyone having to be told a new one.
define('OFFICE_WORD', (string) ($LH_CFG['office_word'] ?? ''));

// Which day it is has to be the day here, not in UTC: PHP defaults to UTC, and a
// five and a half hour disagreement would lock the office out every evening.
date_default_timezone_set($LH_CFG['timezone'] ?? 'Asia/Kolkata');

const TOKEN_TTL  = 900;   // an unlocked page goes back to being an ordinary one
const TRY_LIMIT  = 8;     // wrong answers one address may give
const TRY_KNOWN  = 60;    // ... unless the office has ever answered from it
const TRY_KNOWN_FOR = 30 * 86400;   // how long an address stays known
const TRY_WINDOW = 900;   // before it has to wait this long
const TRY_SLOW   = 250;   // milliseconds every answer costs, right or wrong
const TRY_ROWS   = 500;   // addresses remembered at once, oldest dropped first

// Ceilings. Nothing here is a limit anyone doing this by hand would ever meet;
// they are here because the file has to stay small and the work per request
// bounded even when whoever is asking is not doing it by hand.
const MAX_SLOTS    = 24;     // hours in one code
const MAX_PURPOSE  = 60;     // characters, after slugging
const MAX_AHEAD    = 500;    // days from today a booking may be made
const MAX_BEHIND   = 2;      // days into the past, so late entry still works
const KEEP_DAYS    = 400;    // how long a spent booking stays in the file
define('CALENDAR_URL', $LH_CFG['calendar_url'] ?? 'http://localhost:8001/lecture-hall-calendar.html');

const MAX_BODY      = 8192;   // no request needs more than this

const ROOMS = ['LH-1', 'LH-2', 'LH-3', 'LH-4', 'LH-5'];

// Who may be named in a code, and what that name spells out to. Generated from
// _data/faculty.yaml: first letter of the given name, first letter of the family
// name. Where two people would land on the same pair, whichever of them has the
// shorter server user-id takes that user-id as their token instead and the other
// keeps the initials -- which is why four entries below are words. Regenerate
// when someone joins or leaves.
//
// The page has its own copy of this, built by Liquid from the same file. Both are
// needed: the page uses it to offer names, and this one to refuse a code that
// names nobody. A browser can claim anything, so the server does not take the
// page's word for who exists.
const PEOPLE = [
    'aa'          => 'Arvind Ayyer',
    'ab'          => 'Abhishek Banerjee',
    'ak'          => 'Apoorva Khare',
    'am'          => 'Arka Mallick',
    'bp'          => 'Bharathwaj Palvannan',
    'en'          => 'E. K. Narayanan',
    'gb'          => 'Gautam Bharali',
    'gr'          => 'Govindan Rangarajan',
    'gv'          => 'Ganesh Vaidya',
    'hs'          => 'Harish Seshadri',
    'kv'          => 'Kaushal Verma',
    'mk'          => 'Mahesh Kakde',
    'mn'          => 'Muna Naik',
    'pg'          => 'Purvi Gupta',
    'rg'          => 'Radhika Ganapathy',
    'rv'          => 'R. Venkatesh',
    'sd'          => 'Shaunak Deo',
    'sg'          => 'Subhojoy Gupta',
    'si'          => 'Srikanth K. Iyer',
    'sk'          => 'S Nitish Kumar',
    'ss'          => 'Swarnendu Sil',
    'tb'          => 'Tirthankar Bhattacharyya',
    'tg'          => 'Thirupathi Gudi',
    'vd'          => 'Ved Datar',
    'vp'          => 'Vamsi Pritham Pingali',
    'manju'       => 'Manjunath Krishnapur',
    'gadgil'      => 'Siddhartha Gadgil',
    'soumya'      => 'Soumya Das',
    'sanchayan'   => 'Sanchayan Sen',
];

// The Aug-Dec 2026 timetable: weekday numbers (0 = Sunday), minutes from midnight.
// Generated from _data/courses.yaml.
const CLASSES = [
    ['room' => 'LH-4', 'days' => [2, 4], 'start' => 600, 'end' => 690, 'code' => 'MA 212'],
    ['room' => 'LH-4', 'days' => [1, 3, 5], 'start' => 540, 'end' => 600, 'code' => 'MA 219'],
    ['room' => 'LH-4', 'days' => [1, 3, 5], 'start' => 600, 'end' => 660, 'code' => 'MA 231'],
    ['room' => 'LH-4', 'days' => [2, 4], 'start' => 690, 'end' => 780, 'code' => 'MA 200'],
    ['room' => 'LH-1', 'days' => [2, 4], 'start' => 510, 'end' => 600, 'code' => 'MA 221'],
    ['room' => 'LH-4', 'days' => [1, 3, 5], 'start' => 660, 'end' => 720, 'code' => 'MA 261'],
    ['room' => 'LH-5', 'days' => [1, 3, 5], 'start' => 660, 'end' => 720, 'code' => 'MA 223'],
    ['room' => 'LH-5', 'days' => [1, 3, 5], 'start' => 900, 'end' => 960, 'code' => 'MA 232'],
    ['room' => 'LH-5', 'days' => [2, 4], 'start' => 600, 'end' => 690, 'code' => 'MA 242'],
    ['room' => 'LH-4', 'days' => [2, 4], 'start' => 840, 'end' => 930, 'code' => 'MA 361'],
    ['room' => 'LH-5', 'days' => [2, 4], 'start' => 840, 'end' => 930, 'code' => 'MA 312'],
    ['room' => 'LH-5', 'days' => [2, 4], 'start' => 930, 'end' => 1020, 'code' => 'MA 313'],
    ['room' => 'LH-5', 'days' => [2, 4], 'start' => 690, 'end' => 780, 'code' => 'MA 315'],
    ['room' => 'LH-5', 'days' => [1, 3, 5], 'start' => 480, 'end' => 540, 'code' => 'MA 310'],
    ['room' => 'LH-3', 'days' => [2, 4], 'start' => 945, 'end' => 1035, 'code' => 'MA 347B'],
    ['room' => 'LH-4', 'days' => [1, 3], 'start' => 810, 'end' => 900, 'code' => 'MA 333'],
    ['room' => 'LH-4', 'days' => [1, 3, 5], 'start' => 720, 'end' => 780, 'code' => 'MA 215']
];

// ------------------------------------------------------------------ plumbing
function reply(array $o): never {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    echo json_encode($o);
    exit;
}

function data_path(string ...$bits): string {
    return DATA_DIR . '/' . implode('/', $bits);
}

function ensure_dirs(): void {
    foreach ([DATA_DIR] as $d) {
        if (!is_dir($d) && !@mkdir($d, 0700, true) && !is_dir($d)) {
            reply(['ok' => false, 'error' => 'The booking store is not set up on the server.']);
        }
    }
}

// Made once, on first use. Everything signed with it -- tokens and stored codes
// alike -- stops being valid the moment the file is deleted.
function secret(): string {
    $f = data_path('secret.key');
    if (is_readable($f)) {
        $k = file_get_contents($f);
        if ($k !== false && strlen($k) >= 32) return $k;
    }
    $k = random_bytes(32);
    $tmp = $f . '.' . bin2hex(random_bytes(6));
    if (file_put_contents($tmp, $k) === false) {
        reply(['ok' => false, 'error' => 'The booking store is not writable on the server.']);
    }
    @chmod($tmp, 0600);
    rename($tmp, $f);                    // atomic, so two requests cannot race
    return $k;
}

function sign(string $msg): string {
    return hash_hmac('sha256', $msg, secret());
}

// What the office holds once it has said the day's word: a deadline and a
// signature over it, so nothing has to be remembered between requests and a
// stolen one stops working by itself. Signed with the same key as everything
// else, which lives in a file only the server can read.
function office_token(): string {
    $exp = time() + TOKEN_TTL;
    return $exp . '.' . substr(sign('office:' . $exp), 0, 32);
}

function token_ok(string $t): bool {
    $p = explode('.', $t, 2);
    if (count($p) !== 2 || !ctype_digit($p[0])) return false;
    if ((int) $p[0] < time()) return false;
    return hash_equals(substr(sign('office:' . (int) $p[0]), 0, 32), $p[1]);
}

// The word is short enough to be said out loud, which means it is short enough to
// be guessed at speed. Three things make that expensive: every answer costs a
// fixed wait whether it was right or wrong, wrong ones are counted per address,
// and the door shuts for that address once there have been too many.
//
// The count is per address and not per attempt, so a flood writes the same small
// file over and over instead of growing one, and the table itself is capped.
// Addresses are bucketed by /64 for IPv6, where a single machine is usually
// handed more addresses than it could ever need to rotate through.
//
// This has its own lock. Sharing the booking one would mean anyone could hold up
// every booking on the site simply by guessing badly, quickly.
//
// One address is treated more generously: the one the office has answered
// correctly from before. The department sits behind one address as far as the
// outside world is concerned, so without this a student on the same network
// could lock the office out of its own portal all day by guessing badly on
// purpose, which is a way of messing with it that needs no secret at all. Sixty
// wrong answers in a quarter of an hour is still nowhere near enough to find a
// word, and it is far more than anyone mistyping one will ever need.
function try_bucket(): string {
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    if ($ip === '') return 'unknown';
    $packed = @inet_pton($ip);
    if ($packed !== false && strlen($packed) === 16) return bin2hex(substr($packed, 0, 8)) . '::';
    return $ip;
}

function may_try(bool $wrong): bool {
    $lock = fopen(data_path('.trylock'), 'c');
    if ($lock === false) return false;               // cannot count, so do not allow
    flock($lock, LOCK_EX);
    try {
        $who  = try_bucket();
        $file = data_path('tries.json');
        $now  = time();

        // A row is kept while it is either still counting or still known. The
        // first clears an address that has served its wait; the second is what
        // remembers where the office works from.
        $keep = [];
        foreach (read_json($file) as $k => $row) {
            if (!is_array($row)) continue;
            if (($row['at'] ?? 0) > $now - TRY_WINDOW
                || ($row['ok'] ?? 0) > $now - TRY_KNOWN_FOR) $keep[$k] = $row;
        }
        $row = $keep[$who] ?? [];

        // The right word is never turned away. Being shut out is a thing that
        // happens to wrong answers, and a lock the office itself can be caught
        // behind is a way of attacking it rather than a defence of it.
        if (!$wrong) {
            $keep[$who] = ['n' => 0, 'at' => 0, 'ok' => $now];
            write_json($file, $keep);
            return true;
        }

        $limit = ($row['ok'] ?? 0) > $now - TRY_KNOWN_FOR ? TRY_KNOWN : TRY_LIMIT;
        $mine  = (($row['at'] ?? 0) > $now - TRY_WINDOW ? (int) ($row['n'] ?? 0) : 0) + 1;
        $keep[$who] = ['n' => $mine, 'at' => $now, 'ok' => $row['ok'] ?? 0];

        // Full table: drop whoever was heard from longest ago, never the row just
        // written, so filling it cannot clear someone else's count.
        while (count($keep) > TRY_ROWS) {
            $oldest = null;
            foreach ($keep as $k => $r) {
                if ($k !== $who && ($oldest === null || $r['at'] < $keep[$oldest]['at'])) $oldest = $k;
            }
            if ($oldest === null) break;
            unset($keep[$oldest]);
        }

        write_json($file, $keep);
        return $mine <= $limit;
    } finally { flock($lock, LOCK_UN); fclose($lock); }
}

// Read, change, write -- under a lock, so two bookings in the same second cannot
// each write a file that does not know about the other.
function with_lock(callable $fn) {
    $lock = fopen(data_path('.lock'), 'c');
    if ($lock === false) reply(['ok' => false, 'error' => 'The booking store is not writable.']);
    flock($lock, LOCK_EX);
    try { return $fn(); }
    finally { flock($lock, LOCK_UN); fclose($lock); }
}

function read_json(string $f, array $fallback = []): array {
    if (!is_readable($f)) return $fallback;
    $raw = file_get_contents($f);
    if ($raw === false || $raw === '') return $fallback;
    $v = json_decode($raw, true);
    return is_array($v) ? $v : $fallback;
}

function write_json(string $f, array $v): void {
    $tmp = $f . '.' . bin2hex(random_bytes(6));
    if (file_put_contents($tmp, json_encode($v)) === false) {
        reply(['ok' => false, 'error' => 'Could not save. Tell the office.']);
    }
    @chmod($tmp, 0600);
    rename($tmp, $f);                    // never a half-written file
}

// ------------------------------------------------------------------ times
function mins(string $hm): ?int {
    if (!preg_match('/^(\d{1,2}):(\d{2})$/', trim($hm), $m)) return null;
    $v = ((int) $m[1]) * 60 + (int) $m[2];
    return ($v >= 0 && $v <= 1440) ? $v : null;
}

// What is already in this hall at this time -- a class, or a booking.
function clash(string $room, string $date, int $s, int $e, array $bookings, string $skipId = ''): ?string {
    $wd = (int) (new DateTimeImmutable($date))->format('w');
    foreach (CLASSES as $c) {
        if ($c['room'] === $room && in_array($wd, $c['days'], true)
            && $s < $c['end'] && $e > $c['start']) {
            return $c['code'];
        }
    }
    foreach ($bookings as $b) {
        if ($b['id'] === $skipId || $b['room'] !== $room || $b['date'] !== $date) continue;
        if ($s < mins($b['end']) && $e > mins($b['start'])) {
            $what = $b['purpose'] ?: 'another booking';
            return ($b['name'] ?? '') !== '' ? $b['name'] . ' (' . $what . ')' : $what;
        }
    }
    return null;
}

// ------------------------------------------------------------------ the code
// Must stay in step with encodeRequest() in the page: one side writes these and
// the other reads them, joined only by whatever the requester passed along.
//
//     <who>-lh<hall>-<ddmmyyyy>-<hhmm>-<hhmm>[+lh<hall>-...][-purpose]
//
// Every field but the purpose is a fixed shape, so where each one stops is known
// rather than guessed. Nothing in a code is secret and nothing in it is trusted:
// it says what is wanted, and whether that may happen is settled separately.
function unslug_(string $s): string {
    return trim(preg_replace('/-+/', ' ', ltrim($s, '-')) ?? '');
}

// Pulls a code apart, or returns null if anything at all is off. Never a
// half-reading: a booking that is nearly right is worse than one that is refused.
function decode_code(string $raw): ?array {
    $s = strtolower(preg_replace('/\s+/', '', $raw) ?? '');

    // Whether the name is one we know is the caller's business: "nobody here is
    // called that" is worth saying out loud, where a malformed code is not.
    if (!preg_match('/^([a-z]{2,12})-/', $s, $m)) return null;
    $who = $m[1];
    $s = substr($s, strlen($who) + 1);

    // One or more slots, then whatever is left is the purpose.
    if (!preg_match('/^lh[1-5]-\d{8}-\d{4}-\d{4}(?:\+lh[1-5]-\d{8}-\d{4}-\d{4})*/', $s, $m)) return null;
    $slots = $m[0];
    $parts = explode('+', $slots);
    if (count($parts) > MAX_SLOTS) return null;

    // A booking has to be for a day somewhere near this one. Without this a code
    // is free to name the year 9999, and a file this small has no business
    // holding a hall for a century.
    $floor = (new DateTimeImmutable('today'))->modify('-' . MAX_BEHIND . ' days');
    $roof  = (new DateTimeImmutable('today'))->modify('+' . MAX_AHEAD . ' days');

    $runs = [];
    foreach ($parts as $part) {
        preg_match('/^lh([1-5])-(\d{2})(\d{2})(\d{4})-(\d{4})-(\d{4})$/', $part, $f);
        [, $hall, $dd, $mm, $yyyy, $from, $to] = $f;
        if (!checkdate((int) $mm, (int) $dd, (int) $yyyy)) return null;
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', "$yyyy-$mm-$dd");
        if ($day === false || $day < $floor || $day > $roof) return null;
        $st = mins(substr($from, 0, 2) . ':' . substr($from, 2));
        $en = mins(substr($to, 0, 2) . ':' . substr($to, 2));
        if ($st === null || $en === null || $en <= $st) return null;
        $runs[] = ['room' => "LH-$hall", 'date' => "$yyyy-$mm-$dd",
                   'start' => substr($from, 0, 2) . ':' . substr($from, 2),
                   'end'   => substr($to, 0, 2) . ':' . substr($to, 2)];
    }

    $rest = substr($s, strlen($slots));
    if ($rest !== '' && !preg_match('/^-[a-z0-9-]{1,' . MAX_PURPOSE . '}$/', $rest)) return null;

    return ['who' => $who, 'name' => PEOPLE[$who] ?? '', 'runs' => $runs,
            'purpose' => unslug_($rest)];
}

// Says which of a code's slots are already taken, so the office can be told
// before it acts rather than after.
function clashes_for(array $runs): array {
    $live = live_bookings();
    $out = [];
    foreach ($runs as $r) {
        $hit = clash($r['room'], $r['date'], mins($r['start']), mins($r['end']), $live);
        if ($hit !== null) $out[] = ['room' => $r['room'], 'date' => $r['date'], 'by' => $hit];
    }
    return $out;
}

// ------------------------------------------------------------------ bookings
function bookings_file(): string { return data_path('bookings.json'); }

// Every request reads this file whole, so it must not be allowed to grow for
// ever. A booking that is long past, and one that was withdrawn, are of no
// further use to anybody: they go when the file is next written. Done here
// rather than on a timer so there is nothing to install and nothing to forget.
function prune(array $all): array {
    $cut = (new DateTimeImmutable('today'))->modify('-' . KEEP_DAYS . ' days')->format('Y-m-d');
    $keep = [];
    foreach ($all as $b) {
        if (!is_array($b) || !isset($b['date'], $b['room'], $b['start'], $b['end'])) continue;
        if (($b['status'] ?? 'active') !== 'active') continue;
        if ($b['date'] < $cut) continue;
        $keep[] = $b;
    }
    return $keep;
}

function live_bookings(): array {
    return prune(read_json(bookings_file()));
}

// What the calendar is shown. The address stays in the file: this list is public.
function public_view(array $b): array {
    return [
        'id' => $b['id'], 'room' => $b['room'], 'date' => $b['date'],
        'start' => $b['start'], 'end' => $b['end'],
        'purpose' => $b['purpose'], 'bookedBy' => $b['name'],
    ];
}

// ------------------------------------------------------------------ command line
// Two things worth doing without a browser. Neither can be reached over HTTP, and
// the selftest writes only inside the scratch directory it is given.
//
//   php booking.php --decode <code>            what does this code actually say?
//   php booking.php --data <dir> --selftest    checks the two pieces of logic here
//                                              that are easy to get subtly wrong:
//                                              taking a code apart, and whether a
//                                              token is still good. Run it after
//                                              changing the format or the page's
//                                              encodeRequest(), which has to agree.
if (($i = array_search('--decode', $cli, true)) !== false) {
    echo json_encode(decode_code((string) ($cli[$i + 1] ?? '')), JSON_PRETTY_PRINT), "\n";
    exit(0);
}

if (in_array('--selftest', $cli, true)) {
    if (DATA_DIR === '/var/lib/lhcal') {
        fwrite(STDERR, "Give it a scratch directory:  php booking.php --data /tmp/lhcal-test --selftest\n");
        exit(2);
    }
    ensure_dirs();
    $fail = 0;
    $check = function (string $what, $got, $want) use (&$fail) {
        if ($got === $want) return;
        $fail++;
        fwrite(STDERR, "FAIL $what: got " . json_encode($got) . ", wanted " . json_encode($want) . "\n");
    };

    $c = decode_code('ak-lh3-24092026-1500-1600-number-theory-seminar');
    $check('who',     $c['who'],     'ak');
    $check('name',    $c['name'],    'Apoorva Khare');
    $check('purpose', $c['purpose'], 'number theory seminar');
    $check('runs',    $c['runs'],    [['room' => 'LH-3', 'date' => '2026-09-24',
                                       'start' => '15:00', 'end' => '16:00']]);

    // A word token, two slots, no purpose.
    $c = decode_code('manju-lh1-01012027-0900-1000+lh5-02012027-1100-1230');
    $check('word token', $c['name'], 'Manjunath Krishnapur');
    $check('two slots',  count($c['runs']), 2);
    $check('no purpose', $c['purpose'], '');

    // A purpose may end in digits: there is nothing after it to be confused with.
    $check('digits in purpose', decode_code('ak-lh3-24092026-1500-1600-ma-231')['purpose'], 'ma 231');

    // The ceilings. Each of these is a request nobody would type by hand, and
    // each of them used to be accepted.
    $soon = (new DateTimeImmutable('+30 days'))->format('dmY');
    $one  = fn(int $n) => 'ak-' . implode('+', array_fill(0, $n, "lh1-$soon-0900-1000")) . '-x';
    $check('slots at the limit', count(decode_code($one(MAX_SLOTS))['runs']), MAX_SLOTS);
    $check('slots over it',      decode_code($one(MAX_SLOTS + 1)), null);
    $check('purpose at limit',   strlen(decode_code("ak-lh1-$soon-0900-1000-" . str_repeat('a', MAX_PURPOSE))['purpose']), MAX_PURPOSE);
    $check('purpose over it',    decode_code("ak-lh1-$soon-0900-1000-" . str_repeat('a', MAX_PURPOSE + 1)), null);
    $check('far future',         decode_code('ak-lh1-01019999-0900-1000-x'), null);
    $check('long past',          decode_code('ak-lh1-01012001-0900-1000-x'), null);

    // Pruning: withdrawn and long past go, everything else stays.
    $old  = (new DateTimeImmutable('-' . (KEEP_DAYS + 10) . ' days'))->format('Y-m-d');
    $new_ = (new DateTimeImmutable('+10 days'))->format('Y-m-d');
    $row  = fn($d, $st) => ['id' => 'x', 'room' => 'LH-1', 'date' => $d, 'start' => '09:00',
                            'end' => '10:00', 'purpose' => 'p', 'name' => 'n', 'status' => $st];
    $check('prune', count(prune([$row($new_, 'active'), $row($old, 'active'),
                                 $row($new_, 'cancelled'), ['junk' => 1]])), 1);

    // A token outlives its deadline and nothing else.
    $check('token now',  token_ok(office_token()), true);
    $check('token past', token_ok((time() - 1) . '.' . substr(sign('office:' . (time() - 1)), 0, 32)), false);
    $check('token bent', token_ok((time() + 600) . '.' . str_repeat('0', 32)), false);
    $check('token junk', token_ok('nonsense'), false);

    foreach ([
        'ak-lh9-24092026-1500-1600-x'     => 'no such hall',
        'ak-lh3-31092026-1500-1600-x'     => 'no such date',
        'ak-lh3-24092026-1600-1500-x'     => 'ends before it starts',
        'ak-lh3-24092026-1500-1600-x.y'   => 'punctuation in the purpose',
        'lh3-24092026-1500-1600-x'        => 'nobody named',
        'ak-24092026-1500-1600-x'         => 'no hall at all',
        'seminar on friday'               => 'an ordinary search',
    ] as $bad => $why) {
        $check("refuses: $why", decode_code($bad), null);
    }

    // An unknown name parses -- it is the caller that turns it away, and with a
    // message worth reading rather than "damaged".
    $check('unknown person parses', decode_code('nobody-lh3-24092026-1500-1600-x')['name'], '');

    echo $fail ? "$fail failed\n" : "selftest ok\n";
    exit($fail ? 1 : 0);
}

// ------------------------------------------------------------------ requests
ensure_dirs();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
    reply(['ok' => true, 'bookings' => array_map('public_view', live_bookings())]);
}

$raw = file_get_contents('php://input', false, null, 0, MAX_BODY + 1);
if ($raw === false || strlen($raw) > MAX_BODY) reply(['ok' => false, 'error' => 'Bad request.']);
$in = json_decode($raw, true);
if (!is_array($in)) reply(['ok' => false, 'error' => 'Bad request.']);

$action = is_string($in['action'] ?? null) ? $in['action'] : '';

// ---- the office saying today's word ----
// The one place a secret is compared, and it is compared here rather than in the
// page because the page is public. What comes back is good for a quarter of an
// hour and for nothing else.
if ($action === 'unlock') {
    if (OFFICE_WORD === '') reply(['ok' => false, 'error' => 'Nothing is set up on the server yet.']);
    $said = strtolower(preg_replace('/\s+/', '', (string) ($in['word'] ?? '')) ?? '');
    $want = strtolower(date('d') . OFFICE_WORD);
    $right = hash_equals($want, $said);

    // Checked before the wait below, so an address that has been shut out is
    // turned away at once: made to wait, it could hold a worker open for a
    // quarter of a second at a time and that is a cheaper attack than guessing.
    if (!may_try(!$right)) reply(['ok' => false, 'error' => 'unauthorised']);

    // An answer that was allowed costs the same wait whether it was right or
    // wrong, so the two cannot be told apart by how long they took, and eight
    // guesses take two seconds rather than none.
    usleep(TRY_SLOW * 1000);
    if (!$right) reply(['ok' => false, 'error' => 'unauthorised']);
    reply(['ok' => true, 'token' => office_token()]);
}

// ---- enact it ----
if ($action === 'enact') {
    if (!token_ok((string) ($in['token'] ?? ''))) reply(['ok' => false, 'error' => 'unauthorised']);

    $c = decode_code((string) ($in['value'] ?? ''));
    if (!$c) reply(['ok' => false, 'error' => 'That code is damaged; ask for it again.']);
    if ($c['name'] === '') reply(['ok' => false, 'error' => 'No one here books as "' . $c['who'] . '".']);
    if ($c['purpose'] === '') reply(['ok' => false, 'error' => 'That code carries no purpose.']);

    $runs = $c['runs']; $purpose = $c['purpose']; $by = $c['name'];

    $out = with_lock(function () use ($runs, $purpose, $by) {
        $live = prune(read_json(bookings_file()));

        // Every slot is checked before any is written: a request for three halls
        // that collides on the third must not leave the first two booked.
        foreach ($runs as $r) {
            $hit = clash($r['room'], $r['date'], mins($r['start']), mins($r['end']), $live);
            if ($hit !== null) {
                return ['ok' => false, 'error' => $r['room'] . ' on ' . $r['date']
                        . ' is already taken by ' . $hit . '. Nothing was booked.'];
            }
        }
        // Two slots inside one code can also collide with each other.
        foreach ($runs as $i => $a) {
            foreach (array_slice($runs, $i + 1) as $b2) {
                if ($a['room'] === $b2['room'] && $a['date'] === $b2['date']
                    && mins($a['start']) < mins($b2['end']) && mins($a['end']) > mins($b2['start'])) {
                    return ['ok' => false, 'error' => 'That code overlaps itself in ' . $a['room'] . '.'];
                }
            }
        }

        $id = bin2hex(random_bytes(4));
        $now = gmdate('c');
        foreach ($runs as $r) {
            $live[] = ['id' => $id, 'room' => $r['room'], 'date' => $r['date'],
                       'start' => $r['start'], 'end' => $r['end'],
                       'purpose' => $purpose, 'name' => $by, 'email' => '',
                       'status' => 'active', 'made' => $now];
        }
        write_json(bookings_file(), $live);
        return ['ok' => true, 'id' => $id];
    });
    if (!$out['ok']) reply($out);

    reply(['ok' => true, 'id' => $out['id'], 'purpose' => $purpose, 'who' => $by,
           'slots' => $runs, 'bookings' => array_map('public_view', live_bookings())]);
}

// ---- withdraw, which the office has to be unlocked for too ----
if ($action === 'cancel') {
    if (!token_ok((string) ($in['token'] ?? ''))) reply(['ok' => false, 'error' => 'unauthorised']);
    $id = (string) ($in['id'] ?? '');
    $out = with_lock(function () use ($id) {
        $all  = prune(read_json(bookings_file()));
        $keep = array_values(array_filter($all, fn($b) => ($b['id'] ?? '') !== $id));
        if (count($keep) === count($all)) return ['ok' => false, 'error' => 'No such booking.'];
        write_json(bookings_file(), $keep);
        return ['ok' => true];
    });
    if ($out['ok']) $out['bookings'] = array_map('public_view', live_bookings());
    reply($out);
}

reply(['ok' => false, 'error' => 'Unknown action.']);
