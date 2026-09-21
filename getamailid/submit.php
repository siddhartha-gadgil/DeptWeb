<?php
/**
 * New-postdoc email-ID request for math.iisc.ac.in/getamailid
 *
 * WHAT IT DOES
 *   POST (from index.html, served at /getamailid)
 *     validates the form + the signed joining report (PDF), fills the office's
 *     Email_ID_Creation.xlsx, queues the request, and at once mails Nitish
 *     "X has filled the form; the request goes out in 4 working hours".
 *   php submit.php --cron        (crontab, every minute)
 *     sends every queued request whose time has come: to emailsupport, cc
 *     office.math, chair.math and the reporting faculty, with the
 *     xlsx and the PDF attached, From nitishs@iisc.ac.in.
 *   php submit.php --selftest you@iisc.ac.in
 *     sends one test mail and prints the SMTP conversation.
 *   php submit.php --when "2026-09-25 17:29"
 *     prints when a form filled at that moment would be sent (sanity check).
 *
 * INSTALLING
 *   1. Deploys with the site to /var/www/html/getamailid/submit.php (assets/Email_ID_Creation.xlsx
 *      rides along as the template).
 *   2. mkdir -p /var/lib/getamailid && chown www-data:www-data /var/lib/getamailid && chmod 700 /var/lib/getamailid
 *   3. Credentials live OUTSIDE the repo (deploy.sh ships committed files, so a
 *      password here would land on GitHub), in /var/lib/getamailid/config.php:
 *        <?php return [
 *          'smtp_host' => 'iisc-ac-in.mail.protection.outlook.com', 'smtp_port' => 25, 'smtp_tls' => 'starttls',
 *          'smtp_user' => '',   // M365 Direct Send: no auth, iisc.ac.in recipients only
 *          'mail_from' => 'nitishs@iisc.ac.in',
 *        ];
 *      chown www-data:www-data, chmod 600.
 *   4. crontab -u www-data -e:
 *        * * * * * php /var/www/html/getamailid/submit.php --cron
 *   5. To cancel a queued request before it goes out:
 *        rm /var/lib/getamailid/queue/<id>.*
 *      (the id is in the notification mail's subject.)
 */

declare(strict_types=1);
date_default_timezone_set('Asia/Kolkata');

// ------------------------------------------------------------------ config
const DATA_DIR = '/var/lib/getamailid';
const TEMPLATE = __DIR__ . '/../assets/Email_ID_Creation.xlsx';

const ME          = 'nitishs@iisc.ac.in';
const TO          = 'emailsupport@iisc.ac.in';
const CC          = ['office.math@iisc.ac.in', 'chair.math@iisc.ac.in'];
const DOMAIN      = 'iisc.ac.in';
const LIST_NAME   = 'postdocs.math';

// "4 working hours": Mon-Fri, 09:00-17:30. A form filled Friday 17:29 goes out
// Monday 12:59. ponytail: no holiday calendar; add a HOLIDAYS list here if it bites.
const WORK_START  = 9 * 60;
const WORK_END    = 17 * 60 + 30;
const DELAY_MIN   = 4 * 60;

const MAX_PDF     = 10 * 1024 * 1024;
const PER_IP_HOUR = 5;
const PROJECTS    = ['CSSP', 'FnA', 'SID'];

$CFG = [];
if (is_readable(DATA_DIR . '/config.php')) {
    $loaded = @include DATA_DIR . '/config.php';
    if (is_array($loaded)) $CFG = $loaded;
}
$c = static fn(string $k, $d) => $CFG[$k] ?? $d;
define('MAIL_FROM', $c('mail_from', ME));
define('SMTP_HOST', $c('smtp_host', ''));
define('SMTP_PORT', (int) $c('smtp_port', 25));
define('SMTP_TLS',  $c('smtp_tls',  ''));
define('SMTP_USER', $c('smtp_user', ''));
define('SMTP_PASS', $c('smtp_pass', ''));
define('SMTP_HELO', $c('smtp_helo', 'math.iisc.ac.in'));

