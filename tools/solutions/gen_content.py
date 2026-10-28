#!/usr/bin/env python3
"""Render long-form solution pages: real products, family briefs, interlinks."""
import json, re, html as ihtml
from gen_meta import map_token, platform_kit, ANCHOR, find_products  # noqa

plan = json.load(open('/tmp/solgen/index_plan.json'))
items = []
for o in plan:
    text = o['text'].replace('&nbsp;', ' ').replace('&amp;', '&')
    text = re.sub(r'\s+', ' ', text).strip()
    m = re.match(r'^(.*)\s+\(([^()]*)\)\s*$', text)
    assert m, text
    title, comp = m.group(1).strip(), [x.strip() for x in m.group(2).split('+')]
    items.append({'cat': o['cat'].replace('&amp;', '&'), 'title': title, 'comp': comp})

assert len(items) == 240
import subprocess
KEEP_TITLE = {}
rows = subprocess.run(['mysql', '-u', 'wordpress', '-p8h9bWoJdPjtlm', 'wordpress_db', '--raw', '--batch', '-e',
    "SELECT post_name, post_title FROM wp_posts WHERE post_name LIKE 'solution-00%'"],
    capture_output=True, text=True).stdout.strip().split('\n')
for line in rows[1:]:
    slug, title = line.split('\t')
    KEEP_TITLE[slug] = title

def slugify_wp(text):
    import unicodedata as u
    text = u.normalize('NFKD', text).encode('ascii', 'ignore').decode('ascii')
    return re.sub(r'[\s-]+', '-', re.sub(r'[^a-z0-9\s-]', '', text.lower())).strip('-')

existing = {
    1: 'solution-001-vantage-vue-essential-home-kit',
    2: 'solution-002-vantage-vue-smart-home-starter-kit',
    3: 'solution-003-vantage-vue-with-tripod-mount-package',
    4: 'solution-004-vantage-vue-complete-weather-network',
    5: 'solution-005-vantage-vue-solar-radiation-add-on-config',
}
for i, it in enumerate(items, start=1):
    it['n'] = i
    it['slug'] = existing.get(i) or f"solution-{i:03d}-{slugify_wp(it['title'])}"
    it['ptitle'] = KEEP_TITLE.get(it['slug'], f"Solution {i:03d}: {it['title']}")
assert len(set(it['slug'] for it in items)) == 240

def platform_of(it):
    s = (it['title'] + ' ' + ' '.join(it['comp'])).lower()
    if 'vantage vue' in s or s.startswith('vue'): return 'Vantage Vue'
    if 'pro2' in s or 'pro 2' in s or 'vantage pro' in s: return 'Vantage Pro2'
    if 'enviromonitor' in s: return 'EnviroMonitor'
    if 'airlink' in s or 'air link' in s or 'air quality' in s: return 'AirLink'
    if 'envoy' in s: return 'Weather Envoy'
    if 'weatherlink' in s: return 'WeatherLink'
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

