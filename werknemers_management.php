<?php
require 'config.php';
require_role('werkgever');
include 'templates/header.php';

// Haal alle werknemers op
$stmt = $db->query("
    SELECT u.*, r.naam AS rol_naam FROM users u 
    JOIN rollen r ON r.id = u.rol_id 
    WHERE r.naam = 'werknemer'
    ORDER BY u.naam
");
$werknemers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <h2>Werknemers Beheer</h2>
    <p class="muted">Overzicht en beheer van alle werknemers</p>
</div>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <h3>Alle Werknemers (<?= count($werknemers) ?>)</h3>
        <a href="werknemer_toevoegen.php" class="btn">+ Werknemer Toevoegen</a>
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
                        <td><span class="badge green">Actief</span></td>
                        <td style="text-align:right;">
                            <a href="werknemer_bewerk.php?id=<?= (int)$w['id'] ?>" class="btn ghost" style="padding:6px 8px;font-size:12px;margin:0;">Bewerk</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
