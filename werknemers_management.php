<?php
require 'config.php';
require_any_role(['werkgever', 'super_admin']);
include 'templates/header.php';

$isSuper = is_super_admin();
$werkgeverId = current_werkgever_id();

if ($isSuper) {
    $stmt = $db->query("
        SELECT u.*, r.naam AS rol_naam, wg.naam AS werkgever_naam
        FROM users u
        JOIN rollen r ON r.id = u.rol_id
        LEFT JOIN users wg ON wg.id = u.werkgever_id
        WHERE r.naam = 'werknemer'
        ORDER BY u.naam
    ");
    $werknemers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->prepare("
        SELECT u.*, r.naam AS rol_naam, wg.naam AS werkgever_naam
        FROM users u
        JOIN rollen r ON r.id = u.rol_id
        LEFT JOIN users wg ON wg.id = u.werkgever_id
        WHERE r.naam = 'werknemer' AND u.werkgever_id = ?
        ORDER BY u.naam
    ");
    $stmt->execute([$werkgeverId]);
    $werknemers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<?php $createdId = (int)($_GET['created'] ?? 0); ?>

<div class="card">
    <h2>Werknemers Beheer</h2>
    <p class="muted">Overzicht en beheer van alle werknemers</p>
</div>
<?php if ($createdId > 0): ?>
    <div class="success">Werknemer aangemaakt. Je kunt nu direct via Share notificeren.</div>
<?php endif; ?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <h3>Alle Werknemers (<?= count($werknemers) ?>)</h3>
        <?php if (!$isSuper): ?>
            <a href="werknemer_toevoegen.php" class="btn">+ Werknemer Toevoegen</a>
        <?php endif; ?>
    </div>
    
    <?php if (empty($werknemers)): ?>
        <p class="muted">Geen werknemers aangemaakt.</p>
    <?php else: ?>
        <div style="overflow:auto;margin-top:12px;">
            <table>
                <thead>
                    <tr>
                        <th>Naam</th>
                        <th>Email</th>
                        <th>Telefoonnummer</th>
                        <?php if ($isSuper): ?><th>Werkgever</th><?php endif; ?>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($werknemers as $w): ?>
                    <tr>
                        <td><?= h($w['naam']) ?></td>
                        <td><?= h($w['email']) ?></td>
                        <td><?= h($w['telefoonnummer'] ?? '-') ?></td>
                        <?php if ($isSuper): ?><td><?= h($w['werkgever_naam'] ?? '-') ?></td><?php endif; ?>
                        <td><span class="badge green">Actief</span></td>
                        <td style="text-align:right;">
                            <?php if (!$isSuper): ?>
                                <a href="werknemer_bewerk.php?id=<?= (int)$w['id'] ?>" class="btn ghost" style="padding:6px 8px;font-size:12px;margin:0;">Bewerk</a>
                                <a href="share.php?type=employee&id=<?= (int)$w['id'] ?>" class="btn ghost" style="padding:6px 8px;font-size:12px;margin:0;">Share</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
