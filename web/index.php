<?php
declare(strict_types=1);

// Configuració (credencials) fora del directori web: /var/www/config/db.php
$cfg = require '/var/www/config/db.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; form-action 'self'");

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

// Camps com "timetable" poden portar marcatge HTML: es converteix a text net.
function net(?string $s): string {
    $s = preg_replace('~<(br\s*/?|/p|/li|/tr|/div)>~i', "\n", (string)$s);
    $s = html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string)preg_replace("~\n{3,}~", "\n\n", $s));
}
function dat(?string $d): string { return $d ? date('d/m/Y', strtotime($d)) : '—'; }
function adr(array $r, string $a, string $b, string $c): string {
    $n = trim((string)($r[$b] ?? ''));
    if (!empty($r[$c])) $n .= '–' . $r[$c];
    return trim((string)($r[$a] ?? '') . ' ' . $n);
}
function enllac(string $v): string {
    $e = h($v);
    if (preg_match('~^https?://~i', $v)) return "<a href=\"$e\" rel=\"noopener noreferrer\" target=\"_blank\">$e</a>";
    if (filter_var($v, FILTER_VALIDATE_EMAIL)) return "<a href=\"mailto:$e\">$e</a>";
    return $e;
}

function cap(string $titol): void { ?>
<!DOCTYPE html>
<html lang="ca">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($titol) ?></title>
<style>
:root{--bg:#fafaf9;--card:#fff;--tx:#18181b;--mut:#71717a;--bd:#e7e5e4;--ac:#18181b;--ac2:#f4f4f5;--ring:rgba(37,99,235,.25)}
@media(prefers-color-scheme:dark){:root{--bg:#0c0c0e;--card:#141417;--tx:#f4f4f5;--mut:#a1a1aa;--bd:#27272a;--ac:#f4f4f5;--ac2:#202024;--ring:rgba(122,162,255,.3)}}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--tx);font:16px/1.55 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;-webkit-font-smoothing:antialiased}
a{color:var(--tx);text-decoration-color:var(--bd);text-underline-offset:3px}
a:hover{text-decoration-color:currentColor}
header{background:transparent}
.w{max-width:1040px;margin:0 auto;padding:2.5rem 1.25rem 1rem}
main.w{padding-top:.5rem;padding-bottom:2rem}
h1{margin:0;font-size:2rem;font-weight:650;letter-spacing:-.025em;line-height:1.2}
h1 a{color:inherit;text-decoration:none}
.sub{color:var(--mut);margin:.4rem 0 0;font-size:.95rem}
form.f{display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:.6rem;margin-top:1.75rem}
input,select,button{font:inherit;font-size:.95rem;padding:.65rem .85rem;border:1px solid var(--bd);border-radius:10px;background:var(--card);color:var(--tx);min-width:0;transition:border-color .15s,box-shadow .15s}
input:focus,select:focus,button:focus-visible{outline:none;border-color:var(--tx);box-shadow:0 0 0 4px var(--ring)}
button{background:var(--ac);color:var(--bg);border-color:var(--ac);font-weight:500;cursor:pointer;padding-inline:1.3rem}
button:hover{opacity:.88}
.res{color:var(--mut);margin:1.75rem 0 1rem;font-size:.9rem}
.res a{color:var(--tx)}
.g{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1rem}
.c{background:var(--card);border:1px solid var(--bd);border-radius:14px;padding:1.2rem 1.25rem;transition:border-color .15s,transform .15s}
.g .c:hover{border-color:var(--mut);transform:translateY(-2px)}
.c h2{font-size:1rem;font-weight:600;line-height:1.35;margin:0 0 .5rem;letter-spacing:-.01em}
.c h2 a{text-decoration:none;color:var(--tx)}
.c p{margin:.1rem 0;color:var(--mut);font-size:.88rem}
.t{display:inline-block;background:var(--ac2);color:var(--mut);border-radius:6px;padding:.12rem .55rem;font-size:.74rem;margin:.65rem .35rem 0 0}
.pg{display:flex;justify-content:space-between;align-items:center;margin:2.25rem 0 .5rem;color:var(--mut);font-size:.9rem}
.pg a{padding:.5rem 1rem;border:1px solid var(--bd);border-radius:10px;text-decoration:none;background:var(--card);color:var(--tx);transition:border-color .15s}
.pg a:hover{border-color:var(--tx)}
dl{display:grid;grid-template-columns:max-content 1fr;gap:.7rem 1.75rem;margin:0}
dt{color:var(--mut);font-size:.8rem;text-transform:uppercase;letter-spacing:.05em;padding-top:.2rem}
dd{margin:0}
pre.h{white-space:pre-wrap;font:inherit;margin:0}
footer{color:var(--mut);font-size:.8rem;text-align:center;padding:2rem 1rem 2.5rem}
.ok{display:inline-block;width:.5rem;height:.5rem;border-radius:50%;background:#22a559;margin-right:.5rem;vertical-align:middle}
@media(max-width:700px){form.f{grid-template-columns:1fr}h1{font-size:1.6rem}.w{padding-top:1.5rem}dl{grid-template-columns:1fr;gap:.15rem}dt{padding-top:.7rem}}
</style>
</head>
<body>
<header><div class="w">
<h1><a href="?">Equipaments d'educació de Barcelona</a></h1>
<p class="sub">Dades obertes de l'Ajuntament de Barcelona, servides des de la base de dades B-N01</p>
<?php }

function peu(string $estat): void { ?>
</div></main>
<footer><span class="ok"></span><?= h($estat) ?></footer>
</body></html>
<?php }

try {
    $pdo = new PDO(
        "mysql:host={$cfg['host']};dbname={$cfg['db']};charset=utf8mb4",
        $cfg['user'], $cfg['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 5]
    );
} catch (PDOException $e) {
    error_log('BD: ' . $e->getMessage());
    http_response_code(503);
    cap('Servei no disponible');
    echo '</div></header><main class="w"><div class="c"><h2>Servei no disponible</h2><p>No s\'ha pogut connectar amb la base de dades. Torna-ho a provar d\'aquí a una estona.</p></div></main></body></html>';
    exit;
}

$tot = $pdo->query('SELECT COUNT(*) f, COUNT(DISTINCT register_id) e FROM equipaments_educacio')->fetch();
$estat = sprintf('Connectat a B-N01 (%s) · %s equipaments · %s files · accés de només lectura',
    $cfg['host'], number_format((int)$tot['e'], 0, ',', '.'), number_format((int)$tot['f'], 0, ',', '.'));

// ---------- Fitxa de detall ----------
$id = (string)($_GET['id'] ?? '');
$q = trim((string)($_GET['q'] ?? ''));
$dist = trim((string)($_GET['d'] ?? ''));
$niv = trim((string)($_GET['n'] ?? ''));
$pag = max(1, (int)($_GET['p'] ?? 1));
$keep = array_filter(['q' => $q, 'd' => $dist, 'n' => $niv, 'p' => $pag > 1 ? $pag : ''], fn($v) => $v !== '');

if (preg_match('/^\d{1,20}$/', $id)) {
    $st = $pdo->prepare('SELECT * FROM equipaments_educacio WHERE register_id = ?');
    $st->execute([$id]);
    $rows = $st->fetchAll();
    if (!$rows) { http_response_code(404); cap('No trobat'); echo '</div></header><main class="w"><div class="c"><h2>Equipament no trobat</h2><p><a href="?' . h(http_build_query($keep)) . '">Tornar al llistat</a></p></div></main></body></html>'; exit; }

    $r = $rows[0]; $contactes = []; $nivells = []; $horari = '';
    foreach ($rows as $x) {
        if (($x['values_value'] ?? '') !== '' && $x['values_value'] !== null)
            $contactes[(string)$x['values_category']][trim($x['values_attribute_name'] . ' ' . $x['values_value'])] = [$x['values_attribute_name'], $x['values_value']];
        if (!empty($x['secondary_filters_name'])) $nivells[$x['secondary_filters_name']] = true;
        if ($horari === '' && !empty($x['timetable'])) $horari = net($x['timetable']);
    }
    cap($r['name']);
    echo '<p class="sub"><a href="?' . h(http_build_query($keep)) . '">← Tornar al llistat</a></p></div></header><main class="w">';
    echo '<div class="c"><h2>' . h($r['name']) . '</h2>';
    foreach (array_keys($nivells) as $n) echo '<span class="t">' . h($n) . '</span>';
    echo '<dl style="margin-top:1rem">';
    echo '<dt>Adreça</dt><dd>' . h(adr($r, 'addresses_road_name', 'addresses_start_street_number', 'addresses_end_street_number')) . ', ' . h($r['addresses_zip_code']) . ' ' . h($r['addresses_town']) . '</dd>';
    echo '<dt>Barri</dt><dd>' . h($r['addresses_neighborhood_name']) . '</dd>';
    echo '<dt>Districte</dt><dd>' . h($r['addresses_district_name']) . '</dd>';
    if (!empty($r['institution_name'])) echo '<dt>Institució</dt><dd>' . h($r['institution_name']) . '</dd>';
    foreach ($contactes as $cat => $items) {
        echo '<dt>' . h($cat) . '</dt><dd>';
        foreach ($items as [$a, $v]) echo h((string)$a) . ' ' . enllac((string)$v) . '<br>';
        echo '</dd>';
    }
    if ($horari !== '') echo '<dt>Horari</dt><dd><pre class="h">' . h($horari) . '</pre></dd>';
    if ($r['geo_epgs_4326_lat'] !== null) {
        $la = (float)$r['geo_epgs_4326_lat']; $lo = (float)$r['geo_epgs_4326_lon'];
        echo '<dt>Mapa</dt><dd><a rel="noopener noreferrer" target="_blank" href="https://www.openstreetmap.org/?mlat=' . $la . '&amp;mlon=' . $lo . '#map=18/' . $la . '/' . $lo . '">Veure a OpenStreetMap</a></dd>';
    }
    echo '<dt>Actualitzat</dt><dd>' . h(dat($r['modified'])) . ' (creat el ' . h(dat($r['created'])) . ')</dd>';
    echo '<dt>Identificador</dt><dd>' . h($r['register_id']) . '</dd></dl></div>';
    peu($estat);
    exit;
}

// ---------- Llistat amb filtres i paginació ----------
$where = ['1=1']; $args = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = '(name LIKE ? OR addresses_road_name LIKE ? OR addresses_neighborhood_name LIKE ?)';
    array_push($args, $like, $like, $like);
}
if ($dist !== '') { $where[] = 'addresses_district_name = ?'; $args[] = $dist; }
if ($niv !== '') { $where[] = 'register_id IN (SELECT register_id FROM equipaments_educacio WHERE secondary_filters_name = ?)'; $args[] = $niv; }
$w = implode(' AND ', $where);

$per = 24;
$st = $pdo->prepare("SELECT COUNT(DISTINCT register_id) FROM equipaments_educacio WHERE $w");
$st->execute($args);
$total = (int)$st->fetchColumn();
$pagines = max(1, (int)ceil($total / $per));
$pag = min($pag, $pagines);
$off = ($pag - 1) * $per;

$st = $pdo->prepare("SELECT register_id, MIN(name) AS nom, MIN(addresses_road_name) AS carrer,
        MIN(addresses_start_street_number) AS num, MIN(addresses_zip_code) AS cp,
        MIN(addresses_neighborhood_name) AS barri, MIN(addresses_district_name) AS districte,
        GROUP_CONCAT(DISTINCT secondary_filters_name ORDER BY secondary_filters_name SEPARATOR '|') AS nivells
    FROM equipaments_educacio WHERE $w GROUP BY register_id ORDER BY nom LIMIT $per OFFSET $off");
$st->execute($args);
$equips = $st->fetchAll();

$districtes = $pdo->query('SELECT DISTINCT addresses_district_name FROM equipaments_educacio WHERE addresses_district_name IS NOT NULL ORDER BY 1')->fetchAll(PDO::FETCH_COLUMN);
$nivells = $pdo->query("SELECT DISTINCT secondary_filters_name FROM equipaments_educacio WHERE secondary_filters_name IS NOT NULL AND secondary_filters_name <> '' ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);

cap("Equipaments d'educació");
?>
<form class="f" method="get">
  <input type="search" name="q" value="<?= h($q) ?>" placeholder="Cerca per nom, carrer o barri" aria-label="Cerca">
  <select name="d" aria-label="Districte"><option value="">Tots els districtes</option>
    <?php foreach ($districtes as $x): ?><option<?= $x === $dist ? ' selected' : '' ?>><?= h($x) ?></option><?php endforeach ?>
  </select>
  <select name="n" aria-label="Nivell"><option value="">Tots els nivells</option>
    <?php foreach ($nivells as $x): ?><option<?= $x === $niv ? ' selected' : '' ?>><?= h($x) ?></option><?php endforeach ?>
  </select>
  <button type="submit">Filtrar</button>
</form>
</div></header>
<main class="w">
<p class="res"><?= number_format($total, 0, ',', '.') ?> equipaments<?= $q . $dist . $niv !== '' ? ' trobats amb aquests filtres · <a href="?">Netejar</a>' : '' ?></p>
<?php if (!$equips): ?>
  <div class="c"><h2>Cap resultat</h2><p>Prova amb una altra cerca o treu algun filtre.</p></div>
<?php else: ?>
<div class="g">
<?php foreach ($equips as $e): ?>
  <article class="c">
    <h2><a href="?<?= h(http_build_query($keep + ['id' => $e['register_id']])) ?>"><?= h($e['nom']) ?></a></h2>
    <p><?= h(trim($e['carrer'] . ' ' . $e['num'])) ?><?= $e['cp'] ? ', ' . h($e['cp']) : '' ?></p>
    <p><?= h($e['barri']) ?> · <?= h($e['districte']) ?></p>
    <?php foreach (array_filter(explode('|', (string)$e['nivells'])) as $n): ?><span class="t"><?= h($n) ?></span><?php endforeach ?>
  </article>
<?php endforeach ?>
</div>
<nav class="pg" aria-label="Paginació">
  <?php $b = array_filter(['q' => $q, 'd' => $dist, 'n' => $niv], fn($v) => $v !== ''); ?>
  <span><?php if ($pag > 1): ?><a href="?<?= h(http_build_query($b + ['p' => $pag - 1])) ?>">← Anterior</a><?php endif ?></span>
  <span>Pàgina <?= $pag ?> de <?= $pagines ?></span>
  <span><?php if ($pag < $pagines): ?><a href="?<?= h(http_build_query($b + ['p' => $pag + 1])) ?>">Següent →</a><?php endif ?></span>
</nav>
<?php endif;
peu($estat);
