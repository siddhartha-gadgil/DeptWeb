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
const UNDO_DAYS    = 30;     // ... and a withdrawn one, so a slip can be undone

// Put on the end of a booking code, it withdraws that booking instead of making
// it. Three letters rather than a word because the office types it by hand onto
// the end of something already in front of them, and nothing else a code can end
// with looks like it.
const DROP_MARK    = 'xxx';
define('CALENDAR_URL', $LH_CFG['calendar_url'] ?? 'http://localhost:8001/lecture-hall-calendar.html');

const MAX_BODY      = 8192;   // no request needs more than this

// Every room a code may name. The page has the same list. A room is written in a
// code as its name lowercased without the dash -- 'LH-1' as lh1, 'R-15' as r15 --
// so adding one here is the whole change on this side.
const ROOMS = ['LH-1', 'LH-2', 'LH-3', 'LH-4', 'LH-5', 'R-15'];

// 'lh1' => 'LH-1'. Built rather than written out, so the two cannot drift apart.
function room_tokens(): array {
    $out = [];
    foreach (ROOMS as $r) $out[strtolower(str_replace('-', '', $r))] = $r;
    return $out;
}

// Who may be named in a code, and what that name spells out to. The token is the
// server user-id, straight out of _data/faculty.yaml: it is already unique, every
// colleague knows their own, and it reads as a name rather than as a puzzle.
// Regenerate when someone joins or leaves.
//
// The page has its own copy of this, built by Liquid from the same file. Both are
// needed: the page uses it to offer names, and this one to refuse a code that
// names nobody. A browser can claim anything, so the server does not take the
// page's word for who exists.
const PEOPLE = [
    'abhi'         => 'Abhishek Banerjee',
    'arkamallick'  => 'Arka Mallick',
    'arvind'       => 'Arvind Ayyer',
    'bharali'      => 'Gautam Bharali',
    'bharathwaj'   => 'Bharathwaj Palvannan',
    'gadgil'       => 'Siddhartha Gadgil',
    'gudi'         => 'Thirupathi Gudi',
    'harish'       => 'Harish Seshadri',
    'khare'        => 'Apoorva Khare',
    'kverma'       => 'Kaushal Verma',
    'maheshkakde'  => 'Mahesh Kakde',
    'manju'        => 'Manjunath Krishnapur',
    'muna'         => 'Muna Naik',
    'naru'         => 'E. K. Narayanan',
    'nitishs'      => 'S Nitish Kumar',
    'purvigupta'   => 'Purvi Gupta',
    'radhikag'     => 'Radhika Ganapathy',
    'rangaraj'     => 'Govindan Rangarajan',
    'rvenkat'      => 'R. Venkatesh',
    'sanchayan'    => 'Sanchayan Sen',
    'shaunakdeo'   => 'Shaunak Deo',
    'skiyer'       => 'Srikanth K. Iyer',
    'soumya'       => 'Soumya Das',
    'subhojoy'     => 'Subhojoy Gupta',
    'swarnendusil' => 'Swarnendu Sil',
    'tirtha'       => 'Tirthankar Bhattacharyya',
    'vaidyaganesh' => 'Ganesh Vaidya',
    'vamsipingali' => 'Vamsi Pritham Pingali',
    'vvdatar'      => 'Ved Datar',
];

