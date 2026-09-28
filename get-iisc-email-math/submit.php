<?php
/**
 * New-postdoc email-ID request for math.iisc.ac.in/get-iisc-email-math
 *
 * WHAT IT DOES
 *   POST (from index.html, served at /get-iisc-email-math)
 *     validates the form, signed joining report (PDF), and website photo (JPEG),
 *     fills the office's Email_ID_Creation.xlsx, and mails the files and website
 *     details to sysadmin.math (cc the requester), with a private Step 2 link.
 *     The office reviews and forwards the email request to IISc email support.
 *   POST mode=complete_profile (from step2.html) records the issued IISc user ID
 *     and office, then sends a confirmation link to that IISc email address.
 *   POST mode=confirm_profile queues the verified profile for publication.
 *   php submit.php --selftest you@iisc.ac.in
 *     sends one test mail and prints the SMTP conversation.
 *
 * INSTALLING
 *   1. Deploys with the site to /var/www/html/get-iisc-email-math/submit.php (assets/Email_ID_Creation.xlsx
 *      rides along as the template). Needs php-zip. Set upload_max_filesize
 *      to at least 10M and post_max_size to at least 20M in the web PHP config
 *      to accept the 10 MB PDF and 5 MB JPEG in one submission.
 *   2. mkdir -p /var/lib/getamailid && chown www-data:www-data /var/lib/getamailid && chmod 700 /var/lib/getamailid
 *      Every request's xlsx, pdf, JPEG and private metadata are kept under requests/.
 *   3. Credentials live OUTSIDE the repo (deploy.sh ships committed files, so a
 *      password here would land on GitHub), in /var/lib/getamailid/config.php:
 *        <?php return [
 *          'smtp_host' => 'smtp.gmail.com', 'smtp_port' => 587, 'smtp_tls' => 'starttls',
 *          'smtp_user' => 'tamathiisc@gmail.com', 'smtp_pass' => '<gmail app password>',
 *          'mail_from' => 'tamathiisc@gmail.com',
 *        ];
 *      chown www-data:www-data, chmod 600.
 *   4. Install the private publisher described in _scripts/postdoc-publisher/README.md.
 */

declare(strict_types=1);
date_default_timezone_set('Asia/Kolkata');

// ------------------------------------------------------------------ config
const DATA_DIR = '/var/lib/getamailid';
const TEMPLATE = __DIR__ . '/../assets/Email_ID_Creation.xlsx';

const TO          = 'sysadmin.math@iisc.ac.in';
const CC          = [];

const MAX_PDF     = 10 * 1024 * 1024;
const MAX_PHOTO   = 5 * 1024 * 1024;
const PER_IP_HOUR = 5;
const PROJECTS    = ['CSSP', 'FnA', 'SID'];
const DOCTYPES    = ['Joining Report', 'Joining Memo'];
const EMAIL_DOMAINS = ['gmail.com', 'outlook.com', 'yahoo.com', 'hotmail.com', 'icloud.com', 'proton.me'];
// One label per common fellowship/role; variants and uncommon historical
// appointments can be entered through Others. Keep in sync with index.html.
const DESIGNATIONS = [
    'NBHM Postdoctoral Fellow', 'Institute Postdoctoral Fellow', 'NPDF',
    'Raman PDF', 'Dr. D.S. Kothari', 'IoE Postdoctoral Fellow', 'CPDF',
    'IISc RA', 'Research Associate', 'DST Women Scientist', 'INSPIRE Faculty',
    'Axis Bank Centre Postdoctoral Fellow'
];

$CFG = [];
if (is_readable(DATA_DIR . '/config.php')) {
    $loaded = @include DATA_DIR . '/config.php';
    if (is_array($loaded)) $CFG = $loaded;
}
$c = static fn(string $k, $d) => $CFG[$k] ?? $d;
define('MAIL_FROM', $c('mail_from', 'no-reply@math.iisc.ac.in'));
define('SMTP_HOST', $c('smtp_host', ''));
define('SMTP_PORT', (int) $c('smtp_port', 25));
define('SMTP_TLS',  $c('smtp_tls',  ''));
define('SMTP_USER', $c('smtp_user', ''));
define('SMTP_PASS', $c('smtp_pass', ''));
define('SMTP_HELO', $c('smtp_helo', 'math.iisc.ac.in'));

