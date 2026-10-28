#!/usr/bin/env python3
"""Generate 240 solution pages from the /solutions/ index blueprint + real catalog."""
import csv, json, re, unicodedata

# ---------- load catalog ----------
products = []
for r in csv.reader(open('/tmp/solgen/products.tsv'), delimiter='\t'):
    pid, title, slug, sku, cats = r[0], r[1], r[2], r[3] if len(r) > 3 else '', r[4] if len(r) > 4 else ''
    products.append({'id': pid, 'title': title, 'slug': slug, 'sku': sku or '', 'cats': cats or ''})

def find_products(*kws, exclude=(), limit=5):
    out = []
    for p in products:
        t = (p['title'] + ' ' + p['cats'] + ' ' + p['sku']).lower()
        if all(k.lower() in t for k in kws) and not any(e.lower() in t for e in exclude):
            out.append(p)
    return out[:limit]

def best(*kws, exclude=(), fallback=None):
    hits = find_products(*kws, exclude=exclude, limit=1)
    return hits[0] if hits else fallback

# curated platform anchors (verified real products)
ANCHOR = {
    'vue_station':   {'id': '50', 'title': 'Vantage Vue Weather Station with WeatherLink Console', 'slug': 'vantage-vue-weather-station-with-weatherlink-console', 'sku': '6242'},
    'airlink':       {'id': '207', 'title': 'AirLink Air Quality Monitor and Temperature/Humidity Sensor by Davis Instruments', 'slug': 'airlink-air-quality-monitor', 'sku': '7210'},
    'tripod':        {'id': '114', 'title': 'Mounting Tripod', 'slug': 'mounting-tripod', 'sku': '7716'},
    'live':          {'id': '96', 'title': 'WeatherLink Live', 'slug': 'weatherlink-live', 'sku': '6100'},
    'uv':            {'id': '367', 'title': 'UV Sensor', 'slug': 'uv-sensor', 'sku': '6490'},
    'leafwet':       {'id': '375', 'title': 'Leaf Wetness Sensor, Vantage Pro2 and EnviroMonitor', 'slug': 'leaf-wetness-sensor-vantage-pro2-and-enviromonitor', 'sku': '6420'},
    'soil_sentek':   {'id': '198', 'title': 'Sentek Drill & Drop Soil Moisture Probe, 48"/120 cm', 'slug': 'sentek-drill-drop-soil-moisture-probe-48-120-cm', 'sku': '6441.1222'},
    'sonic_wind':    {'id': '217', 'title': 'Sonic Anemometer Ultrasonic Wind Sensor', 'slug': 'sonic-anemometer-ultrasonic-wind-sensor', 'sku': '6415'},
    'anemometer':    {'id': '218' if True else '0', 'title': 'Anemometer for Vantage Pro2 & EnviroMonitor', 'slug': 'anemometer-for-vantage-pro2-enviromonitor', 'sku': '6410'},
}
# resolve anemometer id properly
for p in products:
    if p['sku'] == '6410':
        ANCHOR['anemometer'] = p
        break

# platform console/ISS picks (best-effort real products)
def platform_kit(platform):
    pl = platform.lower()
    if 'vue' in pl:
        return ANCHOR['vue_station']
    if 'pro2' in pl or 'pro 2' in pl:
        h = best('vantage pro2', 'console', exclude=('cabled',)) or best('vantage pro2', 'wireless')
        return h or ANCHOR['vue_station']
    if 'enviro' in pl:
        h = best('enviromonitor', 'gateway') or best('enviromonitor',)
        return h or ANCHOR['vue_station']
    if 'envoy' in pl:
        h = best('envoy',) or ANCHOR['vue_station']
        return h
    return ANCHOR['vue_station']