# ---------------- family briefs ----------------
FAM = {
 'home': dict(audience='homeowners and weather enthusiasts', use='home weather monitoring',
    conditions=('Philippine homes face monsoon downpours, brownouts and corrosive coastal air. A wireless, solar-powered station keeps reporting through power interruptions, and the monitoring console runs quietly indoors. Mount the sensor suite clear of roof heat and aircon exhausts for trustworthy readings.'),
    deploy=['Pick an open spot at least 3 metres from walls and concrete heat sources.', 'Mount the sensor suite 1.5 to 2 metres above ground or on a short mast clear of obstructions.', 'Pair the console indoors and confirm wireless reception before final mounting.', 'Register the station on WeatherLink.com for phone and web access to your data.', 'Photograph the install and note the heights for future reference.'],
    faq=[('Does it work during brownouts?', 'Yes. The outdoor sensor suite runs on solar power with battery backup, and the console keeps logging on its own supply. Your data history stays intact through power interruptions, which matters during the monsoon season when outages cluster with the most interesting weather.'),
         ('Do I need Wi-Fi at the mounting location?', 'No. Only the indoor console or gateway needs internet. The sensor suite talks to it over Davis wireless, which reaches through typical concrete homes, so the mast can live in the garden while the display lives in the living room.')]),
 'pro': dict(audience='researchers, professionals and serious weather watchers', use='professional-grade measurement',
    conditions=('Research and professional sites demand traceable, defensible data. Fan-aspirated radiation shielding removes midday temperature bias, stainless hardware resists salt air, and cabled options eliminate wireless doubt in electrically noisy compounds. Every reading should be able to survive a panel review.'),
    deploy=['Site the aspirated sensor suite over natural ground, away from exhausts and reflective walls.', 'Use the tripod or a rigid galvanized mast; vibration is the enemy of wind accuracy.', 'Log a two-week overlap with any outgoing instrument before decommissioning it.', 'Document siting with photos so published data carries full metadata.', 'Schedule quarterly cleaning of the radiation shield plates.'],
    faq=[('Is the data defensible for research use?', 'Fan-aspirated temperature and humidity plus documented siting meets the standard most Philippine university and agency projects accept. Keep the siting photos and calibration notes with your records.'),
         ('Cabled or wireless for a professional site?', 'Cabled where trenching is possible; wireless where it is not. Both log to the same console and WeatherLink archives, and both can be quoted with or without installation support.')]),
 'agri': dict(audience='farmers, agronomists and estate managers', use='farm decision-making',
    conditions=('Philippine farms irrigate by guesswork too often. Soil moisture and leaf wetness data cut water and chemical costs, while on-farm rainfall records settle insurance and payroll disputes. Sensors must survive carabao, cogon fires and fiesta fireworks — mount accordingly and fence what matters.'),
    deploy=['Place soil probes in the active root zone of your main crop, not at the field edge.', 'Keep the rain collector clear of mango and banana canopy drip lines.', 'Fence or elevate the station where animals graze.', 'Review readings weekly against irrigation schedules and adjust.', 'Train one farmhand to wipe the collector and read the console.'],
    faq=[('How many soil probes do I need?', 'Start with one representative station per distinct soil or crop block. Large or hilly farms add probes per zone rather than guessing from a single point; the quotation can stage this across two seasons.'),
         ('Can farmhands read the data?', 'Yes. The console shows current conditions at a glance, and WeatherLink pages can be shared to phones without accounts for viewers. No training beyond a ten-minute walkthrough.')]),
 'marine': dict(audience='boat owners, marinas, resorts and coastal operators', use='marine and coastal monitoring',
    conditions=('Salt air destroys cheap electronics in months. Davis marine hardware uses UV-stable housings and stainless fittings, and wireless links cross from mooring or rooftop mast to the clubhouse console without trenching through seawalls. Rinse schedules matter more than specs sheets.'),
    deploy=['Mount above spray height with a clear horizon for wind.', 'Rinse salt crust from the collector funnel monthly in the dry season.', 'Secure masts against habagat gusts; check U-bolts every quarter.', 'Keep the console where crew actually look at it — not in a locked office.', 'Log a pre-typhoon photo set for insurance.'],
    faq=[('Will it survive a typhoon?', 'Stations routinely survive Signal #3 and up when solidly mounted. Remove or secure the console display, photograph the install for insurance, and re-level the mast afterwards. We stock replacement mounts locally.'),
         ('Can I monitor the boat remotely?', 'Yes. Add a WeatherLink Live gateway on shore power and watch wind and conditions from anywhere before deciding to sail out. Several resort clients run exactly this setup.')]),
 'enterprise': dict(audience='facilities teams, enterprises and infrastructure operators', use='facility and infrastructure monitoring',
    conditions=('Campuses, subdivisions and industrial parks need continuous, auditable environmental records — for safety compliance, insurance and operations planning. Cabled and gateway-fed architectures fit buildings with strict IT policies, and logged data settles more arguments than opinions.'),
    deploy=['Coordinate mounting with building admin and secure written permission for rooftops.', 'Prefer cabled or gateway links where corporate Wi-Fi policies block new devices.', 'Nominate one custodian for console access and data exports.', 'Archive monthly summaries for compliance files.', 'Schedule a yearly siting review as buildings and trees change.'],
    faq=[('Can we get data into our existing systems?', 'WeatherLink exports and API access feed dashboards and reports. Tell us your format during quotation and we will scope it; several enterprise clients run automated monthly summaries.'),
         ('Who maintains it after installation?', 'Your team handles wiping the collector and checking mounts; we supply spares from Philippine stock with local support and documentation.')]),
 'hybrid': dict(audience='buyers assembling a purpose-built station', use='custom monitoring builds',
    conditions=('Off-menu combinations are normal in this business — a Vue sensor suite with a Pro-grade mount, or an Envoy feeding an existing console. We verify compatibility of every custom bundle before quoting, so nothing arrives that cannot talk to each other. Bring us your existing inventory list.'),
    deploy=['List what you already own (consoles, gateways, mounts) before ordering.', 'Confirm wireless compatibility between old and new Davis generations.', 'Mount new sensors to the same datum as existing ones for comparable records.', 'Test the full chain on the ground before climbing the mast.', 'Keep old manuals; frequency tables matter when mixing generations.'],
    faq=[('Will new sensors work with my old console?', 'Usually yes within the same ecosystem. Share your console model and firmware when requesting a quotation and we will confirm before you spend anything.'),
         ('Can I expand later?', 'Yes. Davis systems are modular — add AirLink, soil or solar sensors to the same network without replacing the base station. Quote the expansion in stages if budget is tight.')]),
 'air': dict(audience='families, schools, clinics and offices watching air quality', use='air quality and health protection',
    conditions=('Metro Manila and lowland cities regularly breach healthy PM2.5 levels in the dry months, and Taal vog drifts without warning. A hyperlocal AirLink reading beats city-wide averages when deciding on masks, outdoor classes or clinic ventilation. One sensor changes household decisions within a week.'),
    deploy=['Mount outdoors away from kitchen exhausts and genset fumes.', 'Pair with temperature and humidity context for proper AQI interpretation.', 'Share the public WeatherLink page with family, staff or parents.', 'Set personal thresholds for alerts rather than relying on generic advisories.', 'Revisit placement after the first month of readings.'],
    faq=[('Is one AirLink enough for a school or office?', 'One covers its immediate microclimate. Campuses and multi-building sites add units per block and compare readings side by side; quotations scale per building.'),
         ('Does vog really reach us?', 'Taal and Kanlaon vog routinely affects CALABARZON and Metro Manila. Local PM data tells you when to keep children indoors — city averages update too slowly for that call.')]),
 'viticulture': dict(audience='vineyards, orchards and high-value crop growers', use='precision crop microclimate management',
    conditions=('Disease pressure tracks leaf wetness hours, not rainfall totals. In-field wetness and soil data time sprays precisely, cutting chemical passes while protecting high-value harvests. The sensor pays for itself in a single season of avoided sprays.'),
    deploy=['Install leaf wetness sensors at canopy height inside the block, not at the shed.', 'Map wetness hours against your spray log for the first season.', 'Protect cables from tractors and carabao with conduit.', 'Calibrate rain readings against a manual gauge monthly.', 'Photograph canopy stage at each spray for the record.'],
    faq=[('How does this reduce spraying costs?', 'By spraying on measured infection risk instead of calendar dates. Most growers cut one to two passes per season, which funds the station several times over.'),
         ('Does it work for mango and banana too?', 'Yes. The same wetness and soil logic applies to mango blossom blight and banana sigatoka management — only thresholds change.')]),
 'industrial': dict(audience='safety officers, construction and plant managers', use='site safety and compliance monitoring',
    conditions=('Heat index stops work, lightning stops cranes, and gusts stop lifts. On-site measurement beats airport data in disputes with clients and regulators, and logged records defend your safety decisions after incidents. This is protective equipment that happens to measure weather.'),
    deploy=['Mount where workers actually are — not behind the site office.', 'Set heat-index and lightning alert thresholds with your safety plan.', 'Protect the station from dust, cement splash and equipment strikes.', 'Download logs weekly into the site safety file.', 'Brief foremen on reading the console at toolbox meetings.'],
    faq=[('Can this justify a work stoppage?', 'Logged on-site readings are the standard basis for heat and lightning stoppages, and they hold up far better than distant station data when clients push back.'),
         ('What about lightning detection?', 'Add the stand-alone practice: when thunder is heard, clear exposed steel and cranes regardless of what any single sensor says. Sensors inform; procedures decide.')]),
 'offshore': dict(audience='ports, fishponds, island resorts and offshore operators', use='coastal and offshore awareness',
    conditions=('Open-water and fishpond operations live or die on wind and squall awareness. Shore-mounted stations with remote viewing give bangkeros and caretakers a shared, trustworthy picture before anyone launches. A single avoided squall pays for the station.'),
    deploy=['Elevate above rooftops and nipa for clean wind fetch.', 'Use stainless fasteners exclusively within sight of salt water.', 'Provide shore power or solar for the gateway and test night operation.', 'Train caretakers to read wind trend, not just the current number.', 'Establish a go/no-go wind rule and post it.'],
    faq=[('How far offshore is the reading valid?', 'Wind within a few kilometres of shore tracks well; treat it as advisory beyond that and combine with official marine forecasts.'),
         ('Can fishpond caretakers use it?', 'Yes. A simple console or shared phone page showing wind and rain is enough for daily feeding and water-exchange decisions.')]),
 'edu': dict(audience='schools, universities and research groups', use='teaching and research',
    conditions=('A campus station turns abstract science into daily data: students log, graph and argue about real local measurements. For research, documented siting and exports satisfy methods sections and panel reviews. Few teaching investments get used every single school day.'),
    deploy=['Involve students in siting and installation day — ownership keeps sensors alive.', 'Publish a simple daily readout board from the console.', 'Archive raw exports each term for student projects.', 'Assign a faculty custodian for holidays and typhoon season.', 'Tie one assignment per term to the live data.'],
    faq=[('What level is this suitable for?', 'Elementary observations through undergraduate research. The same hardware serves both; only the analysis changes, so the station grows with the school.'),
         ('Can students access data from home?', 'Yes. WeatherLink sharing gives read-only pages for assignments without touching the station or its settings.')]),
 'iot': dict(audience='smart-home owners and integrators', use='connected home automation',
    conditions=('Weather-aware homes close awnings before rain, run ventilation on cool dry nights and warn of garden frost — automatically. Local-first links keep working when the internet does not, which matters during Philippine storms when cloud-only gadgets go blind.'),
    deploy=['Keep sensor-to-hub paths short through concrete walls.', 'Start with one automation (rain alert or awning) before expanding.', 'Document API keys and hub logins where the household can find them.', 'Test fail-safes: automations must fail to a safe state on sensor loss.', 'Revisit rules each season; monsoon and dry months want different logic.'],
    faq=[('Does it work with my existing smart home platform?', 'Through WeatherLink and common integrations, yes for major platforms. Tell us your hub when quoting and we will confirm the exact path before you buy.'),
         ('What happens when the internet drops?', 'Local sensor-to-console links keep logging. Cloud automations pause and resume; nothing is lost, and the station never stops measuring.')]),
 'mount': dict(audience='installers and DIY builders', use='solid, storm-ready installation',
    conditions=('Most station failures are mounting failures: wobble ruins wind data, shade ruins temperature, and rust ruins everything within two monsoons. Galvanized steel, stainless fasteners and proper grounding outlast the electronics they carry. Buy the mount once.'),
    deploy=['Choose the mount before the station: tripod, pole or wall arm to suit the site.', 'Level the mast with a bubble level; re-check after the first storm.', 'Use stainless U-bolts and clamps within 5 km of the sea.', 'Ground tall masts in lightning-prone provinces.', 'Leave service loops in cables for future adjustments.'],
    faq=[('Tripod or pole — which is better?', 'Tripods win on rooftops and rentals (no drilling); poles win for height and rigidity in open ground. We stock both with the right clamps.'),
         ('How high should the sensors go?', 'Wind reads best at 10 metres; temperature and rain read best near 2 metres over grass. Compromise honestly for your site and note the heights.')]),
}
CATKEY = [('home', ['Home & Enthusiast']), ('pro', ['Professional & Research']), ('agri', ['AgriTech & Environmental']),
 ('marine', ['Marine & Coastal (Specialized']), ('enterprise', ['Enterprise & Infrastructure']), ('hybrid', ['Hybrid & Custom']),
 ('air', ['Air Quality & Health', 'Advanced Air Quality']), ('viticulture', ['Precision Agriculture', 'Viticulture']),
 ('industrial', ['Industrial, Safety']), ('offshore', ['Marine, Coastal & Offshore']), ('edu', ['Education, Research']),
 ('iot', ['Smart Home & IoT']), ('mount', ['Mounting & Installation'])]
