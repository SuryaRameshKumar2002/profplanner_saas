<?php
require 'config.php';
require_any_role(['werkgever', 'super_admin']);
include 'templates/header.php';

$action = $_GET['action'] ?? '';
$isSuper = is_super_admin();
$werkgeverId = current_werkgever_id();

if ($isSuper) {
    $stmt = $db->query("SELECT b.*, w.naam AS werkgever_naam FROM buses b LEFT JOIN users w ON w.id = b.werkgever_id ORDER BY b.naam ASC");
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->prepare("SELECT b.*, w.naam AS werkgever_naam FROM buses b LEFT JOIN users w ON w.id = b.werkgever_id WHERE b.werkgever_id = ? ORDER BY b.naam ASC");
    $stmt->execute([$werkgeverId]);
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Haal alle werknemers op
if ($isSuper) {
    $stmt = $db->query("SELECT u.*, r.naam AS rol_naam FROM users u JOIN rollen r ON r.id = u.rol_id WHERE r.naam = 'werknemer' ORDER BY u.naam");
    $werknemers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $db->prepare("SELECT u.*, r.naam AS rol_naam FROM users u JOIN rollen r ON r.id = u.rol_id WHERE r.naam = 'werknemer' AND u.werkgever_id = ? ORDER BY u.naam");
    $stmt->execute([$werkgeverId]);
    $werknemers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// POST: Voeg bus toe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bus'])) {
    if ($isSuper) {
        $error = "Super admin kan hier geen bus toevoegen zonder werkgever context.";
    } else {
    $naam = trim($_POST['naam'] ?? '');
    $omschrijving = trim($_POST['omschrijving'] ?? '');
    $kleur = $_POST['kleur'] ?? '#16a34a';
    
    if (empty($naam)) {
        $error = "Bus naam is verplicht";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO buses (werkgever_id, naam, omschrijving, kleur) VALUES (?, ?, ?, ?)");
            $stmt->execute([$werkgeverId, $naam, $omschrijving, $kleur]);
            $success = "Bus '{$naam}' toegevoegd";
            header("Refresh:1");
        } catch (Exception $e) {
            $error = "Fout bij toevoegen: " . $e->getMessage();
        }
    }}
}

// POST: Werknemers aan bus toewijzen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_workers'])) {
    $bus_id = (int)$_POST['bus_id'];
    $werknemers_ids = $_POST['werknemers_ids'] ?? [];
    
    try {
        // Verwijder oude toewijzingen
        if (!$isSuper) {
            $chk = $db->prepare("SELECT id FROM buses WHERE id = ? AND werkgever_id = ?");
            $chk->execute([$bus_id, $werkgeverId]);
            if (!$chk->fetchColumn()) {
                throw new RuntimeException("Geen toegang tot deze bus.");
            }
        }

        $stmt = $db->prepare("DELETE FROM werknemers_buses WHERE bus_id = ?");
        $stmt->execute([$bus_id]);
        
        // Voeg nieuwe toe
        $stmt = $db->prepare("INSERT INTO werknemers_buses (user_id, bus_id) VALUES (?, ?)");
        foreach ($werknemers_ids as $user_id) {
            $uid = (int)$user_id;
            if (!$isSuper) {
                $ownStmt = $db->prepare("SELECT id FROM users WHERE id = ? AND werkgever_id = ?");
                $ownStmt->execute([$uid, $werkgeverId]);
                if (!$ownStmt->fetchColumn()) {
                    continue;
                }
            }
            $stmt->execute([$uid, $bus_id]);
        }
        $success = "Werknemers aan bus toegewezen";
        header("Refresh:1");
    } catch (Exception $e) {
        $error = "Fout: " . $e->getMessage();
    }
}

// POST: Verwijder bus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_bus'])) {
    $bus_id = (int)$_POST['bus_id'];
    try {
        if ($isSuper) {
            $stmt = $db->prepare("DELETE FROM buses WHERE id = ?");
            $stmt->execute([$bus_id]);
        } else {
            $stmt = $db->prepare("DELETE FROM buses WHERE id = ? AND werkgever_id = ?");
            $stmt->execute([$bus_id, $werkgeverId]);
        }
        $success = "Bus verwijderd";
        header("Refresh:1");
    } catch (Exception $e) {
        $error = "Fout: " . $e->getMessage();
    }
}

// Haal werknemers voor een bus op
$bus_werknemers = [];
if (!empty($action) && $action === 'assign') {
    $bus_id = (int)($_GET['bus_id'] ?? 0);
    if ($bus_id > 0) {
        $stmt = $db->prepare("SELECT user_id FROM werknemers_buses WHERE bus_id = ?");
        $stmt->execute([$bus_id]);
        $bus_werknemers = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'user_id');
    }
}
?>

<div class="card">
    <h2>Bus & Team Beheer</h2>
    <p class="muted">Beheer bussen/teams (HV01, HV02, DVI) en wijs werknemers toe</p>
</div>

<?php if (isset($success)): ?>
    <div class="success"><?= h($success) ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="error"><?= h($error) ?></div>
