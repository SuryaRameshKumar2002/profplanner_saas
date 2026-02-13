<?php
require 'config.php';
require_login();
include 'templates/header.php';

$user = $_SESSION['user'];
$isSuper = is_super_admin();
$werkgeverId = current_werkgever_id();

// Bepaal huidige week
$week = isset($_GET['week']) ? (int)$_GET['week'] : 0;
$current_date = new DateTime();
if ($week != 0) {
    $current_date->modify(($week > 0 ? '+' : '') . ($week * 7) . ' days');
}

// Maandag van de week
$monday = clone $current_date;
$monday->modify('Monday this week');

// Volgende maandag
$next_monday = clone $monday;
$next_monday->modify('+7 days');

// Vorige maandag
$prev_monday = clone $monday;
$prev_monday->modify('-7 days');

// Query roosters voor deze week
if ($user['rol'] === 'werknemer') {
    // Werknemer: alleen eigen klussen
    $stmt = $db->prepare("
        SELECT r.*, b.naam AS bus_naam, b.kleur, b.image_path, o.naam AS opdrachtgever_naam
        FROM roosters r
        LEFT JOIN buses b ON b.id = r.bus_id
        LEFT JOIN opdrachtgevers o ON o.id = r.opdrachtgever_id
        WHERE r.werknemer_id = ? 
        AND DATE(r.datum) >= ? 
        AND DATE(r.datum) < ?
        ORDER BY r.bus_id, r.datum, r.tijd
    ");
    $stmt->execute([$user['id'], $monday->format('Y-m-d'), $next_monday->format('Y-m-d')]);
} elseif ($isSuper) {
    // Super admin: alle werkgevers
    $stmt = $db->prepare("
        SELECT r.*, b.naam AS bus_naam, b.kleur, b.image_path, o.naam AS opdrachtgever_naam, u.naam AS werknemer_naam, wg.naam AS werkgever_naam
        FROM roosters r
        LEFT JOIN buses b ON b.id = r.bus_id
        LEFT JOIN opdrachtgevers o ON o.id = r.opdrachtgever_id
        LEFT JOIN users u ON u.id = r.werknemer_id
        LEFT JOIN users wg ON wg.id = r.werkgever_id
        WHERE DATE(r.datum) >= ? 
        AND DATE(r.datum) < ?
        ORDER BY r.bus_id, r.datum, r.tijd
    ");
    $stmt->execute([$monday->format('Y-m-d'), $next_monday->format('Y-m-d')]);
} else {
    // Werkgever: alle klussen voor deze week
    $stmt = $db->prepare("
        SELECT r.*, b.naam AS bus_naam, b.kleur, b.image_path, o.naam AS opdrachtgever_naam, u.naam AS werknemer_naam
        FROM roosters r
        LEFT JOIN buses b ON b.id = r.bus_id
        LEFT JOIN opdrachtgevers o ON o.id = r.opdrachtgever_id
        LEFT JOIN users u ON u.id = r.werknemer_id
        WHERE r.werkgever_id = ?
        AND DATE(r.datum) >= ? 
        AND DATE(r.datum) < ?
        ORDER BY r.bus_id, r.datum, r.tijd
    ");
    $stmt->execute([$werkgeverId, $monday->format('Y-m-d'), $next_monday->format('Y-m-d')]);
}
$roosters = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Groepeer per bus
$buses_planning = [];
if ($isSuper) {
    $stmt = $db->query("SELECT * FROM buses WHERE actief = TRUE ORDER BY naam");
} else {
    $stmt = $db->prepare("SELECT * FROM buses WHERE actief = TRUE AND werkgever_id = ? ORDER BY naam");
    $stmt->execute([$werkgeverId]);
}
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $bus) {
    $buses_planning[$bus['id']] = [
        'info' => $bus,
        'roosters' => []
    ];
}

foreach ($roosters as $rooster) {
    $bus_id = $rooster['bus_id'] ?? 0;
    if (!isset($buses_planning[$bus_id])) {
        $buses_planning[$bus_id] = [
            'info' => ['id' => $bus_id, 'naam' => 'Geen Bus', 'kleur' => '#9ca3af'],
            'roosters' => []
        ];
    }
    $buses_planning[$bus_id]['roosters'][] = $rooster;
}
?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <div>
            <h2>Weekplanning</h2>
            <p class="muted">Week van <?= $monday->format('d M Y') ?> tot <?= $next_monday->modify('-1 day')->format('d M Y') ?></p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="planner_weekly.php?week=<?= $week - 1 ?>" class="btn ghost">← Vorige week</a>
            <a href="planner_weekly.php?week=0" class="btn ghost">Vandaag</a>
            <a href="planner_weekly.php?week=<?= $week + 1 ?>" class="btn ghost">Volgende week →</a>
        </div>
    </div>
</div>

<!-- Per bus -->
<?php foreach ($buses_planning as $bus_group): ?>
    <?php if (count($bus_group['roosters']) > 0 || $user['rol'] === 'werkgever'): ?>
    <div class="card" style="border-left:4px solid <?= h($bus_group['info']['kleur']) ?>;">
        <h3 style="color:<?= h($bus_group['info']['kleur']) ?>;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <?php if (!empty($bus_group['info']['image_path'])): ?>
                <img src="<?= h($bus_group['info']['image_path']) ?>" alt="<?= h($bus_group['info']['naam']) ?>" class="bus-thumb">
            <?php endif; ?>
            Bus: <?= h($bus_group['info']['naam']) ?>
            <span class="badge" style="margin-left:8px;"><?= count($bus_group['roosters']) ?> klussen</span>
        </h3>
        
        <?php if (empty($bus_group['roosters'])): ?>
            <p class="muted">Geen klussen deze week</p>
        <?php else: ?>
            <div style="overflow:auto;margin-top:12px;">
                <table>
                    <thead>
                        <tr>
                            <th style="width:100px;">Datum</th>
                            <th style="width:80px;">Tijd</th>
                            <th>Klus</th>
                            <th>Locatie</th>
                            <?php if ($user['rol'] === 'werkgever'): ?>
                                <th>Werknemer</th>
                            <?php elseif ($isSuper): ?>
                                <th>Werknemer</th>
                                <th>Werkgever</th>
                            <?php endif; ?>
                            <th>Status</th>
                            <th style="width:80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bus_group['roosters'] as $r): 
                            $status = strtolower($r['status'] ?? 'gepland');
                            $badge_class = 'badge';
                            if ($status === 'afgerond') $badge_class .= ' green';
                            elseif ($status === 'afgebroken') $badge_class .= ' red';
                            elseif ($status === 'verzet') $badge_class .= ' yellow';
                        ?>
                        <tr>
                            <td><?= h($r['datum']) ?></td>
                            <td><?= h($r['tijd']) ?></td>
                            <td><?= h($r['titel'] ?? '') ?></td>
                            <td><?= h($r['locatie'] ?? '') ?></td>
                            <?php if ($user['rol'] === 'werkgever'): ?>
                                <td><?= h($r['werknemer_naam'] ?? '-') ?></td>
                            <?php elseif ($isSuper): ?>
                                <td><?= h($r['werknemer_naam'] ?? '-') ?></td>
                                <td><?= h($r['werkgever_naam'] ?? '-') ?></td>
                            <?php endif; ?>
                            <td><span class="<?= $badge_class ?>"><?= h($r['status'] ?? 'gepland') ?></span></td>
                            <td style="text-align:center;">
                                <a href="rooster_detail.php?id=<?= (int)$r['id'] ?>" class="btn ghost" style="padding:6px 8px;font-size:12px;text-align:center;margin:0;">Info</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php include 'templates/footer.php'; ?>