// Copied from booking.php (generated from _data/faculty.yaml). The form sends the
// name (typed against a datalist); it must match one of these exactly.
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

// ------------------------------------------------------------------ send
function send_request(array $r, string $base, array &$trace): bool {
    $body = "Hi Nitish,\n\n"
          . "Dr. {$r['name']} has submitted the new postdoc form. The email creation spreadsheet, "
          . "{$r['doctype']}, and photograph are attached. Please review the spreadsheet and joining document, "
          . "then forward only those two files to emailsupport@iisc.ac.in. The photograph is for the department website.\n\n"
          . "Email request\n"
          . "Name: {$r['name']}\n"
          . "Designation: {$r['designation']}\n"
          . "Reporting faculty: {$r['faculty_name']}\n"
          . "Joining / ending: {$r['joining']} to {$r['ending']}\n"
          . "Personal email: {$r['email']}\n"
          . "Mobile: {$r['mobile']}\n\n"
          . "Department website details\n"
          . "name: {$r['name']}\n"
          . "webpage: {$r['webpage']}\n"
          . "phd: {$r['phd']}\n"
          . "position: {$r['designation']}\n"
          . "research-area: {$r['research_area']}\n"
          . "photo: attached JPEG\n\n"
          . "For the postdoc (copied here): once your IISc email ID is ready, enter your user ID and office room at:\n"
          . "{$r['step2_url']}\n\n"
          . "We’ll ask you to confirm the profile from your IISc inbox before it appears on the website. "
          . "Please also visit Nitish in Room R-14 for biometrics and computer lab access.\n";
    $tag = preg_replace('/[^A-Za-z0-9]+/', '_', $r['name']);
    return send_mail([TO], array_merge(CC, [$r['email']]), 'New postdoc details - Dr. ' . $r['name'], $body,
        ["Email_ID_Creation_$tag.xlsx" => "$base.xlsx", str_replace(' ', '_', $r['doctype']) . "_$tag.pdf" => "$base.pdf",
         "Postdoc_Photo_$tag.jpg" => "$base.jpg"], $trace);
}

// ------------------------------------------------------------------ web
function reply(int $status, string $text): never {
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo $text;
    exit;
}