// Copied from booking.php (generated from _data/faculty.yaml). The browser sends a
// user-id; the server decides what name and address that means, so a form
// cannot cc an arbitrary address.
const FACULTY = [
    'arvind'         => 'Arvind Ayyer',
    'abhi'           => 'Abhishek Banerjee',
    'bharali'        => 'Gautam Bharali',
    'tirtha'         => 'Tirthankar Bhattacharyya',
    'soumya'         => 'Soumya Das',
    'vvdatar'        => 'Ved Datar',
    'shaunakdeo'     => 'Shaunak Deo',
    'gadgil'         => 'Siddhartha Gadgil',
    'radhikag'       => 'Radhika Ganapathy',
    'gudi'           => 'Thirupathi Gudi',
    'purvigupta'     => 'Purvi Gupta',
    'subhojoy'       => 'Subhojoy Gupta',
    'skiyer'         => 'Srikanth K. Iyer',
    'maheshkakde'    => 'Mahesh Kakde',
    'khare'          => 'Apoorva Khare',
    'manju'          => 'Manjunath Krishnapur',
    'arkamallick'    => 'Arka Mallick',
    'muna'           => 'Muna Naik',
    'naru'           => 'E. K. Narayanan',
    'bharathwaj'     => 'Bharathwaj Palvannan',
    'vamsipingali'   => 'Vamsi Pritham Pingali',
    'rangaraj'       => 'Govindan Rangarajan',
    'sanchayan'      => 'Sanchayan Sen',
    'harish'         => 'Harish Seshadri',
    'swarnendusil'   => 'Swarnendu Sil',
    'vaidyaganesh'   => 'Ganesh Vaidya',
    'rvenkat'        => 'R. Venkatesh',
    'kverma'         => 'Kaushal Verma'
];

// ------------------------------------------------------------------ scheduling
// Advance $ts by $mins working minutes. Outside working hours the clock does not
// run: minutes still owed carry to the next working morning.
function add_working_minutes(int $ts, int $mins): int {
    while (true) {
        $dow = (int) date('N', $ts);
        $tod = (int) date('G', $ts) * 60 + (int) date('i', $ts);
        if ($dow > 5 || $tod >= WORK_END) {                         // jump to next working 09:00
            $ts = strtotime('tomorrow', $ts) + WORK_START * 60;
            continue;
        }
        if ($tod < WORK_START) { $ts = strtotime('today', $ts) + WORK_START * 60; $tod = WORK_START; }
        $left = WORK_END - $tod;
        if ($mins <= $left) return $ts + $mins * 60;
        $mins -= $left;
        $ts += $left * 60;
    }
}

