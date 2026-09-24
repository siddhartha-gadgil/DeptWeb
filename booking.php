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
 *   over the phone. The office pastes it with the office code on the end after a
 *   dot, and the booking is made. Nothing else is needed from the requester.
 *
 *   The office code is checked HERE, on the server, and never appears in the page
 *   -- which is the whole difference between this and the version that had no
 *   backend. Reading the site's source teaches nobody how to book.
 *
 *   No mail is needed for any of it, which is the point of trying it this way:
 *   the OTP version cannot work until an SMTP relay exists.
 *
 * RUNNING IT
 *   ./serve.sh     then open the address it prints
 */

declare(strict_types=1);

// ------------------------------------------------------------------ config
const DATA_DIR    = '/var/lib/lhcal';
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
// code written here would be a code anyone can read. Set it in
// /var/lib/lhcal/config.php (chmod 600, outside the web root):
//     <?php return ['office_code' => '...'];
// Until that exists nothing can be booked, which is the safe way to fail.
define('OFFICE_CODE', (string) ($LH_CFG['office_code'] ?? ''));
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
    header('Cache-Control: no-store');
    echo json_encode($o);
    exit;
}

function data_path(string ...$bits): string {
    return DATA_DIR . '/' . implode('/', $bits);
}

function ensure_dirs(): void {
    foreach ([DATA_DIR, data_path('otp')] as $d) {
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

function valid_date(string $d): bool {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m)) return false;
    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
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
//     <who>-lh<hall>-<ddmmyyyy>-<hhmm>-<hhmm>[+lh<hall>-...][-purpose][.<office code>]
//
// Every field but the purpose is a fixed shape, so where each one stops is known
// rather than guessed. The office code is the one part the requester never sees;
// it is fenced off behind a dot, which no slug can contain, so a purpose ending
// in digits cannot be mistaken for it and a code with no dot is simply a request
// that has not been authorised yet.
function unslug_(string $s): string {
    return trim(preg_replace('/-+/', ' ', ltrim($s, '-')) ?? '');
}

// Pulls a code apart, or returns null if anything at all is off. Never a
// half-reading: a booking that is nearly right is worse than one that is refused.
// The office code is returned as it was written, not compared here -- the caller
// decides what a wrong one means, and only enact() cares.
function decode_code(string $raw): ?array {
    $s = strtolower(preg_replace('/\s+/', '', $raw) ?? '');

    // The office code first, so the rest is exactly what the requester was given.
    $office = '';
    $dot = strrpos($s, '.');
    if ($dot !== false) {
        $office = substr($s, $dot + 1);
        $s = substr($s, 0, $dot);
        if (!preg_match('/^[a-z0-9]+$/', $office)) return null;
    }

    // Whether the name is one we know is the caller's business: "nobody here is
    // called that" is worth saying out loud, where a malformed code is not.
    if (!preg_match('/^([a-z]{2,12})-/', $s, $m)) return null;
    $who = $m[1];
    $s = substr($s, strlen($who) + 1);

    // One or more slots, then whatever is left is the purpose.
    if (!preg_match('/^lh[1-5]-\d{8}-\d{4}-\d{4}(?:\+lh[1-5]-\d{8}-\d{4}-\d{4})*/', $s, $m)) return null;
    $slots = $m[0];
    $runs  = [];
    foreach (explode('+', $slots) as $part) {
        preg_match('/^lh([1-5])-(\d{2})(\d{2})(\d{4})-(\d{4})-(\d{4})$/', $part, $f);
        [, $hall, $dd, $mm, $yyyy, $from, $to] = $f;
        if (!checkdate((int) $mm, (int) $dd, (int) $yyyy)) return null;
        $st = mins(substr($from, 0, 2) . ':' . substr($from, 2));
        $en = mins(substr($to, 0, 2) . ':' . substr($to, 2));
        if ($st === null || $en === null || $en <= $st) return null;
        $runs[] = ['room' => "LH-$hall", 'date' => "$yyyy-$mm-$dd",
                   'start' => substr($from, 0, 2) . ':' . substr($from, 2),
                   'end'   => substr($to, 0, 2) . ':' . substr($to, 2)];
    }

    $rest = substr($s, strlen($slots));
    if ($rest !== '' && !preg_match('/^-[a-z0-9-]+$/', $rest)) return null;

    return ['who' => $who, 'name' => PEOPLE[$who] ?? '', 'runs' => $runs,
            'purpose' => unslug_($rest), 'office' => $office];
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

function live_bookings(): array {
    return array_values(array_filter(read_json(bookings_file()),
        fn($b) => is_array($b) && ($b['status'] ?? 'active') === 'active'));
}

// What the calendar is shown. The address stays in the file: this list is public.
function public_view(array $b): array {
    return [
        'id' => $b['id'], 'room' => $b['room'], 'date' => $b['date'],
        'start' => $b['start'], 'end' => $b['end'],
        'purpose' => $b['purpose'], 'bookedBy' => $b['name'],
        'owner' => substr(sign('owner:' . $b['email']), 0, 12),
    ];
}

// ------------------------------------------------------------------ command line
// Two things worth doing without a browser. Both touch no files and neither can
// be reached over HTTP.
//
//   php booking.php --decode <code>    what does this code actually say?
//   php booking.php --selftest         checks the one piece of logic here that is
//                                      easy to get subtly wrong: taking a code
//                                      apart. Run it after changing the format or
//                                      the page's encodeRequest(), which has to
//                                      agree with it.
$argv = $argv ?? [];
if (PHP_SAPI === 'cli' && ($i = array_search('--decode', $argv, true)) !== false) {
    echo json_encode(decode_code((string) ($argv[$i + 1] ?? '')), JSON_PRETTY_PRINT), "\n";
    exit(0);
}

if (PHP_SAPI === 'cli' && in_array('--selftest', $argv, true)) {
    $fail = 0;
    $check = function (string $what, $got, $want) use (&$fail) {
        if ($got === $want) return;
        $fail++;
        fwrite(STDERR, "FAIL $what: got " . json_encode($got) . ", wanted " . json_encode($want) . "\n");
    };

    $c = decode_code('ak-lh3-24092026-1500-1600-number-theory-seminar.1234');
    $check('who',     $c['who'],     'ak');
    $check('name',    $c['name'],    'Apoorva Khare');
    $check('purpose', $c['purpose'], 'number theory seminar');
    $check('office',  $c['office'],  '1234');
    $check('runs',    $c['runs'],    [['room' => 'LH-3', 'date' => '2026-09-24',
                                       'start' => '15:00', 'end' => '16:00']]);

    // A word token, two slots, no purpose, no office code.
    $c = decode_code('manju-lh1-01012027-0900-1000+lh5-02012027-1100-1230');
    $check('word token', $c['name'], 'Manjunath Krishnapur');
    $check('two slots',  count($c['runs']), 2);
    $check('no purpose', $c['purpose'], '');
    $check('no office',  $c['office'], '');

    // A purpose ending in digits is not mistaken for an office code: that was the
    // whole reason for fencing the office code off behind a dot.
    $c = decode_code('ak-lh3-24092026-1500-1600-ma-231');
    $check('digits in purpose', $c['purpose'], 'ma 231');
    $check('no office either',  $c['office'],  '');

    foreach ([
        'ak-lh9-24092026-1500-1600-x'     => 'no such hall',
        'ak-lh3-31092026-1500-1600-x'     => 'no such date',
        'ak-lh3-24092026-1600-1500-x'     => 'ends before it starts',
        'ak-lh3-24092026-1500-1600-x.a-b' => 'punctuation in the office code',
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

// ---- what does this code say? (no office code needed, so anyone can check) ----
if ($action === 'read') {
    $c = decode_code((string) ($in['value'] ?? ''));
    if (!$c) reply(['ok' => false, 'error' => 'That code is damaged; ask for it again.']);
    if ($c['name'] === '') reply(['ok' => false, 'error' => 'No one here books as "' . $c['who'] . '".']);

    reply(['ok' => true,
           'authorised' => OFFICE_CODE !== '' && $c['office'] !== ''
                           && hash_equals(OFFICE_CODE, $c['office']),
           'who' => $c['name'], 'purpose' => $c['purpose'],
           'slots' => $c['runs'], 'clashes' => clashes_for($c['runs'])]);
}

// ---- enact it ----
if ($action === 'enact') {
    $c = decode_code((string) ($in['value'] ?? ''));
    if (!$c) reply(['ok' => false, 'error' => 'That code is damaged; ask for it again.']);
    if ($c['name'] === '') reply(['ok' => false, 'error' => 'No one here books as "' . $c['who'] . '".']);

    if (OFFICE_CODE === '') reply(['ok' => false, 'error' => 'No office code is set up on the server.']);

    // Deliberately vague: whether the code was absent or merely wrong is not
    // something worth telling whoever is trying.
    if ($c['office'] === '' || !hash_equals(OFFICE_CODE, $c['office'])) {
        reply(['ok' => false, 'error' => 'unauthorised']);
    }
    if ($c['purpose'] === '') reply(['ok' => false, 'error' => 'That code carries no purpose.']);

    $runs = $c['runs']; $purpose = $c['purpose']; $by = $c['name'];

    $out = with_lock(function () use ($runs, $purpose, $by) {
        $all  = read_json(bookings_file());
        $live = array_values(array_filter($all, fn($b) => is_array($b) && ($b['status'] ?? 'active') === 'active'));

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
            $all[] = ['id' => $id, 'room' => $r['room'], 'date' => $r['date'],
                      'start' => $r['start'], 'end' => $r['end'],
                      'purpose' => $purpose, 'name' => $by, 'email' => '',
                      'status' => 'active', 'made' => $now];
        }
        write_json(bookings_file(), $all);
        return ['ok' => true, 'id' => $id];
    });
    if (!$out['ok']) reply($out);

    reply(['ok' => true, 'id' => $out['id'], 'purpose' => $purpose, 'who' => $by,
           'slots' => $runs, 'bookings' => array_map('public_view', live_bookings())]);
}

// ---- withdraw, which also needs the office code ----
if ($action === 'cancel') {
    if (OFFICE_CODE === '' || !hash_equals(OFFICE_CODE, (string) ($in['code'] ?? ''))) {
        reply(['ok' => false, 'error' => 'unauthorised']);
    }
    $id = (string) ($in['id'] ?? '');
    $out = with_lock(function () use ($id) {
        $all = read_json(bookings_file());
        $found = false;
        foreach ($all as &$b) {
            if (!is_array($b) || ($b['id'] ?? '') !== $id) continue;
            $b['status'] = 'cancelled';
            $found = true;
        }
        unset($b);
        if (!$found) return ['ok' => false, 'error' => 'No such booking.'];
        write_json(bookings_file(), $all);
        return ['ok' => true];
    });
    if ($out['ok']) $out['bookings'] = array_map('public_view', live_bookings());
    reply($out);
}

reply(['ok' => false, 'error' => 'Unknown action.']);
