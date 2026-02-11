<?php
require 'config.php';
require_any_role(['werkgever', 'super_admin']);
include 'templates/header.php';

$action = $_GET['action'] ?? '';
$isSuper = is_super_admin();
$werkgeverId = current_werkgever_id();

if ($isSuper) {
    $stmt = $db->query("
        SELECT o.*, w.naam AS werkgever_naam
        FROM opdrachtgevers o
        LEFT JOIN users w ON w.id = o.werkgever_id
        ORDER BY o.naam ASC
    ");
    $opdrachtgevers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->prepare("
        SELECT o.*, w.naam AS werkgever_naam
        FROM opdrachtgevers o
        LEFT JOIN users w ON w.id = o.werkgever_id
        WHERE o.werkgever_id = ?
        ORDER BY o.naam ASC
    ");
    $stmt->execute([$werkgeverId]);
    $opdrachtgevers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// POST: Voeg opdrachtgever toe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_klant'])) {
    if ($isSuper) {
        $error = "Super admin kan hier geen klant toevoegen. Voeg toe via werkgever context.";
    } else {
    $naam = trim($_POST['naam'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefoonnummer = trim($_POST['telefoonnummer'] ?? '');
    $adres = trim($_POST['adres'] ?? '');
    
    if (empty($naam)) {
        $error = "Naam is verplicht";
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO opdrachtgevers (werkgever_id, naam, email, telefoonnummer, adres) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$werkgeverId, $naam, $email, $telefoonnummer, $adres]);
            $success = "Klant '{$naam}' toegevoegd";
            header("Refresh:1");
        } catch (Exception $e) {
            $error = "Fout: " . $e->getMessage();
        }
    }
    }
}

// POST: Verwijder opdrachtgever
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_klant'])) {
    $klant_id = (int)$_POST['klant_id'];
    try {
        if ($isSuper) {
            $stmt = $db->prepare("DELETE FROM opdrachtgevers WHERE id = ?");
            $stmt->execute([$klant_id]);
        } else {
            $stmt = $db->prepare("DELETE FROM opdrachtgevers WHERE id = ? AND werkgever_id = ?");
            $stmt->execute([$klant_id, $werkgeverId]);
        }
        $success = "Klant verwijderd";
        header("Refresh:1");
    } catch (Exception $e) {
        $error = "Fout: " . $e->getMessage();
    }
}
?>

<div class="card">
    <h2>Opdrachtgevers & Klanten</h2>
    <p class="muted">Beheer klanten en opdrachtgevers voor klussen</p>
</div>

<?php if (isset($success)): ?>
    <div class="success"><?= h($success) ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="error"><?= h($error) ?></div>
<?php endif; ?>

<!-- Klant Toevoegen -->
<?php if ($action !== 'list' && !$isSuper): ?>
<div class="card">
    <h3>+ Nieuwe Klant Toevoegen</h3>
    <form method="post">
        <label>Bedrijfsnaam *</label>
        <input type="text" name="naam" placeholder="Bijv. Elektra BV" required>
        
        <label>Email</label>
        <input type="email" name="email" placeholder="info@bedrijf.nl">
        
        <label>Telefoonnummer</label>
        <input type="tel" name="telefoonnummer" placeholder="+31 20 123 4567">
        
        <label>Adres</label>
        <input type="text" name="adres" placeholder="Straat 123, 1234 AB Amsterdam">
        
        <button type="submit" name="add_klant" class="btn">Toevoegen</button>
    </form>
</div>
<?php endif; ?>

<!-- Klanten Overzicht -->
<div class="card">
    <h3>Alle Opdrachtgevers (<?= count($opdrachtgevers) ?>)</h3>
    
    <?php if (empty($opdrachtgevers)): ?>
        <p class="muted">Nog geen opdrachtgevers aangemaakt.</p>
    <?php else: ?>
        <div style="overflow:auto;margin-top:12px;">
            <table>
                <thead>
                    <tr>
                        <th>Bedrijf</th>
                        <th>Email</th>
                        <th>Telefoon</th>
                        <th>Adres</th>
                        <?php if ($isSuper): ?><th>Werkgever</th><?php endif; ?>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($opdrachtgevers as $og): ?>
                    <tr>
                        <td><?= h($og['naam']) ?></td>
                        <td><?= h($og['email'] ?? '-') ?></td>
                        <td><?= h($og['telefoonnummer'] ?? '-') ?></td>
                        <td><?= h($og['adres'] ?? '-') ?></td>
                        <?php if ($isSuper): ?><td><?= h($og['werkgever_naam'] ?? '-') ?></td><?php endif; ?>
                        <td style="text-align:right;">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="klant_id" value="<?= (int)$og['id'] ?>">
                                <button type="submit" name="delete_klant" class="btn ghost" style="padding:6px 8px;font-size:12px;margin:0;background:#fee2e2;color:#991b1b;" onclick="return confirm('Verwijder klant?')">Verwijder</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