# ---------- component token -> product ----------
def map_token(token, platform):
    t = token.lower().strip()
    pl = (platform or '').lower()
    if t in ('vue', 'pro2', 'pro 2', 'vantage vue', 'vantage pro2', 'pro2 plus', 'pro2 iss'):
        return platform_kit(platform)
    if 'two consoles' in t or t == '2 consoles':
        return platform_kit(platform)
    if 'solar panel' in t:
        return {'id': '360', 'title': 'Extra Solar Panel Kit', 'slug': 'extra-solar-panel-kit', 'sku': '6616'}
    if t in ('temp', 'temperature', 'temp sensor'):
        h = find_products('temperature', limit=1)
        return h[0] if h else None
    if 'ip gateway' in t:
        return {'id': '220', 'title': 'EnviroMonitor IP Gateway (Wi-Fi/Ethernet)', 'slug': 'enviromonitor-ip-gateway-wi-fi-ethernet', 'sku': '6805'}
    if t == 'solar':
        return {'id': '362', 'title': 'Solar Power Kit', 'slug': 'solar-power-kit', 'sku': '6614'}
    if t == 'mast' or 'mounting mast' in t:
        return ANCHOR['tripod']
    if 'multiple consoles' in t or 'two iss' in t or 'multiple iss' in t or 'pro2 plus iss' in t or 'vantage pro2 iss' in t or 'dual iss' in t:
        return platform_kit(platform)
    if 'fan shield' in t:
        h = find_products('radiation shield', limit=1)
        return h[0] if h else None
    if 'console' in t and 'consoles' not in t:
        return platform_kit(platform)
    if 'sensor suite' in t or t.strip() in ('iss',):
        return platform_kit(platform)
    if 'tripod' in t or 'mount' in t or 'pole' in t:
        return ANCHOR['tripod']
    if 'airlink' in t or 'air link' in t:
        return ANCHOR['airlink']
    if 'weatherlink live' in t or ('live gateway' in t) or t.strip() == 'live':
        return ANCHOR['live']
    if 'solar radiation' in t:
        h = best('solar radiation', exclude=('bracket',)) or best('solar', 'sensor')
        return h
    if 'uv' in t:
        return ANCHOR['uv']
    if 'leaf wet' in t:
        return ANCHOR['leafwet']
    if 'soil' in t:
        h = best('soil moisture') or ANCHOR['soil_sentek']
        return h
    if 'anemometer' in t or 'wind' in t:
        return ANCHOR['sonic_wind'] if 'sonic' in t or 'high' in t else ANCHOR['anemometer']
    if 'rain' in t:
        h = best('rain collector') or best('rain', exclude=('gauge',))
        return h
    if 'fan-aspirat' in t or 'aspirat' in t:
        h = best('fan-aspirated') or best('radiation shield')
        return h
    if 'cabled' in t:
        h = best('cabled', exclude=('envoy',)) or best('cabled',)
        return h
    if 'battery' in t or 'backup power' in t or 'power kit' in t:
        h = best('battery') or best('power kit') or best('power', exclude=('cord',))
        return h
    if 'envoy' in t:
        h = best('envoy',)
        return h
    if 'node' in t:
        h = best('node', exclude=('door',)) or best('enviromonitor')
        return h
    if 'receiver' in t or 'repeater' in t or 'long-range' in t or 'long range' in t:
        h = best('repeater') or best('receiver') or best('envoy')
        return h
    if 'temperature' in t or 'humidity' in t or 'temp/hum' in t or 'indoor' in t:
        h = best('temperature', 'humidity') or best('temperature')
        return h
    if 'barometr' in t or 'pressure' in t:
        return None
    if 'nist' in t or 'calibrat' in t:
        return None
    if 'cellular' in t or 'data logger' in t or 'analog' in t or 'ethernet' in t or 'wifi' in t or 'software' in t or 'export' in t:
        h = best('data logger') or best('weatherlink') or None
        return h
    if 'debris' in t or 'bird spike' in t:
        h = best('bird spike') or best('debris')
        return h
    if 'chafe' in t or 'anchor' in t or 'marine' in t:
        h = best('boat hook') or best('marine', exclude=('replacement',))
        return h
    if 'dehumidifier' in t or 'dryr' in t:
        h = best('dehumidifier') or best('dryr')
        return h
    if 'troller' in t or 'sextant' in t or 'compass' in t or 'telescope' in t or 'binocular' in t:
        h = best('troller') or None
        return h
    if 'guide' in t or 'book' in t or 'chart' in t:
        return None
    return None

