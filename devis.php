<?php
// JPV ELEC – envoi du formulaire de demande de devis (page Contact) par e-mail.
// Fonctionne sur un hébergement OVH qui accepte le PHP.

$destinataire = 'jn.hollard@jpv-elec.fr';
// Adresse d'expédition : doit appartenir au domaine relié à l'hébergement (jpv-elec.com), sinon OVH bloque l'envoi.
$expediteur   = 'site@jpv-elec.com';

function retour($etat) {
    header('Location: contact.html?envoi=' . $etat . '#devis', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    retour('erreur');
}

// Piège à robots : ce champ est invisible pour les visiteurs.
if (!empty($_POST['site-web'])) {
    retour('ok');
}

// Récupère un champ, sans retours à la ligne pour les champs courts (évite l'injection d'en-têtes).
function champ($nom, $multiligne = false) {
    $v = trim((string)($_POST[$nom] ?? ''));
    if (!$multiligne) {
        $v = str_replace(["\r", "\n"], ' ', $v);
    }
    return mb_substr($v, 0, $multiligne ? 5000 : 200);
}

$nom       = champ('nom');
$societe   = champ('societe');
$telephone = champ('telephone');
$email     = champ('email');
$message   = champ('message', true);

if ($nom === '' || $societe === '' || $telephone === '' || $message === ''
    || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    retour('erreur');
}

$sujet = 'Demande de devis – ' . $societe;
$corps = "Nouvelle demande de devis reçue depuis le site JPV ELEC.\n\n"
       . "Nom : $nom\n"
       . "Société : $societe\n"
       . "Téléphone : $telephone\n"
       . "E-mail : $email\n\n"
       . "Projet :\n$message\n\n"
       . "---\nPour répondre au client, utilisez simplement « Répondre ».\n";

$entetes = [
    'From: ' . mb_encode_mimeheader('Site JPV ELEC', 'UTF-8') . " <$expediteur>",
    "Reply-To: $email",
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
];

$envoye = mail(
    $destinataire,
    mb_encode_mimeheader($sujet, 'UTF-8'),
    $corps,
    implode("\r\n", $entetes),
    '-f' . $expediteur
);

retour($envoye ? 'ok' : 'erreur');
