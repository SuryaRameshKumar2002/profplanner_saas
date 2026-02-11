<?php
require 'config.php';
require_login();
include 'templates/header.php';

$user = $_SESSION['user'];
$success = $error = '';

// POST: Update profiel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $naam = trim($_POST['naam'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefoonnummer = trim($_POST['telefoonnummer'] ?? '');
    
    if (empty($naam) || empty($email)) {
        $error = "Naam en email zijn verplicht";
    } else {
        try {
            $stmt = $db->prepare("
                UPDATE users SET naam = ?, email = ?, telefoonnummer = ? 
                WHERE id = ?
            ");
            $stmt->execute([$naam, $email, $telefoonnummer, $user['id']]);
            
            // Update sessie
            $_SESSION['user']['naam'] = $naam;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['telefoonnummer'] = $telefoonnummer;
            
            $success = "Profiel bijgewerkt";
        } catch (Exception $e) {
            $error = "Fout: " . $e->getMessage();
        }
    }
}

// POST: Wachtwoord wijzigen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Alle velden zijn verplicht";
    } elseif ($new_password !== $confirm_password) {
        $error = "Wachtwoorden komen niet overeen";
    } elseif (!password_verify($old_password, $user['wachtwoord'])) {
        $error = "Oud wachtwoord is incorrect";
    } elseif (strlen($new_password) < 6) {
        $error = "Wachtwoord moet minstens 6 karakters zijn";
    } else {
        try {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET wachtwoord = ? WHERE id = ?");
            $stmt->execute([$hashed, $user['id']]);
            $success = "Wachtwoord gewijzigd";
        } catch (Exception $e) {
            $error = "Fout: " . $e->getMessage();
        }
    }
}
?>

<div class="card">
    <h2>Mijn Profiel</h2>
    <p class="muted">Beheer je accountgegevens</p>
</div>

<?php if ($success): ?>
    <div class="success"><?= h($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="error"><?= h($error) ?></div>
<?php endif; ?>

<!-- Profiel Informatie -->
<div class="card">
    <h3>Profielgegevens</h3>
    <form method="post">
        <label>Volledige Naam</label>
        <input type="text" name="naam" value="<?= h($user['naam']) ?>" required>
        
        <label>Email</label>
        <input type="email" name="email" value="<?= h($user['email']) ?>" required>
        
        <label>Telefoonnummer</label>
        <input type="tel" name="telefoonnummer" value="<?= h($user['telefoonnummer'] ?? '') ?>">
        
        <div style="background:#f3f4f6;padding:12px;border-radius:8px;margin:12px 0;font-size:13px;">
            <strong>Rol:</strong> <?= h(ucfirst($user['rol'])) ?><br>
            <strong>Lid sinds:</strong> <?= h($user['gemaakt_op'] ?? 'Onbekend') ?>
        </div>
        
        <button type="submit" name="update_profile" class="btn">Opslaan</button>
    </form>
</div>

<!-- Wachtwoord Wijzigen -->
<div class="card">
    <h3>Wachtwoord Wijzigen</h3>
    <form method="post">
        <label>Huidig Wachtwoord</label>
        <input type="password" name="old_password" required>
        
        <label>Nieuw Wachtwoord</label>
        <input type="password" name="new_password" required>
        
        <label>Wachtwoord Herhalen</label>
        <input type="password" name="confirm_password" required>
        
        <button type="submit" name="change_password" class="btn">Wijzigen</button>
    </form>
</div>

<?php include 'templates/footer.php'; ?>