# ---------- slugify (WP-like) ----------
def slugify(text):
    import unicodedata as u
    text = u.normalize('NFKD', text).encode('ascii', 'ignore').decode('ascii')
    text = text.lower()
    text = re.sub(r'[^a-z0-9\s-]', '', text)
    text = re.sub(r'[\s-]+', '-', text).strip('-')
    return text

# ---------- index plan ----------
plan = json.load(open('/tmp/solgen/index_plan.json'))
items = []
for o in plan:
    text = o['text'].replace('&nbsp;', ' ').replace('&amp;', '&')
    text = re.sub(r'\s+', ' ', text).strip()
    m = re.match(r'^(.*?)\s*\((.*)\)\s*$', text)
    title = m.group(1).strip() if m else text
    comp = [x.strip() for x in (m.group(2).split('+') if m else [])]
    items.append({'cat': o['cat'].replace('&amp;', '&'), 'title': title, 'comp': comp})

assert len(items) == 240, len(items)
# verify first five map to existing slugs
existing = {
    1: 'solution-001-vantage-vue-essential-home-kit',
    2: 'solution-002-vantage-vue-smart-home-starter-kit',
    3: 'solution-003-vantage-vue-with-tripod-mount-package',
    4: 'solution-004-vantage-vue-complete-weather-network',
    5: 'solution-005-vantage-vue-solar-radiation-add-on-config',
}
import subprocess
KEEP_TITLE = {}
rows = subprocess.run(['mysql', '-u', 'wordpress', '-p8h9bWoJdPjtlm', 'wordpress_db', '--raw', '--batch', '-e',
    "SELECT post_name, post_title FROM wp_posts WHERE post_name LIKE 'solution-00%'"],
    capture_output=True, text=True).stdout.strip().split('\n')
for line in rows[1:]:
    slug, title = line.split('\t')
    KEEP_TITLE[slug] = title
for n, slug in existing.items():
    it = items[n - 1]
    gen = f"solution-{n:03d}-{slugify(it['title'])}"
    base = '-'.join(slug.split('-')[:2])
    assert gen.startswith(base), (gen, slug)
    assert slug in KEEP_TITLE, slug
print('first-five slugs OK; keeping live titles:', list(KEEP_TITLE.values()))

for i, it in enumerate(items, start=1):
    it['n'] = i
    it['slug'] = existing.get(i) or f"solution-{i:03d}-{slugify(it['title'])}"
    it['ptitle'] = KEEP_TITLE.get(it['slug'], f"Solution {i:03d}: {it['title']}")
slugs = [it['slug'] for it in items]
assert len(set(slugs)) == 240, 'dup slugs!'

# platform detect
def platform_of(it):
    s = (it['title'] + ' ' + ' '.join(it['comp'])).lower()
    if 'vantage vue' in s or s.startswith('vue'):
        return 'Vantage Vue'
    if 'pro2' in s or 'pro 2' in s or 'vantage pro' in s:
        return 'Vantage Pro2'
    if 'enviromonitor' in s:
        return 'EnviroMonitor'
    if 'airlink' in s or 'air link' in s or 'air quality' in s:
        return 'AirLink'
    if 'envoy' in s:
        return 'Weather Envoy'
    if 'weatherlink' in s:
        return 'WeatherLink'
    c = it['cat'].lower()
    if 'vue' in c: return 'Vantage Vue'
    if 'pro2' in c: return 'Vantage Pro2'
    if 'enviro' in c: return 'EnviroMonitor'
    if 'airlink' in c or 'air quality' in c: return 'AirLink'
    if 'envoy' in c: return 'Weather Envoy'
    if 'marine' in c: return 'Vantage Pro2'
    return 'Vantage Vue'

for it in items:
    it['platform'] = platform_of(it)

json.dump([{'n': it['n'], 'slug': it['slug'], 'ptitle': it['ptitle'], 'cat': it['cat'], 'title': it['title'], 'comp': it['comp'], 'platform': it['platform']} for it in items], open('/tmp/solgen/solutions_meta.json', 'w'), indent=1)
print('meta written; platforms:', {p: sum(1 for it in items if it['platform'] == p) for p in sorted(set(it['platform'] for it in items))})
