# Postdoc profile publisher

The public form stores the cropped JPEG and profile fields under
`/var/lib/getamailid/requests`. After the applicant enters their IISc user ID
and room in Step 2, a confirmation link goes to `USERID@iisc.ac.in`. Clicking
**Confirm profile** changes the private request from `.verify.json` to
`.ready.json`. The publisher processes only `.ready.json` requests.

For each confirmed request, `publish.py` adds a quoted YAML entry to
`_data/postdocs.yaml`, copies the photograph to `images/stu-USERID.jpg`, commits
those two files, pushes `master` to GitHub, builds the site and copies it to
`/var/www/html`. It then marks the request `.published.json`. The personal
email, mobile number, joining PDF, spreadsheet and confirmation tokens are never
committed. If a push or build fails, the ready request remains for retry. A
matching `onboarding-id` in the YAML prevents duplicate entries on retry.

## Server setup

1. Deploy the updated site once with the usual site deployment procedure so
   `step2.html`, `verify.html`, and `submit.php` are live.
2. Make a dedicated checkout at `/srv/postdoc-publisher/DeptWeb` on `master`.
   Configure its `origin` as an SSH GitHub remote with a **write-enabled deploy
   key for this repository only**. Keep the private key outside the checkout and
   readable only by root. Configure `user.name` and `user.email` in this checkout
   for automated commits. Confirm that a noninteractive `git push` is permitted
   by the repository's branch rules. Do not put credentials in the web root or
   `/var/lib/getamailid`. On the current server, the dedicated key is
   `/root/.ssh/postdoc_publisher`; set the checkout's `core.sshCommand` to
   `ssh -i /root/.ssh/postdoc_publisher -o IdentitiesOnly=yes` after adding its
   public key to the repository's Deploy Keys with **Allow write access**.
3. Install the same Ruby and Jekyll bundle used by the regular deployment and
   ensure `bundle`, `git`, and `rsync` are on the service's PATH. Run
   `bundle check` in the dedicated checkout. The web root must be writable by
   the service user; the provided unit runs as root so it can read the private
   requests and deploy the built files.
4. Copy `postdoc-publisher.service` and `postdoc-publisher.timer` to
   `/etc/systemd/system/`. Adjust the checkout path in the service if needed.
   Run `systemctl daemon-reload` and
   `systemctl enable --now postdoc-publisher.timer`.
5. Submit a test Step 1 request, complete Step 2 with a test IISc mailbox, and
   click the confirmation link. Check `journalctl -u postdoc-publisher.service`
   and confirm both the GitHub commit and live `/postdocs.html` entry. Use a
   disposable test user ID, then remove that test entry and photo in a separate
   commit.

The service holds `/run/lock/postdoc-publisher.lock` while processing the queue.
It stops on a dirty checkout or a duplicate user ID, leaving the request ready
and logging the problem for an administrator to resolve. It never edits the
department website from the PHP web process.