<?php endif; ?>

<!-- Bus Toevoegen -->
<?php if ($action !== 'assign'): ?>
<?php if (!$isSuper): ?>
<div class="card">
    <h3>+ Nieuwe Bus Toevoegen</h3>
    <form method="post">
        <label>Bus Naam (bijv. HV01, HV02)</label>
        <input type="text" name="naam" placeholder="HV01" required>
        
        <label>Omschrijving</label>
        <textarea name="omschrijving" placeholder="Bijv. Hoog Voltage Team 1"></textarea>
        
        <label>Kleur (voor planning)</label>
        <input type="color" name="kleur" value="#16a34a">
        
        <button type="submit" name="add_bus" class="btn">Toevoegen</button>
    </form>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Buses Overzicht -->
<div class="card">
    <h3>Beschikbare Bussen</h3>
    <?php if (empty($buses)): ?>
        <p class="muted">Nog geen bussen aangemaakt.</p>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px;margin-top:12px;">
            <?php foreach ($buses as $bus): ?>
                <div style="border:1px solid #e5e7eb;border-radius:8px;padding:16px;background:#fff;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:20px;height:20px;background:<?= h($bus['kleur']) ?>;border-radius:4px;"></div>
                        <h4 style="margin:0;font-size:16px;"><?= h($bus['naam']) ?></h4>
                    </div>
                    <p style="margin:8px 0 12px;font-size:13px;color:#64748b;"><?= h($bus['omschrijving'] ?? '') ?></p>
                    <?php if ($isSuper): ?>
                        <div class="muted">Werkgever: <?= h($bus['werkgever_naam'] ?? '-') ?></div>
                    <?php endif; ?>
                    
                    <?php
                    // Toon werknemers in deze bus
                    $stmt = $db->prepare("
                        SELECT u.id, u.naam FROM werknemers_buses wb
                        JOIN users u ON u.id = wb.user_id
                        WHERE wb.bus_id = ?
                        ORDER BY u.naam
                    ");
                    $stmt->execute([$bus['id']]);
                    $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <div style="margin-bottom:12px;">
                        <small style="color:#64748b;font-weight:600;">Werknemers (<?= count($workers) ?>)</small>
                        <div style="margin-top:4px;display:flex;flex-wrap:wrap;gap:4px;">
                            <?php if (empty($workers)): ?>
                                <span class="badge" style="background:#f3f4f6;">Geen werknemers</span>
                            <?php else: ?>
                                <?php foreach ($workers as $w): ?>
                                    <span class="badge"><?= h($w['naam']) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div style="display:flex;gap:8px;">
                        <a href="buses_management.php?action=assign&bus_id=<?= (int)$bus['id'] ?>" class="btn ghost" style="flex:1;text-align:center;margin:0;padding:8px 12px;font-size:13px;">Werknemers</a>
                        <form method="post" style="flex:1;margin:0;">
                            <input type="hidden" name="bus_id" value="<?= (int)$bus['id'] ?>">
                            <button type="submit" name="delete_bus" class="btn ghost" style="width:100%;margin:0;padding:8px 12px;font-size:13px;background:#fee2e2;color:#991b1b;" onclick="return confirm('Verwijder bus?')">Verwijderen</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Werknemers Toewijzen -->
<?php if ($action === 'assign' && isset($_GET['bus_id'])): ?>
    <?php if ($isSuper): ?>
      <div class="notice">Toewijzen via deze pagina is alleen toegestaan binnen werkgever context.</div>
    <?php else: ?>
    <?php
    $bus_id = (int)$_GET['bus_id'];
    $bus = null;
    foreach ($buses as $b) {
        if ($b['id'] == $bus_id) {
            $bus = $b;
            break;
        }
    }
    if ($bus):
    ?>
    <div class="card">
        <h3>Werknemers Toewijzen aan Bus: <?= h($bus['naam']) ?></h3>
        <form method="post">
            <input type="hidden" name="bus_id" value="<?= (int)$bus_id ?>">
            
            <label style="margin-top:12px;">Selecteer werknemers</label>
            <div style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:8px;padding:12px;max-height:300px;overflow-y:auto;">
                <?php if (empty($werknemers)): ?>
                    <p class="muted">Geen werknemers beschikbaar</p>
                <?php else: ?>
                    <?php foreach ($werknemers as $w): ?>
                        <label style="display:flex;align-items:center;margin:8px 0;font-weight:normal;cursor:pointer;">
                            <input type="checkbox" name="werknemers_ids[]" value="<?= (int)$w['id'] ?>" 
                                <?= in_array($w['id'], $bus_werknemers) ? 'checked' : '' ?> 
                                style="width:auto;margin:0 8px 0 0;padding:0;cursor:pointer;">
                            <?= h($w['naam']) ?>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <button type="submit" name="assign_workers" class="btn" style="margin-top:12px;">Opslaan</button>
            <a href="buses_management.php" class="btn ghost" style="margin-left:8px;">Annuleren</a>
        </form>
    </div>
    <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<?php include 'templates/footer.php'; ?>
