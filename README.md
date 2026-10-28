# apexvalue.com — monorepo

Two projects, one repo. Clone this on the new Proxmox and continue there:

```sh
git clone https://github.com/apexsinc/apexvalue.git
cd apexvalue
```

## Layout

| Folder | What | Status |
|---|---|---|
| `wordpress/` | Legacy WordPress child theme (`apexvalue`, Storefront child, frozen at v1.13.7) + `AGENTS.md`, `UPDATING.md`, theme `README.md` | Serves production until DNS cutover, then retired |
| `cloudflare/` | Astro rebuild targeting Cloudflare Pages + Functions + D1 + R2 + Resend | Active development — see `cloudflare/README.md` |

## wordpress/ notes

- Single contributor: `janasco <jaymaranasco@gmail.com>` (enforced by `wordpress/.githooks/commit-msg`). Enable with `git config core.hooksPath wordpress/.githooks`.
- Offsite backups: UpdraftPlus daily → Google Drive (retain 5). Pre-migration full set: `backup_2026-10-28-0910_APEX_Value_8b97fcde3e61-*` + `/var/backups/apex-pre-cloudflare-20261028/` on the old container.
- Do **not** point WordPress at this checkout's `wordpress/` subfolder — the live theme must stay at `<wp>/wp-content/themes/apexvalue/` with `style.css` at its root.

## cloudflare/ notes

Stack: Astro (static) → Cloudflare Pages; quote API as Pages Functions;
D1 (quotes, sequential numbers); R2 (product images); Resend (email);
Decap CMS (editing); Pagefind (search); Turnstile (already in use);
Airtable kept as inquiry store. Only 8 redirect rules to port.
Details in `cloudflare/README.md`.