// ------------------------------------------------------------------ xlsx
// The template is a zip; row 2 of sheet1 is the one data row. Rewrite just that
// row with inline strings, keeping each cell's style, and leave everything else.
function fill_xlsx(array $vals, string $out): void {
    if (!copy(TEMPLATE, $out)) throw new RuntimeException('cannot copy template');
    $z = new ZipArchive();
    if ($z->open($out) !== true) throw new RuntimeException('cannot open xlsx');
    $xml = $z->getFromName('xl/worksheets/sheet1.xml');
    $styles = ['A' => 4, 'B' => 5, 'C' => 6, 'D' => 4, 'E' => 6, 'F' => 7, 'G' => 8, 'H' => 9, 'I' => 9, 'J' => 5];
    $row = '<row r="2">';
    foreach (array_keys($styles) as $i => $col) {
        $v = htmlspecialchars($vals[$i], ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $row .= "<c r=\"{$col}2\" s=\"{$styles[$col]}\" t=\"inlineStr\"><is><t>$v</t></is></c>";
    }
    $row .= '</row>';
    $xml = preg_replace('~<row r="2">.*?</row>~s', $row, $xml, 1, $n);
    if ($n !== 1) throw new RuntimeException('template row 2 not found');
    $z->addFromString('xl/worksheets/sheet1.xml', $xml);
    $z->close();
}

// ------------------------------------------------------------------ mail
function smtp_read($fh): array {
    $all = '';
    do {
        $line = fgets($fh, 1024);
        if ($line === false || $line === '') return [0, $all];
        $all .= $line;
    } while (strlen($line) >= 4 && $line[3] === '-');
    return [(int) substr($all, 0, 3), rtrim($all)];
}

function smtp_say($fh, string $cmd, int $expect, array &$trace): bool {
    fwrite($fh, $cmd . "\r\n");
    $secret = !preg_match('/^(EHLO|STARTTLS|AUTH|MAIL|RCPT|DATA|QUIT)\b/', $cmd);
    $trace[] = '> ' . ($secret ? '(credentials withheld)' : $cmd);
    [$code, $text] = smtp_read($fh);
    $trace[] = '< ' . $text;
    return $code === $expect;
}

// $files: [filename => path]. Everything goes out as one multipart/mixed message.
function send_mail(array $to, array $cc, string $subject, string $body, array $files, array &$trace = []): bool {
    $b = 'b' . bin2hex(random_bytes(12));
    $msg = "From: " . MAIL_FROM . "\r\n"
         . "To: " . implode(', ', $to) . "\r\n"
         . ($cc ? "Cc: " . implode(', ', $cc) . "\r\n" : '')
         . "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n"
         . "Date: " . date('r') . "\r\n"
         . "Message-ID: <" . bin2hex(random_bytes(8)) . '@' . SMTP_HELO . ">\r\n"
         . "MIME-Version: 1.0\r\n"
         . "Content-Type: multipart/mixed; boundary=\"$b\"\r\n\r\n"
         . "--$b\r\nContent-Type: text/plain; charset=utf-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
         . chunk_split(base64_encode($body));
    foreach ($files as $name => $path) {
        $msg .= "--$b\r\nContent-Type: application/octet-stream; name=\"$name\"\r\n"
              . "Content-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"$name\"\r\n\r\n"
              . chunk_split(base64_encode(file_get_contents($path)));
    }
    $msg .= "--$b--\r\n";

    $err = 0; $etxt = '';
    $fh = @stream_socket_client((SMTP_TLS === 'ssl' ? 'ssl://' : 'tcp://') . SMTP_HOST . ':' . SMTP_PORT, $err, $etxt, 10);
    if (!$fh) { $trace[] = "could not connect to " . SMTP_HOST . ':' . SMTP_PORT . " -- $etxt"; return false; }
    stream_set_timeout($fh, 30);
    try {
        [$code, $text] = smtp_read($fh);
        $trace[] = '< ' . $text;
        if ($code !== 220) return false;
        if (!smtp_say($fh, 'EHLO ' . SMTP_HELO, 250, $trace)) return false;
        if (SMTP_TLS === 'starttls') {
            if (!smtp_say($fh, 'STARTTLS', 220, $trace)) return false;
            if (!stream_socket_enable_crypto($fh, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { $trace[] = 'STARTTLS failed'; return false; }
            if (!smtp_say($fh, 'EHLO ' . SMTP_HELO, 250, $trace)) return false;
        }
        if (SMTP_USER !== '') {
            if (!smtp_say($fh, 'AUTH LOGIN', 334, $trace)) return false;
            if (!smtp_say($fh, base64_encode(SMTP_USER), 334, $trace)) return false;
            if (!smtp_say($fh, base64_encode(SMTP_PASS), 235, $trace)) return false;
        }
        if (!smtp_say($fh, 'MAIL FROM:<' . MAIL_FROM . '>', 250, $trace)) return false;
        foreach (array_merge($to, $cc) as $r) if (!smtp_say($fh, "RCPT TO:<$r>", 250, $trace)) return false;
        if (!smtp_say($fh, 'DATA', 354, $trace)) return false;
        fwrite($fh, preg_replace('/^\./m', '..', $msg) . "\r\n.\r\n");
        [$code, $text] = smtp_read($fh);
        $trace[] = '< ' . $text;
        if ($code !== 250) return false;
        smtp_say($fh, 'QUIT', 221, $trace);
        return true;
    } finally { @fclose($fh); }
}

// ------------------------------------------------------------------ queue
function qdir(): string { return DATA_DIR . '/queue'; }

function send_request(array $r, string $base, array &$trace): bool {
    $body = "Dear Team,\n\n"
          . "Could you please create an IISc email account for a PostDoc who has recently joined the Department of Mathematics? The details are attached.\n\n"
          . "Kindly add the user to " . LIST_NAME . " as well.\n\n"
          . "Thank you.\nNitish\n080-2293-2514\n";
    return send_mail([TO], array_merge(CC, [$r['faculty'] . '@' . DOMAIN]),
        'Email ID creation request - ' . $r['name'], $body,
        ['Email_ID_Creation.xlsx' => "$base.xlsx", 'Joining_Report.pdf' => "$base.pdf"], $trace);
}

function run_cron(): void {
    foreach (glob(qdir() . '/*.json') ?: [] as $f) {
        $r = json_decode(file_get_contents($f), true);
        if (!$r || $r['send_at'] > time()) continue;
        $base = substr($f, 0, -5);
        $trace = [];
        if (send_request($r, $base, $trace)) {
            @mkdir(DATA_DIR . '/sent', 0700);
            foreach (['json', 'xlsx', 'pdf'] as $e) @rename("$base.$e", DATA_DIR . '/sent/' . basename($base) . ".$e");
        } else {
            error_log("getamailid: send failed for " . basename($base) . "\n" . implode("\n", $trace));
        }
    }
}

// ------------------------------------------------------------------ web
function reply(int $status, string $text): never {
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo $text;
    exit;
}

function handle_post(): void {
    $p = static fn(string $k) => trim((string) ($_POST[$k] ?? ''));
    if ($p('website') !== '') reply(200, 'ok');                         // honeypot

    @mkdir(qdir(), 0700, true);
    $ip = preg_replace('/[^0-9a-f.:]/i', '', $_SERVER['REMOTE_ADDR'] ?? '');
    $ipf = DATA_DIR . "/ip-$ip";
    $hits = array_filter(file_exists($ipf) ? explode("\n", trim(file_get_contents($ipf))) : [], fn($t) => (int) $t > time() - 3600);
    if (count($hits) >= PER_IP_HOUR) reply(429, 'Too many submissions; try again later.');
    file_put_contents($ipf, implode("\n", array_merge($hits, [time()])));

    $first = $p('first'); $last = $p('last'); $desig = $p('designation'); $mobile = $p('mobile');
    $email = $p('email'); $fac = $p('faculty'); $join = $p('joining'); $end = $p('ending'); $proj = $p('project');
    foreach (['first' => $first, 'last' => $last, 'designation' => $desig, 'email' => $email, 'joining' => $join, 'ending' => $end] as $k => $v)
        if ($v === '' || strlen($v) > 200) reply(400, "Missing or too long: $k");
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) reply(400, 'Invalid personal email');
    if (!isset(FACULTY[$fac])) reply(400, 'Unknown reporting faculty');
    if (!in_array($proj, PROJECTS, true)) reply(400, 'Invalid project ID');
    $d = static fn(string $s) => ($t = strtotime($s)) && preg_match('/^\d{4}-\d\d-\d\d$/', $s) ? date('d-m-Y', $t) : reply(400, 'Invalid date');
    $join = $d($join); $end = $d($end);
    if (!preg_match('/^\+?[0-9 \-]{6,20}$/', $mobile)) reply(400, 'Invalid mobile number');

    $up = $_FILES['report'] ?? null;
    if (!$up || $up['error'] !== UPLOAD_ERR_OK) reply(400, 'Joining report PDF is required');
    if ($up['size'] > MAX_PDF) reply(400, 'PDF is larger than 10 MB');
    if (substr((string) file_get_contents($up['tmp_name'], false, null, 0, 5), 0, 5) !== '%PDF-') reply(400, 'The joining report must be a PDF');

    $name = "$first $last";
    $id = date('Ymd-His') . '-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
    $base = qdir() . "/$id";
    if (!move_uploaded_file($up['tmp_name'], "$base.pdf")) reply(500, 'Could not store the PDF');
    fill_xlsx(['Mathematics', $first, $last, $desig, $mobile, $email, 'Prof. ' . FACULTY[$fac], $join, $end, $proj], "$base.xlsx");

    $send_at = add_working_minutes(time(), DELAY_MIN);
    file_put_contents("$base.json", json_encode([
        'name' => $name, 'email' => $email, 'faculty' => $fac, 'send_at' => $send_at, 'filed_at' => time(),
    ]));

    $trace = [];
    send_mail([ME], [], "New email ID form: $name [$id]",
        "Hello Nitish,\n\nThis is an automated email to notify you that $name has filled the form to get a new iisc email and an email to the email support is scheduled to be sent out exactly after 4 working hours.\n",
        ['Email_ID_Creation.xlsx' => "$base.xlsx", 'Joining_Report.pdf' => "$base.pdf"], $trace);

    reply(200, "Thank you. Your request has been recorded and will be forwarded to IISc email support on " . date('l, d M Y \a\t H:i', $send_at) . '.');
}

// ------------------------------------------------------------------ main
if (PHP_SAPI === 'cli') {
    $a = $argv[1] ?? '';
    if ($a === '--cron') { run_cron(); exit; }
    if ($a === '--when') { echo date('D d M Y H:i', add_working_minutes(strtotime($argv[2] ?? 'now'), DELAY_MIN)), "\n"; exit; }
    if ($a === '--selftest') {
        $trace = [];
        $ok = send_mail([$argv[2] ?? ME], [], 'getamailid self-test', "It works.\n", [], $trace);
        echo implode("\n", $trace), "\n", $ok ? "sent\n" : "FAILED\n"; exit((int) !$ok);
    }
    // Self-check for the working-hours arithmetic: Fri 17:29 -> Mon 12:59, Mon 09:00 -> Mon 13:00.
    assert(date('D H:i', add_working_minutes(strtotime('2026-09-25 17:29'), DELAY_MIN)) === 'Mon 12:59');
    assert(date('D H:i', add_working_minutes(strtotime('2026-09-21 09:00'), DELAY_MIN)) === 'Mon 13:00');
    assert(date('D H:i', add_working_minutes(strtotime('2026-09-26 11:00'), DELAY_MIN)) === 'Mon 13:00');
    echo "usage: --cron | --when 'YYYY-MM-DD HH:MM' | --selftest addr\n"; exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(405, 'POST only');
handle_post();
