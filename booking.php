<?php
/**
 * PLAYGROUND -- booking by code, not by one-time password.
 *
 * A copy of the lecture-hall engine with the whole email/OTP front door taken out
 * and replaced by a code. Nothing here is deployed; temp/ is ignored by git and
 * excluded from the Jekyll build, so this can be broken freely.
 *
 * HOW A BOOKING HAPPENS
 *   Whoever wants a hall picks the slots, types a purpose, and the page hands them
 *   a booking code that spells out the request:
 *
 *       1 21092026 1500 to 1600 -number-theory-seminar
 *       ^ hall     ^ ddmmyyyy    ^ what for
 *                    ^ from ^ to
 *
 *   They send that to the office. The office pastes it with the office code stuck
 *   on the end, and the booking is made.
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
        if ($s < mins($b['end']) && $e > mins($b['start'])) return $b['purpose'] ?: 'another booking';
    }
    return null;
}

// ------------------------------------------------------------------ the code
// Must stay byte-for-byte compatible with encodeRequest() in the page: one side
// writes these and the other reads them, joined only by whatever the requester
// pasted into an email.
//
//     <hall><ddmmyyyy><hhmm>to<hhmm>[+...][-purpose]
//
function slug_(string $s): string {
    $t = strtolower($s);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t) ?? '';
    $t = trim($t, '-');
    return rtrim(substr($t, 0, 40), '-');
}

function unslug_(string $s): string {
    return trim(preg_replace('/-+/', ' ', ltrim($s, '-')) ?? '');
}

// Returns the slots, or null if anything at all is off. Never a half-reading: a
// booking that is nearly right is worse than one that is refused.
function decode_slots(string $raw): ?array {
    $s = strtolower(preg_replace('/\s+/', '', $raw) ?? '');
    if ($s === '') return null;
    $runs = [];
    foreach (explode('+', $s) as $part) {
        if (!preg_match('/^([1-5])(\d{2})(\d{2})(\d{4})(\d{4})to(\d{4})$/', $part, $m)) return null;
        [, $hall, $dd, $mm, $yyyy, $from, $to] = $m;
        if (!checkdate((int) $mm, (int) $dd, (int) $yyyy)) return null;
        $st = mins(substr($from, 0, 2) . ':' . substr($from, 2));
        $en = mins(substr($to, 0, 2) . ':' . substr($to, 2));
        if ($st === null || $en === null || $en <= $st) return null;
        $runs[] = ['room' => "LH-$hall", 'date' => "$yyyy-$mm-$dd",
                   'start' => substr($from, 0, 2) . ':' . substr($from, 2),
                   'end'   => substr($to, 0, 2) . ':' . substr($to, 2)];
    }
    return $runs ?: null;
}

// Pulls a pasted input apart. The slot part is a fixed shape, so where it stops is
// not a guess; a purpose may follow, and the office code may be stuck on the end
// with no space. The code is a known length, so a purpose that happens to end in
// digits cannot swallow it -- both readings are tried and the one whose code
// checks out wins.
function split_input(string $raw): ?array {
    $s = strtolower(preg_replace('/\s+/', '', $raw) ?? '');
    if (!preg_match('/^(?:[1-5]\d{12}to\d{4})(?:\+[1-5]\d{12}to\d{4})*/', $s, $m)) return null;
    $slots = $m[0];
    $rest  = substr($s, strlen($slots));
    $n     = strlen(OFFICE_CODE);

    $readings = [];
    if (strlen($rest) >= $n) {
        $readings[] = ['purpose' => unslug_(substr($rest, 0, -$n)), 'code' => substr($rest, -$n)];
    }
    $readings[] = ['purpose' => unslug_($rest), 'code' => ''];
    return ['slots' => $slots, 'readings' => $readings];
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
    $split = split_input((string) ($in['value'] ?? ''));
    $runs  = $split ? decode_slots($split['slots']) : null;
    if (!$runs) reply(['ok' => false, 'error' => 'That code is damaged — ask for it again.']);

    // Only the reading whose code checks out may say it is authorised.
    $authorised = false; $purpose = $split['readings'][count($split['readings']) - 1]['purpose'];
    foreach ($split['readings'] as $r) {
        if ($r['code'] !== '' && hash_equals(OFFICE_CODE, $r['code'])) {
            $authorised = true; $purpose = $r['purpose']; break;
        }
    }
    reply(['ok' => true, 'authorised' => $authorised, 'purpose' => $purpose,
           'slots' => $runs, 'clashes' => clashes_for($runs)]);
}

// ---- enact it ----
if ($action === 'enact') {
    $split = split_input((string) ($in['value'] ?? ''));
    $runs  = $split ? decode_slots($split['slots']) : null;
    if (!$runs) reply(['ok' => false, 'error' => 'That code is damaged — ask for it again.']);

    if (OFFICE_CODE === '') reply(['ok' => false, 'error' => 'No office code is set up on the server.']);

    $ok = false; $purpose = '';
    foreach ($split['readings'] as $r) {
        if ($r['code'] !== '' && hash_equals(OFFICE_CODE, $r['code'])) {
            $ok = true; $purpose = $r['purpose']; break;
        }
    }
    // Deliberately vague: whether the code was absent or merely wrong is not
    // something worth telling whoever is trying.
    if (!$ok) reply(['ok' => false, 'error' => 'unauthorised']);
    if ($purpose === '') reply(['ok' => false, 'error' => 'That code carries no purpose.']);

    $by = mb_substr(trim((string) ($in['by'] ?? '')), 0, 60);

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

    reply(['ok' => true, 'id' => $out['id'], 'purpose' => $purpose, 'slots' => $runs,
           'bookings' => array_map('public_view', live_bookings())]);
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