def famkey(cat):
    for k, needles in CATKEY:
        if any(n.lower() in cat.lower() for n in needles):
            return k
    return 'hybrid'

PLAT = {
 'Vantage Vue': 'The Vantage Vue pairs an integrated sensor suite with a backlit console: wind, rain, temperature, humidity and pressure in one wireless hop. It is the platform we recommend when ease of ownership matters more than laboratory traceability.',
 'Vantage Pro2': 'The Vantage Pro2 is the professional workhorse: modular sensors, fan-aspirated radiation shielding options, cabled variants for difficult sites, and an ecosystem of soil, leaf-wetness and solar add-ons. When measurements must defend decisions, this is the platform.',
 'EnviroMonitor': 'EnviroMonitor is a node-and-gateway architecture for distributed sensing: nodes where the crop, slope or shoreline is, one gateway with cellular backhaul, everything visible in one dashboard. Built for area coverage rather than single-point readings.',
 'AirLink': 'AirLink measures PM1, PM2.5, PM10, temperature and humidity in a compact wireless package. It slots into any Davis network or runs standalone, and its readings feed the same WeatherLink pages and alerts as the big stations.',
 'Weather Envoy': 'The Weather Envoy family bridges consoles, gateways and data services: the quiet infrastructure that moves measurements from mast to screen. Enterprise and multi-site builds lean on it heavily.',
 'WeatherLink': 'WeatherLink is the connective tissue — Live gateways, consoles and cloud services that turn raw sensor chatter into pages, alerts and exports people actually use.',
}
INTRO_V = [
 ("{title} pairs {plat} sensing with a {use} brief: {aud}. Instead of assembling parts and hoping they cooperate, you get a verified bundle from the authorized Davis Instruments distributor in the Philippines — quoted, supplied and supported locally.",
  "Every component in this kit earns its place. The {plat} core handles the measurement physics; the add-ons extend it to your exact site. Our team confirms compatibility before you pay a centavo, and everything ships from Philippine stock with local support after installation.",
  "What follows is the full picture: what is in the kit, why each part is there, how to deploy it in Philippine conditions, answers to the questions buyers actually ask, and how to get a formal quotation. No prices are published — every quote is prepared against current stock and peso pricing."),
 ("Built for {aud}, {title} starts from the {plat} platform and extends it into {use}. It is a complete, compatible, locally supported package — no grey-market parts, no compatibility surprises, no waiting on overseas shipping.",
  "What makes a bundle a solution is integration: sensors, mounting, power and data viewed as one system. That is how we quote it, how we ship it, and how we support it from our Philippine warehouse. Read on for the kit list, the reasoning, the deployment guide and the questions everyone asks first.",
  "A note on honesty: no single kit fits every site. This page tells you exactly what this bundle covers and where its limits are, so your quotation request can state them and our reply can address them precisely."),
 ("If {use} is the job, {title} is the tool: a {plat}-based kit scoped for {aud}. Genuine Davis Instruments hardware, verified compatibility, local stock and local support — the four things that separate a working station from an expensive ornament.",
  "Below you will find the complete kit with factory part numbers, the reasoning behind each choice, a deployment checklist tuned to Philippine conditions, maintenance expectations, and answers to common questions. When you are ready, the Request a Quote button starts a formal quotation — not a shopping cart."),
]