function handle_profile(): void {
    $id = trim((string) ($_POST['request_id'] ?? ''));
    $token = trim((string) ($_POST['token'] ?? ''));
    if (!preg_match('/^\d{8}-\d{6}-[a-z0-9-]+-[a-f0-9]{12}$/', $id) || !preg_match('/^[a-f0-9]{64}$/', $token))
        reply(400, 'Invalid Step 2 link. Please use the link in your email.');
    $base = DATA_DIR . "/requests/$id";
    if (!is_file("$base.pending.json") && !is_file("$base.verify.json") &&
        !is_file("$base.ready.json") && !is_file("$base.published.json"))
        reply(400, 'Invalid Step 2 link. Please use the link in your email.');
    $lock = fopen("$base.step2.lock", 'c');
    if (!$lock || !flock($lock, LOCK_EX)) reply(500, 'Could not open this request. Please try again.');
    $state = null;
    foreach (['pending', 'verify', 'ready', 'published'] as $candidate) {
        if (is_file("$base.$candidate.json")) { $state = $candidate; break; }
    }
    if ($state === null) reply(400, 'Invalid Step 2 link. Please use the link in your email.');
    $record = json_decode((string) file_get_contents("$base.$state.json"), true);
    if (!is_array($record) || !isset($record['token_hash']) || !is_string($record['token_hash']) ||
        !hash_equals($record['token_hash'], hash('sha256', $token)))
        reply(400, 'Invalid Step 2 link. Please use the link in your email.');
    if ($state === 'ready' || $state === 'published') reply(200, 'Your profile has already been confirmed.');
    if ($state === 'verify' && filemtime("$base.verify.json") > time() - 300)
        reply(200, 'Please check your IISc inbox for the confirmation link.');

    $user_id = trim((string) ($_POST['user_id'] ?? ''));
    $office = trim((string) ($_POST['office'] ?? ''));
    if (!preg_match('/^[A-Za-z0-9._-]{1,100}$/', $user_id)) reply(400, 'Invalid IISc user ID');
    if (strlen($office) > 100 || preg_match('/[\r\n]/', $office)) reply(400, 'Invalid office room');
    if (!is_file("$base.jpg")) reply(500, 'Photograph could not be found. Please contact office.math@iisc.ac.in.');
    $recipient = $user_id . '@iisc.ac.in';
    if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) reply(400, 'Invalid IISc user ID');
    $record['user_id'] = $user_id;
    $record['office'] = $office;
    $record['completed_at'] = date(DATE_ATOM);
    $confirm_token = bin2hex(random_bytes(32));
    $record['confirm_hash'] = hash('sha256', $confirm_token);
    $confirm_url = 'https://math.iisc.ac.in/get-iisc-email-math/verify/#id=' . rawurlencode($id) . '&token=' . $confirm_token;
    $temp = "$base.verify-" . bin2hex(random_bytes(6)) . '.tmp';
    $encoded = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($encoded === false || file_put_contents($temp, $encoded, LOCK_EX) === false)
        reply(500, 'Could not save Step 2. Please try again.');
    @chmod($temp, 0600);
    $trace = [];
    if (!send_mail([$recipient], [], 'Confirm your Mathematics postdoc profile',
        "Hello {$record['name']},\n\nYour department profile and photograph are ready. Please confirm that we may add them to the website:\n$confirm_url\n\nIf this wasn't you, you can ignore this email.\n\nDepartment of Mathematics, IISc\n",
        [], $trace)) {
        @unlink($temp);
        error_log("getamailid: profile confirmation failed for $id\n" . implode("\n", $trace));
        reply(500, 'Could not send confirmation to your IISc address. Please try again later.');
    }
    if (!rename($temp, "$base.verify.json")) reply(500, 'Could not save Step 2. Please contact office.math@iisc.ac.in.');
    @unlink("$base.pending.json");
    reply(200, 'Please check your IISc inbox and confirm your profile using the link we sent.');
}

function confirm_profile(): void {
    $id = trim((string) ($_POST['request_id'] ?? ''));
    $token = trim((string) ($_POST['token'] ?? ''));
    if (!preg_match('/^\d{8}-\d{6}-[a-z0-9-]+-[a-f0-9]{12}$/', $id) || !preg_match('/^[a-f0-9]{64}$/', $token))
        reply(400, 'Invalid confirmation link.');
    $base = DATA_DIR . "/requests/$id";
    if (!is_file("$base.verify.json") && !is_file("$base.ready.json") && !is_file("$base.published.json"))
        reply(400, 'Invalid confirmation link.');
    $lock = fopen("$base.step2.lock", 'c');
    if (!$lock || !flock($lock, LOCK_EX)) reply(500, 'Could not open this request. Please try again.');
    foreach (['verify', 'ready', 'published'] as $state) {
        $path = "$base.$state.json";
        if (!is_file($path)) continue;
        $record = json_decode((string) file_get_contents($path), true);
        if (!is_array($record) || !isset($record['confirm_hash']) || !is_string($record['confirm_hash']) ||
            !hash_equals($record['confirm_hash'], hash('sha256', $token)))
            reply(400, 'Invalid confirmation link.');
        if ($state !== 'verify') reply(200, 'Your profile has already been confirmed.');
        if (!rename($path, "$base.ready.json")) reply(500, 'Could not queue your profile. Please try again.');
        reply(200, 'Thanks for confirming. Your profile and photograph will appear on the department website shortly.');
    }
    reply(400, 'Invalid confirmation link.');
}