// The Aug-Dec 2026 timetable: weekday numbers (0 = Sunday), minutes from midnight.
// Generated from _data/courses.yaml.
const CLASSES = [
    ['room' => 'LH-4', 'days' => [2, 4], 'start' => 600, 'end' => 690, 'code' => 'MA 212'],
    ['room' => 'LH-4', 'days' => [1, 3, 5], 'start' => 540, 'end' => 600, 'code' => 'MA 219'],
    ['room' => 'LH-4', 'days' => [1, 3, 5], 'start' => 600, 'end' => 660, 'code' => 'MA 231'],
    ['room' => 'LH-4', 'days' => [2, 4], 'start' => 690, 'end' => 780, 'code' => 'MA 200'],
    ['room' => 'LH-4', 'days' => [2, 4], 'start' => 510, 'end' => 600, 'code' => 'MA 221'],
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
// Nothing in a code is secret and nothing in it is trusted: it says what is
// wanted, and whether that may happen is settled separately.
//
// The pieces are read by shape rather than by position, so they may be given in
// any order. Eight digits is a date, four is a clock time, a word that names a
// room is the room, a word that names a colleague is who it is for, and whatever
// is left is the purpose. That is what lets a code survive being read down a
// telephone and typed back in by someone who did not write it.
function unslug_(string $s): string {
    return trim(preg_replace('/[_\s-]+/', ' ', $s) ?? '');
}

// Pulls a code apart, or returns null if anything at all is off. Never a
// half-reading: a booking that is nearly right is worse than one that is refused.
function decode_code(string $raw): ?array {
    $s = strtolower(preg_replace('/\s+/', '', $raw) ?? '');
    if ($s === '' || !preg_match('/^[a-z0-9_+-]+$/', $s)) return null;

    // A booking has to be for a day somewhere near this one. Without this a code
    // is free to name the year 9999, and a file this small has no business
    // holding a hall for a century.
    $floor = (new DateTimeImmutable('today'))->modify('-' . MAX_BEHIND . ' days');
    $roof  = (new DateTimeImmutable('today'))->modify('+' . MAX_AHEAD . ' days');

    $rooms  = room_tokens();
    $groups = explode('+', $s);
    if (count($groups) > MAX_SLOTS) return null;

    $words = [];                       // neither a room, nor a date, nor a time
    $runs  = [];
    foreach ($groups as $group) {
        $room = null; $date = null; $times = [];
        foreach (explode('-', $group) as $tok) {
            if ($tok === '') continue;
            if (isset($rooms[$tok])) {
                if ($room !== null) return null;             // two halls, one slot
                $room = $rooms[$tok];
            } elseif (preg_match('/^\d{8}$/', $tok)) {
                if ($date !== null) return null;
                $date = $tok;
            } elseif (preg_match('/^\d{4}$/', $tok)) {
                $times[] = $tok;
            } elseif (preg_match('/^[a-z][a-z0-9_]*$/', $tok)) {
                $words[] = $tok;
            } else {
                return null;                                 // a shape we cannot read
            }
        }
        if ($room === null || $date === null || count($times) !== 2) return null;

        // Either way round: the earlier clock time is the start. A slot that ran
        // backwards would be a typo, and reading it the only way it can be meant
        // is kinder than refusing it.
        sort($times);
        [$from, $to] = $times;
        [$dd, $mm, $yyyy] = [substr($date, 0, 2), substr($date, 2, 2), substr($date, 4)];
        if (!checkdate((int) $mm, (int) $dd, (int) $yyyy)) return null;
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', "$yyyy-$mm-$dd");
        if ($day === false || $day < $floor || $day > $roof) return null;
        $st = mins(substr($from, 0, 2) . ':' . substr($from, 2));
        $en = mins(substr($to, 0, 2) . ':' . substr($to, 2));
        if ($st === null || $en === null || $en <= $st) return null;
        $runs[] = ['room' => $room, 'date' => "$yyyy-$mm-$dd",
                   'start' => substr($from, 0, 2) . ':' . substr($from, 2),
                   'end'   => substr($to, 0, 2) . ':' . substr($to, 2)];
    }

    // The first word that names somebody is who the hall is for; the rest is what
    // it is for. If no word names anybody, the first one is still handed back as
    // the name, so the caller can say "nobody here is called that" rather than
    // quietly booking it for the purpose.
    $who = '';
    $purpose = [];
    foreach ($words as $wd) {
        if ($who === '' && isset(PEOPLE[$wd])) $who = $wd;
        else $purpose[] = $wd;
    }
    if ($who === '') {
        if (!$purpose) return null;
        $who = array_shift($purpose);
    }

    $text = unslug_(implode(' ', $purpose));
    if (strlen($text) > MAX_PURPOSE) return null;
    // A purpose ending in the marker would make its own code unreadable: taking
    // the marker off would leave a code nobody was ever given.
    if (str_ends_with(str_replace(' ', '', $text), DROP_MARK)) return null;

    return ['who' => $who, 'name' => PEOPLE[$who] ?? '', 'runs' => $runs,
            'purpose' => $text];
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
// ever. A booking long past is of no further use to anybody and goes when the
// file is next written. Done here rather than on a timer so there is nothing to
// install and nothing to forget.
//
// A withdrawn one is kept for a month rather than removed. Withdrawing is one
// click and there is no undo in the page, so the row is what an undo would be
// made of: editing the file by hand is not pleasant, but it beats telling
// somebody their booking is gone and cannot be got back.
function prune(array $all): array {
    $cut  = (new DateTimeImmutable('today'))->modify('-' . KEEP_DAYS . ' days')->format('Y-m-d');
    $undo = time() - UNDO_DAYS * 86400;
    $keep = [];
    foreach ($all as $b) {
        if (!is_array($b) || !isset($b['date'], $b['room'], $b['start'], $b['end'])) continue;
        if ($b['date'] < $cut) continue;
        if (($b['status'] ?? 'active') !== 'active'
            && strtotime((string) ($b['dropped'] ?? '')) < $undo) continue;
        $keep[] = $b;
    }
    return $keep;
}

// What the calendar has in it: everything still standing.
function live_bookings(): array {
    return array_values(array_filter(prune(read_json(bookings_file())),
        fn($b) => ($b['status'] ?? 'active') === 'active'));
}

// What the calendar is shown. The address stays in the file: this list is public.
function public_view(array $b): array {
    return [
        'room' => $b['room'], 'date' => $b['date'],
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

    $c = decode_code('khare-lh3-24092026-1500-1600-number_theory_seminar');
    $check('who',     $c['who'],     'khare');
    $check('name',    $c['name'],    'Apoorva Khare');
    $check('purpose', $c['purpose'], 'number theory seminar');
    $check('runs',    $c['runs'],    [['room' => 'LH-3', 'date' => '2026-09-24',
                                       'start' => '15:00', 'end' => '16:00']]);

    // The same request said in any order at all. Every one of these is the code
    // above with its pieces shuffled, and every one has to come back the same.
    $same = $c;
    foreach ([
        'lh3-khare-24092026-1500-1600-number_theory_seminar',
        '24092026-1500-1600-lh3-number_theory_seminar-khare',
        'number_theory_seminar-1600-1500-24092026-khare-lh3',
        '1500-24092026-number_theory_seminar-lh3-khare-1600',
    ] as $i => $shuffled) {
        $check("shuffled $i", decode_code($shuffled), $same);
    }

    // Two slots, no purpose, and the second slot's pieces out of order too.
    $c = decode_code('manju-lh1-01012027-0900-1000+02012027-lh5-1230-1100');
    $check('word token', $c['name'], 'Manjunath Krishnapur');
    $check('two slots',  count($c['runs']), 2);
    $check('no purpose', $c['purpose'], '');
    $check('slot 2 read', $c['runs'][1], ['room' => 'LH-5', 'date' => '2027-01-02',
                                          'start' => '11:00', 'end' => '12:30']);

    // The Chairman's room, which is not an LH and so takes a different token.
    $check('r15',      decode_code('khare-r15-24092026-1500-1600-x')['runs'][0]['room'], 'R-15');
    $check('no r14',   decode_code('khare-r14-24092026-1500-1600-x'), null);
    $check('no lh6',   decode_code('khare-lh6-24092026-1500-1600-x'), null);

    // Underscores are what hold a purpose together now, so a hyphen inside one
    // still reads rather than throwing the whole code away.
    $check('hyphen purpose', decode_code('khare-lh3-24092026-1500-1600-oral-exam')['purpose'], 'oral exam');
    $check('digits in purpose', decode_code('khare-lh3-24092026-1500-1600-ma_231')['purpose'], 'ma 231');

    // A name nobody has comes back as itself with no name against it, so the
    // caller can say which part was wrong.
    $c = decode_code('nobody-lh3-24092026-1500-1600');
    $check('unknown who',  $c['who'],  'nobody');
    $check('unknown name', $c['name'], '');

    // Two halls or two dates in one slot is not a request anyone can act on.
    $check('two halls', decode_code('khare-lh1-lh2-24092026-1500-1600'), null);
    $check('two dates', decode_code('khare-lh1-24092026-25092026-1500-1600'), null);
    $check('one time',  decode_code('khare-lh1-24092026-1500'), null);
    $check('no hall',   decode_code('khare-24092026-1500-1600'), null);
    $check('junk token', decode_code('khare-lh1-24092026-1500-1600-12345'), null);

    // The ceilings. Each of these is a request nobody would type by hand, and
    // each of them used to be accepted.
    $soon = (new DateTimeImmutable('+30 days'))->format('dmY');
    $one  = fn(int $n) => 'khare-' . implode('+', array_fill(0, $n, "lh1-$soon-0900-1000")) . '-x';
    $check('slots at the limit', count(decode_code($one(MAX_SLOTS))['runs']), MAX_SLOTS);
    $check('slots over it',      decode_code($one(MAX_SLOTS + 1)), null);
    $check('purpose at limit',   strlen(decode_code("khare-lh1-$soon-0900-1000-" . str_repeat('a', MAX_PURPOSE))['purpose']), MAX_PURPOSE);
    $check('purpose over it',    decode_code("khare-lh1-$soon-0900-1000-" . str_repeat('a', MAX_PURPOSE + 1)), null);
    $check('far future',         decode_code('khare-lh1-01019999-0900-1000-x'), null);
    $check('long past',          decode_code('khare-lh1-01012001-0900-1000-x'), null);
    $check('drop mark',          decode_code('khare-lh1-' . $soon . '-0900-1000-vivaxxx'), null);

    // Pruning: withdrawn and long past go, everything else stays.
    $old  = (new DateTimeImmutable('-' . (KEEP_DAYS + 10) . ' days'))->format('Y-m-d');
    $new_ = (new DateTimeImmutable('+10 days'))->format('Y-m-d');
    $row  = fn($d, $st) => ['id' => 'x', 'room' => 'LH-1', 'date' => $d, 'start' => '09:00',
                            'end' => '10:00', 'purpose' => 'p', 'name' => 'n', 'status' => $st];
    $just = $row($new_, 'cancelled'); $just['dropped'] = gmdate('c');
    $stale = $row($new_, 'cancelled');   // withdrawn, but long ago enough to forget
    $stale['dropped'] = gmdate('c', time() - (UNDO_DAYS + 1) * 86400);
    $check('prune', count(prune([$row($new_, 'active'), $row($old, 'active'),
                                 $just, $stale, ['junk' => 1]])), 2);
    $check('withdrawn stays out of the calendar',
           count(array_filter(prune([$row($new_, 'active'), $just]),
                              fn($b) => ($b['status'] ?? 'active') === 'active')), 1);

    $check('purpose ending in the marker', decode_code('ak-lh1-' . $soon . '-0900-1000-fixxx'), null);

    // A token outlives its deadline and nothing else.
    $check('token now',  token_ok(office_token()), true);
    $check('token past', token_ok((time() - 1) . '.' . substr(sign('office:' . (time() - 1)), 0, 32)), false);
    $check('token bent', token_ok((time() + 600) . '.' . str_repeat('0', 32)), false);
    $check('token junk', token_ok('nonsense'), false);

    foreach ([
        'khare-lh9-24092026-1500-1600-x'   => 'no such hall',
        'khare-lh3-31092026-1500-1600-x'   => 'no such date',
        'khare-lh3-24092026-1500-1500-x'   => 'no time at all between them',
        'khare-lh3-24092026-1500-1600-x.y' => 'punctuation in the purpose',
        'khare-24092026-1500-1600-x'       => 'no hall at all',
        'lh3-24092026-1500-1600'           => 'nobody named and nothing else to go on',
        'seminar on friday'                => 'an ordinary search',
    ] as $bad => $why) {
        $check("refuses: $why", decode_code($bad), null);
    }

    // Given backwards, a slot is read the only way it can be meant rather than
    // refused. This is the one shape that used to be turned away and now is not.
    $check('backwards slot', decode_code('khare-lh3-24092026-1600-1500-x')['runs'][0],
           ['room' => 'LH-3', 'date' => '2026-09-24', 'start' => '15:00', 'end' => '16:00']);

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
        $all  = prune(read_json(bookings_file()));
        $live = array_values(array_filter($all, fn($b) => ($b['status'] ?? 'active') === 'active'));

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

// ---- withdraw, which the office has to be unlocked for too ----
// The same code that made the booking, with the marker on the end. Nothing new
// is issued for this: whoever booked has the code already, in the mail they sent
// asking for it, and replying to that mail is how they ask for it back.
//
// The code is matched against what is on the calendar rather than trusted, so a
// code that was never enacted, or was enacted and already withdrawn, withdraws
// nothing and says so.
if ($action === 'cancel') {
    if (!token_ok((string) ($in['token'] ?? ''))) reply(['ok' => false, 'error' => 'unauthorised']);

    $said = strtolower(preg_replace('/\s+/', '', (string) ($in['value'] ?? '')) ?? '');
    if (!str_ends_with($said, DROP_MARK)) reply(['ok' => false, 'error' => 'That is not a withdrawal.']);
    $c = decode_code(substr($said, 0, -strlen(DROP_MARK)));
    if (!$c || $c['name'] === '') reply(['ok' => false, 'error' => 'That code is damaged; ask for it again.']);

    $out = with_lock(function () use ($c) {
        $all = prune(read_json(bookings_file()));

        // Which booking this code made: one whose every slot, purpose and person
        // are on the calendar now. Several may share a slot over the years, so
        // the match has to be on all of it, and has to land on exactly one.
        $ids = null;
        foreach ($c['runs'] as $r) {
            $here = [];
            foreach ($all as $b) {
                if (($b['status'] ?? 'active') !== 'active') continue;
                if ($b['room'] === $r['room'] && $b['date'] === $r['date']
                    && $b['start'] === $r['start'] && $b['end'] === $r['end']
                    && $b['purpose'] === $c['purpose'] && $b['name'] === $c['name']) {
                    $here[$b['id']] = true;
                }
            }
            $ids = $ids === null ? $here : array_intersect_key($ids, $here);
            if (!$ids) break;
        }
        if (!$ids) return ['ok' => false, 'error' => 'Nothing on the calendar matches that code.'];
        if (count($ids) > 1) return ['ok' => false, 'error' => 'More than one booking matches that code.'];

        $id = array_key_first($ids);
        $now = gmdate('c');
        $gone = [];
        foreach ($all as &$b) {
            if (($b['id'] ?? '') !== $id || ($b['status'] ?? 'active') !== 'active') continue;
            $b['status'] = 'cancelled';
            $b['dropped'] = $now;
            $gone[] = $b;
        }
        unset($b);
        write_json(bookings_file(), $all);
        return ['ok' => true, 'purpose' => $gone[0]['purpose'], 'who' => $gone[0]['name'],
                'slots' => array_map(fn($b) => ['room' => $b['room'], 'date' => $b['date'],
                                                'start' => $b['start'], 'end' => $b['end']], $gone)];
    });
    if ($out['ok']) $out['bookings'] = array_map('public_view', live_bookings());
    reply($out);
}

reply(['ok' => false, 'error' => 'Unknown action.']);
