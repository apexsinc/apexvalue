# Solutions bulk generator (240 pages)

Built the 240 solution pages from the `/solutions/` index blueprint
plus real product data (titles, SKUs, slugs from WooCommerce).

- `gen_meta.py` — index plan + slug mapping + platform detection
  (needs the `index_plan.json` + `products.tsv` inputs; regenerates
  `solutions_meta.json`). Run from this dir: `python3 gen_meta.py`
- `gen_content.py` — long-form page HTML + interlinking
  (`solutions_content.json`, word-count report). Run: `python3 gen_content.py`
- `insert.php` — `wp eval-file` inserter (235 inserts + 5 updates).
- `index_new.html` pattern — index link rebuild was done inline;
  see session log 2026-10-28 if a rebuild is ever needed again.

Inputs are snapshots in `/tmp/solgen` on the old container; the
canonical record is the UpdraftPlus backup + DB dump in `/var/backups`.
