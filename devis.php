<?php
// JPV ELEC – envoi du formulaire de demande de devis (page Contact) par e-mail.
// Fonctionne sur un hébergement OVH qui accepte le PHP (compatible PHP 7.2 et plus).

$destinataire = 'jn.hollard@jpv-elec.fr';
// Adresse d'expédition : doit appartenir au domaine relié à l'hébergement (jpv-elec.com), sinon OVH bloque l'envoi.
$expediteur   = 'site@jpv-elec.com';

// Domaines autorisés à envoyer le formulaire.
$domaines_site = ['jpv-elec.com', 'www.jpv-elec.com', 'localhost'];

// Les messages bloqués sont notés ici, hors du dossier www (donc invisibles depuis internet).
$journal_spam = dirname(__DIR__) . '/devis-spam.log';

function retour($etat) {
    header('Location: contact.html?envoi=' . $etat . '#devis', true, 303);
    exit;
}

// Note la raison du blocage puis fait croire au robot que l'envoi a réussi, pour qu'il ne cherche pas à contourner.
function spam($raison) {
    global $journal_spam;
    if (!is_file($journal_spam) || filesize($journal_spam) < 1000000) {
        $ligne = date('Y-m-d H:i:s') . ' | ' . ($_SERVER['REMOTE_ADDR'] ?? '?') . ' | ' . $raison . ' | '
               . str_replace(["\r", "\n"], ' ', mb_substr(($_POST['email'] ?? '') . ' | ' . ($_POST['message'] ?? ''), 0, 300)) . "\n";
        @file_put_contents($journal_spam, $ligne, FILE_APPEND | LOCK_EX);
    }
    retour('ok');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    retour('erreur');
}

// 1. Pièges à robots : ces champs sont invisibles pour les visiteurs.
if (!empty($_POST['site-web']) || !empty($_POST['url'])) {
    spam('champ piège rempli');
}

// 2. Le formulaire doit être envoyé depuis notre site (les navigateurs indiquent toujours l'origine d'un envoi).
$origine = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
$hote = strtolower((string)parse_url($origine, PHP_URL_HOST));
if (!in_array($hote, $domaines_site, true)) {
    spam('envoyé depuis un autre site : ' . ($hote ?: 'aucune origine'));
}

// 3. Délai de saisie mesuré par la page Contact : un humain met plus de 4 secondes à remplir le formulaire.
//    Si la mesure manque (JavaScript désactivé), on affiche le message d'erreur qui donne notre téléphone.
if (!isset($_POST['d']) || !ctype_digit((string)$_POST['d'])) {
    retour('erreur');
}
if ((int)$_POST['d'] < 4) {
    spam('rempli en ' . (int)$_POST['d'] . ' s');
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

// 4. Champs obligatoires, téléphone avec au moins 9 chiffres, adresse e-mail dont le domaine existe.
$domaine_email = substr(strrchr($email, '@') ?: '', 1);
if ($nom === '' || $societe === '' || mb_strlen($message) < 10
    || preg_match_all('/\d/', $telephone) < 9
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || !(checkdnsrr($domaine_email, 'MX') || checkdnsrr($domaine_email, 'A'))) {
    retour('erreur');
}

// 5. Contenu publicitaire.
$tout = mb_strtolower("$nom $societe $email $message");
if (preg_match('~https?://|www\.~i', "$nom $societe")) {
    spam('lien dans le nom ou la société');
}
if (preg_match_all('~https?://|www\.~i', $message) > 1) {
    spam('plusieurs liens dans le message');
}
if (preg_match('/[\p{Cyrillic}\p{Han}\p{Hangul}\p{Arabic}\p{Thai}]/u', $tout)) {
    spam('alphabet étranger');
}
// Texte illisible (souvent un message étranger dont les lettres sont devenues des « ? »).
if (preg_match('/\?{5,}/', $tout)) {
    spam('texte illisible');
}
$mots_spam = [
    'seo', 'backlink', 'référencement', 'referencement', 'première page de google', 'premiere page de google',
    'first page of google', 'google ads', 'marketing digital', 'digital marketing', 'growth hacking',
    'génération de leads', 'generation de leads', 'lead generation', 'prospection automatisée',
    'refonte de votre site', 'création de site', 'creation de site', 'web design', 'webdesign', 'rédaction web',
    'audit gratuit', 'visibilité en ligne', 'visibilite en ligne', 'réseaux sociaux', 'community manager',
    'crypto', 'bitcoin', 'forex', 'casino', 'viagra', 'cialis', 'loan', 'investment opportunity',
    'unsubscribe', 'se désinscrire', 'se desinscrire', 'désabonner', 'desabonner', 'newsletter',
    'sponsored post', 'article sponsorisé', 'guest post', 'dropshipping', 'telegram', 'whatsapp me',
];
foreach ($mots_spam as $mot) {
    if (preg_match('/(?<![\p{L}\d])' . preg_quote($mot, '/') . '(?![\p{L}\d])/u', $tout)) {
        spam('mot publicitaire : ' . $mot);
    }
}

// 6. Pas plus de 3 demandes par heure depuis la même connexion.
$compteur = sys_get_temp_dir() . '/jpv-devis-' . sha1($_SERVER['REMOTE_ADDR'] ?? '') . '.txt';
$envois = [];
if (is_file($compteur)) {
    foreach (explode("\n", (string)@file_get_contents($compteur)) as $t) {
        if ((int)$t > time() - 3600) {
            $envois[] = (int)$t;
        }
    }
}
if (count($envois) >= 3) {
    retour('erreur');
}
$envois[] = time();
@file_put_contents($compteur, implode("\n", $envois), LOCK_EX);

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
