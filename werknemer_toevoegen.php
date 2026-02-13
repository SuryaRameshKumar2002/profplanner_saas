<?php
require 'config.php';
require_role('werkgever');
include 'templates/header.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $naam = trim($_POST['naam'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefoonnummer = trim($_POST['telefoonnummer'] ?? '');
    $wachtwoord = $_POST['wachtwoord'] ?? '';

    if ($naam === '' || $email === '' || $wachtwoord === '') {
        $error = 'Naam, email en wachtwoord zijn verplicht.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Voer een geldig emailadres in.';
    } elseif (strlen($wachtwoord) < 6) {
        $error = 'Wachtwoord moet minimaal 6 tekens bevatten.';
    } else {
        try {
            $rolStmt = $db->prepare("SELECT id FROM rollen WHERE naam = 'werknemer' LIMIT 1");
            $rolStmt->execute();
            $rolId = $rolStmt->fetchColumn();

            if (!$rolId) {
                throw new RuntimeException("Rol 'werknemer' ontbreekt in de database.");
            }

            $stmt = $db->prepare('INSERT INTO users (naam, email, wachtwoord, rol_id, werkgever_id, telefoonnummer) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $naam,
                $email,
                password_hash($wachtwoord, PASSWORD_DEFAULT),
                (int)$rolId,
                (int)$_SESSION['user']['id'],
                ($telefoonnummer !== '' ? $telefoonnummer : null)
            ]);
            $newUserId = (int)$db->lastInsertId();
            notify_for_scope(
                $db,
                'employee_created',
                'Nieuwe werknemer aangemaakt',
                'Account voor ' . $naam . ' is aangemaakt.',
                'werknemers_management.php',
                ['recipient_ids' => [$newUserId]]
            );

            header('Location: werknemers_management.php?created=' . $newUserId);
            exit;
        } catch (Throwable $e) {
            $error = 'Opslaan mislukt: ' . $e->getMessage();
        }
    }
}
?>

<div class="card">
    <h2>Werknemer Toevoegen</h2>
    <p class="muted">Maak een nieuw werknemer-account aan.</p>
</div>

<?php if ($success): ?>
    <div class="success"><?= h($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error"><?= h($error) ?></div>
<?php endif; ?>

<div class="card">
    <form method="post">
        <label>Naam *</label>
        <input type="text" name="naam" required value="<?= h($_POST['naam'] ?? '') ?>">

        <label>Email *</label>
        <input type="email" name="email" required value="<?= h($_POST['email'] ?? '') ?>">

        <label>Telefoonnummer</label>
        <input type="text" name="telefoonnummer" value="<?= h($_POST['telefoonnummer'] ?? '') ?>">

        <label>Tijdelijk wachtwoord *</label>
        <input type="password" name="wachtwoord" minlength="6" required>

        <button class="btn" type="submit">Aanmaken</button>
        <a class="btn ghost" href="werknemers_management.php" style="margin-left:8px;">Annuleren</a>
    </form>
</div>

<?php include 'templates/footer.php'; ?>
