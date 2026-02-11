<?php
require 'config.php';
require_role('werkgever');
include 'templates/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo "<div class='error'>Ongeldige werknemer.</div>";
    include 'templates/footer.php';
    exit;
}

$stmt = $db->prepare("SELECT u.*, r.naam AS rol_naam FROM users u JOIN rollen r ON r.id = u.rol_id WHERE u.id = ? LIMIT 1");
$stmt->execute([$id]);
$werknemer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$werknemer || ($werknemer['rol_naam'] ?? '') !== 'werknemer') {
    echo "<div class='error'>Werknemer niet gevonden.</div>";
    include 'templates/footer.php';
    exit;
}

if ((int)($werknemer['werkgever_id'] ?? 0) !== (int)($_SESSION['user']['id'] ?? 0)) {
    echo "<div class='error'>Geen toegang tot deze werknemer.</div>";
    include 'templates/footer.php';
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $naam = trim($_POST['naam'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefoonnummer = trim($_POST['telefoonnummer'] ?? '');
    $nieuwWachtwoord = $_POST['nieuw_wachtwoord'] ?? '';

    if ($naam === '' || $email === '') {
        $error = 'Naam en email zijn verplicht.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Voer een geldig emailadres in.';
    } else {
        try {
            if ($nieuwWachtwoord !== '') {
                if (strlen($nieuwWachtwoord) < 6) {
                    throw new RuntimeException('Nieuw wachtwoord moet minimaal 6 tekens bevatten.');
                }
                $update = $db->prepare('UPDATE users SET naam = ?, email = ?, telefoonnummer = ?, wachtwoord = ? WHERE id = ?');
                $update->execute([
                    $naam,
                    $email,
                    ($telefoonnummer !== '' ? $telefoonnummer : null),
                    password_hash($nieuwWachtwoord, PASSWORD_DEFAULT),
                    $id
                ]);
            } else {
                $update = $db->prepare('UPDATE users SET naam = ?, email = ?, telefoonnummer = ? WHERE id = ?');
                $update->execute([
                    $naam,
                    $email,
                    ($telefoonnummer !== '' ? $telefoonnummer : null),
                    $id
                ]);
            }

            header('Location: werknemers_management.php');
            exit;
        } catch (Throwable $e) {
            $error = 'Opslaan mislukt: ' . $e->getMessage();
        }
    }

    $werknemer['naam'] = $naam;
    $werknemer['email'] = $email;
    $werknemer['telefoonnummer'] = $telefoonnummer;
}
?>

<div class="card">
    <h2>Werknemer Bewerken</h2>
    <p class="muted">Pas de gegevens van de werknemer aan.</p>
</div>

<?php if ($error): ?>
    <div class="error"><?= h($error) ?></div>
<?php endif; ?>

<div class="card">
    <form method="post">
        <label>Naam *</label>
        <input type="text" name="naam" required value="<?= h($werknemer['naam'] ?? '') ?>">

        <label>Email *</label>
        <input type="email" name="email" required value="<?= h($werknemer['email'] ?? '') ?>">

        <label>Telefoonnummer</label>
        <input type="text" name="telefoonnummer" value="<?= h($werknemer['telefoonnummer'] ?? '') ?>">

        <label>Nieuw wachtwoord (optioneel)</label>
        <input type="password" name="nieuw_wachtwoord" minlength="6" placeholder="Leeg laten om niet te wijzigen">

        <button class="btn" type="submit">Opslaan</button>
        <a class="btn ghost" href="werknemers_management.php" style="margin-left:8px;">Annuleren</a>
    </form>
</div>

<?php include 'templates/footer.php'; ?>
