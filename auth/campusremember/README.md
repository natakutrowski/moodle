# CampusFR 30-day opt-in login — DEV PROTOTYPE

For Moodle 5.0 and PHP 8.4. Do NOT deploy to production before browser/integration tests.

* Installation: extract this `campusremember` directory into `auth/`, run Moodle CLI upgrade, then enable plugin alongside existing `email` authentication.
* Does not change existing `sessiontimeout`, existing users' authentication methods, or Moodle core.
* After a successful POST on `/login/index.php` with explicit `campusremember=1`, writes a host-only Secure/HttpOnly/SameSite=Lax 30-day cookie containing a selector/validator. The validator is hashed in DB.
* Login pages and protected-page login redirects can restore an expired Moodle session using a valid token. Max token duration is absolute, not rolling.
* Logout revokes this device's token; password changes revoke all tokens; admins are excluded.
* Limitation: public pages which never call `require_login` do not restore a session automatically. User is re-authenticated when navigating to login or a protected page.
* Security note: this is an alpha prototype for browser testing, not externally penetration tested.

Test cases before PROD: (1) opt-out no cookie, (2) opt-in cookie & hashed DB token, (3) remove `MoodleSession` cookie and revisit `/login/index.php` (auto-login), (4) log out then re-open (no auto-login), (5) password change revokes token, (6) admin cannot opt in, (7) check FR/RU labels and Edly mobile.