function handle_post(): void {
    $p = static fn(string $k) => trim((string) ($_POST[$k] ?? ''));
    if ($p('website') !== '') reply(200, 'ok');                         // honeypot

    @mkdir(DATA_DIR . '/requests', 0700, true);
    $ip = preg_replace('/[^0-9a-f.:]/i', '', $_SERVER['REMOTE_ADDR'] ?? '');
    $ipf = DATA_DIR . "/ip-$ip";
    $hits = array_filter(file_exists($ipf) ? explode("\n", trim(file_get_contents($ipf))) : [], fn($t) => (int) $t > time() - 3600);
    if (count($hits) >= PER_IP_HOUR) reply(429, 'Too many submissions; try again later.');
    file_put_contents($ipf, implode("\n", array_merge($hits, [time()])));

    $first = $p('first'); $last = $p('last'); $choice = $p('designation'); $mobile = $p('mobile');
    $email_local = $p('email_local'); $domain_choice = $p('email_domain');
    $email_domain = $domain_choice === 'Others' ? $p('email_domain_other') : $domain_choice;
    if ($domain_choice !== 'Others' && !in_array($domain_choice, EMAIL_DOMAINS, true)) reply(400, 'Please select an email domain');
    if ($email_local === '' || strlen($email_local) > 100 || preg_match('/[@\s]/', $email_local)) reply(400, 'Invalid email username');
    if ($email_domain === '' || strlen($email_domain) > 150 || !preg_match('/^[A-Za-z0-9.-]+$/', $email_domain)) reply(400, 'Invalid email domain');
    $email = $email_local . '@' . $email_domain;
    $fac = $p('faculty'); $join = $p('joining'); $end = $p('ending'); $proj = $p('project'); $doctype = $p('doctype');
    $webpage = $p('webpage'); $phd = $p('phd'); $research_area = $p('research_area');
    $desig = $choice === 'Others' ? $p('designation_other') : $choice;
    if ($choice !== 'Others' && !in_array($choice, DESIGNATIONS, true)) reply(400, 'Please select a designation from the list');
    foreach (['first' => $first, 'last' => $last, 'designation' => $desig, 'email' => $email, 'joining' => $join, 'ending' => $end] as $k => $v)
        if ($v === '' || strlen($v) > 200) reply(400, "Missing or too long: $k");
    foreach (['phd' => $phd, 'research_area' => $research_area] as $k => $v)
        if ($v === '' || strlen($v) > ($k === 'research_area' ? 500 : 200)) reply(400, "Missing or too long: $k");
    if (strlen($webpage) > 500 || ($webpage !== '' && (!filter_var($webpage, FILTER_VALIDATE_URL) || !preg_match('~^https?://~i', $webpage))))
        reply(400, 'Invalid webpage URL');
    foreach ([$first, $last, $desig, $mobile, $fac, $phd, $research_area] as $value)
        if (preg_match('/[\r\n]/', $value)) reply(400, 'Please enter each detail on one line');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) reply(400, 'Invalid personal email');
    if (!in_array($fac, FACULTY, true)) reply(400, 'Reporting faculty must be picked from the list');
    if (!in_array($proj, PROJECTS, true)) reply(400, 'Invalid project ID');
    if (!in_array($doctype, DOCTYPES, true)) reply(400, 'Invalid supporting document type');
    $d = static fn(string $s) => ($t = strtotime($s)) && preg_match('/^\d{4}-\d\d-\d\d$/', $s) ? date('d-m-Y', $t) : reply(400, 'Invalid date');
    $join = $d($join); $end = $d($end);
    if (!preg_match('/^\+?[0-9 \-]{6,20}$/', $mobile)) reply(400, 'Invalid mobile number');

    $up = $_FILES['report'] ?? null;
    if (!$up || $up['error'] !== UPLOAD_ERR_OK) reply(400, 'Joining report PDF is required; check that both files fit the server upload limits');
    if ($up['size'] > MAX_PDF) reply(400, 'PDF is larger than 10 MB');
    if (substr((string) file_get_contents($up['tmp_name'], false, null, 0, 5), 0, 5) !== '%PDF-') reply(400, 'The joining report must be a PDF');

    $photo = $_FILES['photo'] ?? null;
    if (!$photo || $photo['error'] !== UPLOAD_ERR_OK) reply(400, 'A website photograph is required; check that both files fit the server upload limits');
    if ($photo['size'] > MAX_PHOTO) reply(400, 'Photograph is larger than 5 MB');
    $image = @getimagesize($photo['tmp_name']);
    if (!$image || $image[2] !== IMAGETYPE_JPEG) reply(400, 'The photograph must be a JPEG image');
    if ($image[0] * 4 !== $image[1] * 3) reply(400, 'The photograph must have a portrait 3:4 crop');

    $name = "$first $last";
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-') ?: 'postdoc';
    $id = date('Ymd-His') . '-' . $slug . '-' . bin2hex(random_bytes(6));
    $base = DATA_DIR . "/requests/$id";
    if (!move_uploaded_file($up['tmp_name'], "$base.pdf")) reply(500, 'Could not store the PDF');
    if (!move_uploaded_file($photo['tmp_name'], "$base.jpg")) reply(500, 'Could not store the photograph');
    fill_xlsx(['Mathematics', $first, $last, $desig, $mobile, $email, 'Prof. ' . $fac, $join, $end, $proj], "$base.xlsx");

    $token = bin2hex(random_bytes(32));
    $record = ['id' => $id, 'token_hash' => hash('sha256', $token), 'name' => $name,
               'webpage' => $webpage, 'phd' => $phd, 'position' => $desig,
               'research_area' => $research_area, 'created_at' => date(DATE_ATOM)];
    $encoded = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($encoded === false || file_put_contents("$base.pending.json", $encoded, LOCK_EX) === false)
        reply(500, 'Could not save the website details. Please contact office.math@iisc.ac.in.');
    @chmod("$base.pending.json", 0600);
    $step2_url = 'https://math.iisc.ac.in/get-iisc-email-math/step2/#id=' . rawurlencode($id) . '&token=' . $token;

    $trace = [];
    if (!send_request(['name' => $name, 'email' => $email, 'designation' => $desig, 'faculty_name' => $fac,
                       'joining' => $join, 'ending' => $end, 'mobile' => $mobile, 'doctype' => $doctype,
                       'webpage' => $webpage, 'phd' => $phd, 'research_area' => $research_area,
                       'step2_url' => $step2_url], $base, $trace)) {
        error_log("getamailid: send failed for $id\n" . implode("\n", $trace));
        reply(500, 'Your details were saved but the email could not be sent. Please write to office.math@iisc.ac.in.');
    }
    reply(200, 'Thanks. Your details have reached the department office, and your Step 2 link is in the email copied to you. Use it when your IISc email ID is ready. Please visit Nitish in Room R-14 for biometrics and computer lab access.');
}

// ------------------------------------------------------------------ main
if (PHP_SAPI === 'cli') {
    $a = $argv[1] ?? '';
    if ($a === '--selftest') {
        $trace = [];
        $ok = send_mail([$argv[2] ?? TO], [], 'getamailid self-test', "It works.\n", [], $trace);
        echo implode("\n", $trace), "\n", $ok ? "sent\n" : "FAILED\n"; exit((int) !$ok);
    }
    echo "usage: --selftest addr\n"; exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(405, 'POST only');
if (($_POST['mode'] ?? '') === 'complete_profile') handle_profile();
if (($_POST['mode'] ?? '') === 'confirm_profile') confirm_profile();
handle_post();