def esc(s):
    return ihtml.escape(s, quote=False)

def plink(p):
    return f'<a href="/products/{p["slug"]}/">{esc(p["title"])}</a>' if p else ''

def addon_picks(platform, used_slugs, k=3):
    cands, seen = [], set(used_slugs)
    key = {'Vantage Vue': 'vue', 'Vantage Pro2': 'pro2', 'EnviroMonitor': 'enviromonitor',
           'AirLink': 'airlink', 'Weather Envoy': 'envoy', 'WeatherLink': 'weatherlink'}.get(platform, '')
    from gen_meta import products as ALL
    for p in ALL:
        if p['slug'] in seen:
            continue
        if key and key in (p['title'] + ' ' + p['slug']).lower():
            cands.append(p)
            if len(cands) >= k:
                break
    return cands

def build(it, siblings):
    n, title, comp, plat, cat = it['n'], it['title'], it['comp'], it['platform'], it['cat']
    F = FAM[famkey(cat)]
    v = INTRO_V[n % 3]
    mapped = [(tok, map_token(tok, plat + ' ' + title)) for tok in comp]
    prod_links = [p for _, p in mapped if p]
    rows = []
    base = platform_kit(plat + ' ' + title)
    rows.append(('Base station', f'{plink(base)}' + (f' <small>SKU {esc(base["sku"])}</small>' if base.get('sku') else '')))
    for tok, p in mapped:
        if p and (not base or p['slug'] != base['slug']):
            cell = f'{plink(p)}' + (f' <small>SKU {esc(p["sku"])}</small>' if p.get('sku') else '')
        elif p:
            cell = 'Included in the base station above'
        else:
            cell = esc(tok) + ' <small>(specified at quotation — tell us your site)</small>'
        rows.append((esc(tok), cell))
    benefits = []
    for tok, p in mapped[:6]:
        t = tok.lower()
        if 'tripod' in t or 'mount' in t or 'pole' in t or 'mast' in t or 'guy' in t:
            benefits.append('Storm-ready mounting keeps wind data honest through habagat gusts and simplifies re-levelling after typhoons.')
        elif 'airlink' in t or 'air ' in t or 'aqi' in t or 'pm' in t:
            benefits.append('Hyperlocal air-quality readings for health decisions, not city-wide averages that update too late.')
        elif 'live' in t or 'gateway' in t or 'cellular' in t:
            benefits.append('Remote viewing from any phone — check conditions before travelling to site.')
        elif 'solar' in t or 'uv' in t:
            benefits.append('Solar-radiation data for energy assessment, agronomy and evapotranspiration work.')
        elif 'soil' in t:
            benefits.append('Root-zone moisture data that cuts irrigation guesswork and documents water use.')
        elif 'leaf' in t:
            benefits.append('Leaf-wetness hours that time sprays precisely instead of by calendar.')
        elif 'console' in t and 'consoles' not in t:
            benefits.append('An at-a-glance indoor console the whole household or crew can read without training.')
        elif 'consoles' in t or 'two ' in t or 'dual' in t or '2 ' in t:
            benefits.append('Redundancy and coverage: two observation points, one coherent data picture.')
        elif 'cabled' in t:
            benefits.append('Cabled link for electrically noisy compounds or sites with strict wireless policies.')
        elif 'battery' in t or 'backup' in t or 'power' in t:
            benefits.append('Power resilience for brownouts and off-grid sites common outside Metro Manila.')
        elif 'rain' in t:
            benefits.append('Tipping-spoon rainfall accuracy for records that settle disputes and claims.')
        elif 'anemometer' in t or 'wind' in t:
            benefits.append('Wind sensing tuned for the exposure of the site, from courtyards to coastlines.')
        elif 'envoy' in t or 'logger' in t or 'receiver' in t or 'repeater' in t:
            benefits.append('Data-path hardware that keeps measurements flowing to screens and archives.')
        elif 'sensor suite' in t or t in ('pro2', 'vue', 'iss'):
            benefits.append(f'Sensor suite integrated with the {plat} core — specified to the site in the quotation.')
        else:
            benefits.append(f'{tok.strip()} scoped to the site at quotation time — no guesswork in the final quote.')
    benefits = benefits[:6]
    addons = addon_picks(plat, set([base['slug']] + [p['slug'] for p in prod_links])) if base else []
    rel = [s for s in siblings if s['slug'] != it['slug']][:4]
    B = []
    B.append(f'<!-- wp:paragraph --><p>{v[0].format(title=esc(title), plat=esc(plat), use=esc(F["use"]), aud=esc(F["audience"]))}</p><!-- /wp:paragraph -->')
    B.append(f'<!-- wp:paragraph --><p>{PLAT.get(plat, PLAT["Vantage Vue"])}</p><!-- /wp:paragraph -->')
    B.append(f'<!-- wp:paragraph --><p>{v[1].format(title=esc(title), plat=esc(plat), use=esc(F["use"]), aud=esc(F["audience"]))}</p><!-- /wp:paragraph -->')
    B.append('<!-- wp:paragraph --><p>A note on honesty: no single kit fits every site. This page tells you exactly what this bundle covers and where its limits are, so your quotation request can state them and our reply can address them precisely.</p><!-- /wp:paragraph -->')
    B.append(f'<!-- wp:heading {{"level":2}} --><h2 class="wp-block-heading">What is in the {esc(title)}</h2><!-- /wp:heading -->')
    B.append('<!-- wp:paragraph --><p>Every kit below is picked from stock we actually carry. Part numbers are Davis factory SKUs — quote them back to us and we will confirm availability and current peso pricing.</p><!-- /wp:paragraph -->')
    trows = ''.join(f'<tr><td>{k}</td><td>{val}</td></tr>' for k, val in rows)
    B.append(f'<!-- wp:table --><figure class="wp-block-table"><table><thead><tr><th>Component</th><th>Specified product</th></tr></thead><tbody>{trows}</tbody></table></figure><!-- /wp:table -->')
    B.append(f'<!-- wp:heading {{"level":2}} --><h2 class="wp-block-heading">Why this combination works</h2><!-- /wp:heading -->')
    B.append('<!-- wp:list --><ul class="wp-block-list">' + ''.join(f'<!-- wp:list-item --><li>{esc(b)}</li><!-- /wp:list-item -->' for b in benefits) + '</ul><!-- /wp:list -->')
    if addons:
        B.append(f'<!-- wp:heading {{"level":2}} --><h2 class="wp-block-heading">Compatible add-ons worth considering</h2><!-- /wp:heading -->')
        B.append('<!-- wp:list --><ul class="wp-block-list">' + ''.join(
            f'<!-- wp:list-item --><li>{plink(a)}' + (f' <small>SKU {esc(a["sku"])}</small>' if a.get('sku') else '') + '</li><!-- /wp:list-item -->' for a in addons) + '</ul><!-- /wp:list -->')
    B.append(f'<!-- wp:heading {{"level":2}} --><h2 class="wp-block-heading">Deployment guide</h2><!-- /wp:heading -->')
    B.append('<!-- wp:list {"ordered":true} --><ol class="wp-block-list">' + ''.join(f'<!-- wp:list-item --><li>{esc(s)}</li><!-- /wp:list-item -->' for s in F['deploy']) + '</ol><!-- /wp:list -->')
    B.append(f'<!-- wp:heading {{"level":2}} --><h2 class="wp-block-heading">Site survey checklist</h2><!-- /wp:heading -->')
    B.append('<!-- wp:list {"ordered":true} --><ol class="wp-block-list">'
        '<!-- wp:list-item --><li>Confirm mounting surface, mast height and who grants roof or ground permission.</li><!-- /wp:list-item -->'
        '<!-- wp:list-item --><li>Walk the wireless path between mast and console; note concrete walls and metal roofs.</li><!-- /wp:list-item -->'
        '<!-- wp:list-item --><li>Identify the nearest power outlet for the console and any gateway.</li><!-- /wp:list-item -->'
        '<!-- wp:list-item --><li>Photograph obstructions in all four compass directions for the quotation.</li><!-- /wp:list-item -->'
        '<!-- wp:list-item --><li>Note flood history and storm exposure so mounts are specified honestly.</li><!-- /wp:list-item -->'
        '</ol><!-- /wp:list -->')
    B.append(f'<!-- wp:heading {{"level":2}} --><h2 class="wp-block-heading">Built for Philippine conditions</h2><!-- /wp:heading -->')
    B.append(f'<!-- wp:paragraph --><p>{esc(F["conditions"])}</p><!-- /wp:paragraph -->')
    B.append(f'<!-- wp:heading {{"level":2}} --><h2 class="wp-block-heading">Data, connectivity and power</h2><!-- /wp:heading -->')
    B.append(f'<!-- wp:paragraph --><p>The {esc(plat)} core logs continuously and shares data through the indoor console and WeatherLink services. Sensor links are wireless for easy retrofits; where sites demand it we specify cabled variants. Solar trickle-charging with battery backup carries the outdoor suite through overcast weeks and brownouts, and every reading is timestamped for later export and analysis.</p><!-- /wp:paragraph -->')
    B.append('<!-- wp:paragraph --><p>Day to day, most owners live on two screens: the console at a glance and the WeatherLink page on their phone. Console alarms cover immediate thresholds such as heat index or gust spikes, while the cloud archive handles weekly reviews, monthly reports and year-on-year comparisons. When you request a quotation, tell us who needs to see the data and on what devices — we will make sure the right display and sharing path is in the kit rather than bolted on afterwards.</p><!-- /wp:paragraph -->')
    B.append(f'<!-- wp:heading {{"level":2}} --><h2 class="wp-block-heading">What this kit does not cover</h2><!-- /wp:heading -->')
    B.append('<!-- wp:paragraph --><p>No honest page skips this. This bundle does not include installation labour, masonry or electrical work, permits, or internet service at the site. It does not replace official PAGASA warnings for public safety decisions. And if your site needs parameters outside this kit — water level, for example — say so in the inquiry and we will scope them as add-ons instead of pretending one station measures everything.</p><!-- /wp:paragraph -->')
    B.append(f'<!-- wp:heading {{"level":2}} --><h2 class="wp-block-heading">Maintenance and local support</h2><!-- /wp:heading -->')
    B.append('<!-- wp:paragraph --><p>Wipe the rain collector funnel seasonally, check mount hardware after typhoons, and keep firmware current through WeatherLink. Spares come from our Philippine warehouse — not from an overseas cart — with local advice when something behaves oddly. If a sensor ever reads suspiciously, send us a photo of the install and the console screen; most issues are siting, not hardware.</p><!-- /wp:paragraph -->')
    B.append('<!-- wp:paragraph --><p>Budget thirty minutes a quarter: a soft brush for the collector, a glance at cable ties and U-bolts, and a check that the console still shows full reception bars. Stations that get this basic attention routinely run a decade or more, and every wearable part — from cups and vanes to batteries and bird spikes — is a stocked spare part with a factory number, not a reason to replace the system.</p><!-- /wp:paragraph -->')
    B.append(f'<!-- wp:heading {{"level":2}} --><h2 class="wp-block-heading">Frequently asked questions</h2><!-- /wp:heading -->')
    faqs = [('Can I add more sensors later?',
             'Yes. The Davis ecosystem is modular: soil, leaf-wetness, solar, UV and air-quality sensors join the same network without replacing the base station. Many buyers start with the core kit on this page and expand at the next budget cycle — mention future plans in your inquiry so we leave the right expansion path open.'),
            ('How do I get a price for this kit?',
             'Use the Request a Quote button below or the inquiry form. Tell us quantities, delivery location and any special requirements; our team replies with a formal quotation against current stock. No prices are published because peso pricing moves with stock and exchange rates — the quotation you receive is firm.'),
            ('How long does delivery take?',
             'Stocked components ship from our Philippine warehouse; lead times for non-stocked parts are confirmed in the quotation before you commit. Provincial deliveries add standard courier time, which we state upfront.'),
            ('Is installation included?',
             'Metro Cebu installations can be arranged; elsewhere we supply a mounting guide with the kit and remote guidance during your install. Complex mast work should use a local rigger — we specify the mount so any competent installer succeeds.')]
    for q, a in F['faq']:
        faqs.append((q, a))
    for q, a in faqs[:5]:
        B.append(f'<!-- wp:heading {{"level":3}} --><h3 class="wp-block-heading">{esc(q)}</h3><!-- /wp:heading -->')
        B.append(f'<!-- wp:paragraph --><p>{esc(a)}</p><!-- /wp:paragraph -->')
    B.append(f'<!-- wp:heading {{"level":2}} --><h2 class="wp-block-heading">Related solutions</h2><!-- /wp:heading -->')
    B.append('<!-- wp:list --><ul class="wp-block-list">' + ''.join(
        f'<!-- wp:list-item --><li><a href="/{s["slug"]}/">{esc(s["ptitle"])}</a></li><!-- /wp:list-item -->' for s in rel) + '</ul><!-- /wp:list -->')
    B.append('<!-- wp:paragraph --><p>Browse the <a href="/solutions/">full solutions index</a> or the <a href="/products/">product catalogue</a>.</p><!-- /wp:paragraph -->')
    B.append('<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/inquiry/">Request a Quote</a></div><!-- /wp:button --></div><!-- /wp:buttons -->')
    content = '\n\n'.join(B)
    words = len(re.sub(r'<[^>]+>', ' ', content).split())
    return content, words, sum(1 for _, p in mapped if p), [tok for tok, p in mapped if not p]

if __name__ == '__main__':
    out, rep = {}, []
    for it in items:
        sibs = [x for x in items if x['cat'] == it['cat']]
        idx = sibs.index(next(s for s in sibs if s['slug'] == it['slug']))
        rel = (sibs[max(0, idx - 1):idx] + sibs[idx + 1:idx + 3])[:4]
        if len(rel) < 3:
            rel = (rel + [s for s in sibs if s not in rel])[:4]
        content, words, linked, unmapped = build(it, rel)
        out[it['slug']] = {'n': it['n'], 'ptitle': it['ptitle'], 'cat': it['cat'], 'content': content}
        rep.append({'n': it['n'], 'slug': it['slug'], 'words': words, 'linked_comps': linked, 'unmapped': unmapped, 'comp': it['comp']})
    json.dump(out, open('/tmp/solgen/solutions_content.json', 'w'))
    json.dump(rep, open('/tmp/solgen/report.json', 'w'), indent=1)
    ws = [r['words'] for r in rep]
    print('pages:', len(out), '| words min/med/max:', min(ws), sorted(ws)[len(ws)//2], max(ws))
    from collections import Counter
    print('unmapped tokens:', Counter(t for r in rep for t in r['unmapped']).most_common(25))
