<?php
/**
 * Plugin Name: Kayli Clinn — Formulaire de Contact
 * Description: Handler sécurisé pour le formulaire de contact (validation, sauvegarde DB, envoi email + accusé de réception)
 * Version: 1.0.0
 * Author: Kayli Clinn
 * Text Domain: kc-contact
 *
 * INSTALLATION :
 * 1. Créer un dossier : wp-content/plugins/kc-contact/
 * 2. Y placer ce fichier sous le nom : kc-contact.php
 * 3. Aller dans Admin WP > Extensions > Activer "Kayli Clinn — Formulaire de Contact"
 * 4. La table SQL se crée automatiquement à l'activation
 * 5. Modifier les constantes ci-dessous selon vos besoins
 */

// Sécurité : empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

/* ════════════════════════════════════════════════════════
   ⚙️ CONFIGURATION — À PERSONNALISER
   ════════════════════════════════════════════════════════ */
define('KC_CONTACT_ADMIN_EMAIL', 'contact@kayliclinn.fr');     // Où vous recevez les messages
define('KC_CONTACT_FROM_NAME',   'Kayli Clinn');                // Nom expéditeur des emails
define('KC_CONTACT_FROM_EMAIL',  'contact@kayliclinn.fr');      // Email expéditeur (doit exister sur votre domaine)
define('KC_CONTACT_RATE_LIMIT',  3);                            // Max 3 envois par IP par heure
define('KC_CONTACT_DB_VERSION',  '1.0');

/* ════════════════════════════════════════════════════════
   🗃️ CRÉATION DE LA TABLE EN BASE (à l'activation)
   ════════════════════════════════════════════════════════ */
register_activation_hook(__FILE__, 'kc_contact_install');
function kc_contact_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'kc_contact_messages';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        subject VARCHAR(50) NOT NULL,
        firstname VARCHAR(50) NOT NULL,
        lastname VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        company VARCHAR(100) DEFAULT NULL,
        message TEXT NOT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        status ENUM('new','read','answered','archived') NOT NULL DEFAULT 'new',
        PRIMARY KEY (id),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    update_option('kc_contact_db_version', KC_CONTACT_DB_VERSION);
}

/* ════════════════════════════════════════════════════════
   🔌 ENREGISTREMENT DES ENDPOINTS AJAX
   ════════════════════════════════════════════════════════ */
// Pour visiteurs non connectés (cas principal)
add_action('wp_ajax_nopriv_kc_contact', 'kc_contact_handle_submission');
// Pour utilisateurs connectés
add_action('wp_ajax_kc_contact', 'kc_contact_handle_submission');

/* ════════════════════════════════════════════════════════
   🎯 HANDLER PRINCIPAL — Reçoit les soumissions du formulaire
   ════════════════════════════════════════════════════════ */
function kc_contact_handle_submission() {

    /* ─── 1. VÉRIFICATION DU NONCE (anti-CSRF) ─────────── */
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'kc_contact_action')) {
        wp_send_json_error(['message' => 'Session expirée, veuillez recharger la page.'], 403);
    }

    /* ─── 2. HONEYPOT (anti-bot silencieux) ────────────── */
    // Si le champ "website" est rempli, c'est un bot
    // On simule un succès pour ne pas l'alerter
    if (!empty($_POST['website'])) {
        error_log('[KC Contact] Bot détecté via honeypot — IP : ' . kc_contact_get_ip());
        wp_send_json_success(['message' => 'Message envoyé avec succès.']);
    }

    /* ─── 3. RATE LIMITING (anti-flood) ────────────────── */
    if (!kc_contact_check_rate_limit()) {
        wp_send_json_error([
            'message' => 'Trop de messages envoyés. Merci de patienter avant de réessayer.'
        ], 429);
    }

    /* ─── 4. RÉCUPÉRATION + SANITIZATION DES DONNÉES ───── */
    $data = [
        'subject'   => sanitize_text_field($_POST['subject'] ?? ''),
        'firstname' => sanitize_text_field($_POST['firstname'] ?? ''),
        'lastname'  => sanitize_text_field($_POST['lastname'] ?? ''),
        'email'     => sanitize_email($_POST['email'] ?? ''),
        'phone'     => sanitize_text_field($_POST['phone'] ?? ''),
        'company'   => sanitize_text_field($_POST['company'] ?? ''),
        'message'   => sanitize_textarea_field($_POST['message'] ?? ''),
    ];

    /* ─── 5. VALIDATION CÔTÉ SERVEUR ───────────────────── */
    $errors = kc_contact_validate($data);
    if (!empty($errors)) {
        wp_send_json_error([
            'message' => 'Données invalides : ' . implode(' ', $errors),
            'fields'  => $errors
        ], 400);
    }

    /* ─── 6. SAUVEGARDE EN BASE DE DONNÉES ─────────────── */
    $message_id = kc_contact_save_to_db($data);
    if (!$message_id) {
        wp_send_json_error(['message' => 'Erreur technique, veuillez réessayer plus tard.'], 500);
    }

    /* ─── 7. ENVOI EMAIL ADMIN (vers vous) ─────────────── */
    $admin_sent = kc_contact_send_admin_email($data, $message_id);

    /* ─── 8. ENVOI ACCUSÉ DE RÉCEPTION (vers visiteur) ── */
    $ack_sent = kc_contact_send_acknowledgment($data);

    /* ─── 9. LOG SI ERREURS EMAIL (mais on n'échoue pas) ─ */
    if (!$admin_sent || !$ack_sent) {
        error_log(sprintf(
            '[KC Contact] Erreur email pour message #%d — admin:%s, ack:%s',
            $message_id,
            $admin_sent ? 'OK' : 'KO',
            $ack_sent ? 'OK' : 'KO'
        ));
    }

    /* ─── 10. RÉPONSE FINALE ──────────────────────────── */
    wp_send_json_success([
        'message'    => 'Votre message a bien été envoyé ! Nous vous répondons sous 24h ouvrées.',
        'message_id' => $message_id
    ]);
}

/* ════════════════════════════════════════════════════════
   ✅ VALIDATION DES DONNÉES
   ════════════════════════════════════════════════════════ */
function kc_contact_validate($data) {
    $errors = [];

    // Type de demande
    $allowed_subjects = ['information', 'suivi', 'reclamation', 'partenariat'];
    if (!in_array($data['subject'], $allowed_subjects, true)) {
        $errors['subject'] = 'Type de demande invalide.';
    }

    // Prénom
    if (empty($data['firstname']) || mb_strlen($data['firstname']) < 2 || mb_strlen($data['firstname']) > 40) {
        $errors['firstname'] = 'Prénom invalide.';
    } elseif (!preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ][A-Za-zÀ-ÖØ-öø-ÿ\s\-'.]{1,39}$/u", $data['firstname'])) {
        $errors['firstname'] = 'Prénom invalide.';
    }

    // Nom
    if (empty($data['lastname']) || mb_strlen($data['lastname']) < 2 || mb_strlen($data['lastname']) > 40) {
        $errors['lastname'] = 'Nom invalide.';
    } elseif (!preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ][A-Za-zÀ-ÖØ-öø-ÿ\s\-'.]{1,39}$/u", $data['lastname'])) {
        $errors['lastname'] = 'Nom invalide.';
    }

    // Email
    if (empty($data['email']) || !is_email($data['email'])) {
        $errors['email'] = 'Email invalide.';
    }

    // Téléphone français
    $phone_clean = preg_replace('/[\s.\-]/', '', $data['phone']);
    if (!preg_match('/^(?:\+33|0033|0)[1-9]\d{8}$/', $phone_clean)) {
        $errors['phone'] = 'Numéro de téléphone français invalide.';
    }

    // Message
    if (empty($data['message']) || mb_strlen($data['message']) < 10) {
        $errors['message'] = 'Message trop court (minimum 10 caractères).';
    } elseif (mb_strlen($data['message']) > 2000) {
        $errors['message'] = 'Message trop long (maximum 2000 caractères).';
    }

    // Société (optionnel mais limité)
    if (!empty($data['company']) && mb_strlen($data['company']) > 80) {
        $errors['company'] = 'Nom de société trop long.';
    }

    return $errors;
}

/* ════════════════════════════════════════════════════════
   💾 SAUVEGARDE EN BASE DE DONNÉES
   ════════════════════════════════════════════════════════ */
function kc_contact_save_to_db($data) {
    global $wpdb;
    $table = $wpdb->prefix . 'kc_contact_messages';

    $inserted = $wpdb->insert(
        $table,
        [
            'created_at' => current_time('mysql'),
            'subject'    => $data['subject'],
            'firstname'  => $data['firstname'],
            'lastname'   => $data['lastname'],
            'email'      => $data['email'],
            'phone'      => $data['phone'],
            'company'    => $data['company'] ?: null,
            'message'    => $data['message'],
            'ip_address' => kc_contact_get_ip(),
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'status'     => 'new',
        ],
        ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
    );

    return $inserted ? $wpdb->insert_id : false;
}

/* ════════════════════════════════════════════════════════
   📧 EMAIL ADMIN (vers vous)
   ════════════════════════════════════════════════════════ */
function kc_contact_send_admin_email($data, $message_id) {
    $subject_labels = [
        'information'  => '📋 Demande d\'information',
        'suivi'        => '📊 Suivi devis/contrat',
        'reclamation'  => '⚠️ Réclamation',
        'partenariat'  => '🤝 Demande de partenariat',
    ];
    $subject_label = $subject_labels[$data['subject']] ?? 'Nouveau message';

    $subject = sprintf(
        '[Kayli Clinn] %s — %s %s (#%d)',
        $subject_label,
        $data['firstname'],
        $data['lastname'],
        $message_id
    );

    $body  = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='font-family:Arial,sans-serif;background:#f8f9fb;padding:20px;color:#1F2937;'>";
    $body .= "<div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.06);'>";
    $body .= "<div style='background:linear-gradient(135deg,#0FA7A5 0%,#0B8483 100%);color:#fff;padding:24px 32px;'>";
    $body .= "<h1 style='margin:0;font-size:20px;'>Nouveau message — $subject_label</h1>";
    $body .= "<p style='margin:6px 0 0;opacity:0.9;font-size:13px;'>Message #$message_id · " . date('d/m/Y H:i') . "</p>";
    $body .= "</div>";
    $body .= "<div style='padding:28px 32px;'>";

    $body .= "<table style='width:100%;border-collapse:collapse;'>";
    $body .= kc_contact_email_row('Prénom',    esc_html($data['firstname']));
    $body .= kc_contact_email_row('Nom',       esc_html($data['lastname']));
    $body .= kc_contact_email_row('Email',     '<a href="mailto:' . esc_attr($data['email']) . '" style="color:#0B8483;">' . esc_html($data['email']) . '</a>');
    $body .= kc_contact_email_row('Téléphone', '<a href="tel:' . esc_attr(preg_replace('/\s/', '', $data['phone'])) . '" style="color:#0B8483;">' . esc_html($data['phone']) . '</a>');
    if (!empty($data['company'])) {
        $body .= kc_contact_email_row('Société', esc_html($data['company']));
    }
    $body .= "</table>";

    $body .= "<div style='margin-top:24px;padding:18px;background:#F0FAFA;border-left:4px solid #0FA7A5;border-radius:6px;'>";
    $body .= "<div style='font-size:11px;font-weight:600;color:#076E6D;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;'>Message</div>";
    $body .= "<div style='white-space:pre-wrap;line-height:1.6;color:#1F2937;'>" . esc_html($data['message']) . "</div>";
    $body .= "</div>";

    $body .= "<div style='margin-top:24px;padding-top:18px;border-top:1px solid #E5E7EB;font-size:11px;color:#6B7280;'>";
    $body .= "IP : " . esc_html(kc_contact_get_ip()) . "<br>";
    $body .= "User-Agent : " . esc_html(substr($_SERVER['HTTP_USER_AGENT'] ?? 'inconnu', 0, 120));
    $body .= "</div>";

    $body .= "</div></div></body></html>";

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . KC_CONTACT_FROM_NAME . ' <' . KC_CONTACT_FROM_EMAIL . '>',
        'Reply-To: ' . $data['firstname'] . ' ' . $data['lastname'] . ' <' . $data['email'] . '>',
    ];

    return wp_mail(KC_CONTACT_ADMIN_EMAIL, $subject, $body, $headers);
}

/* ════════════════════════════════════════════════════════
   📨 ACCUSÉ DE RÉCEPTION (vers le visiteur)
   ════════════════════════════════════════════════════════ */
function kc_contact_send_acknowledgment($data) {
    $subject = 'Nous avons bien reçu votre message — Kayli Clinn';

    $body  = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='font-family:Arial,sans-serif;background:#f8f9fb;padding:20px;color:#1F2937;margin:0;'>";
    $body .= "<div style='max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.06);'>";

    // Header
    $body .= "<div style='background:linear-gradient(135deg,#0FA7A5 0%,#0B8483 100%);color:#fff;padding:32px;text-align:center;'>";
    $body .= "<div style='width:60px;height:60px;background:rgba(255,255,255,0.2);border-radius:50%;margin:0 auto 16px;display:inline-block;line-height:60px;font-size:30px;'>✓</div>";
    $body .= "<h1 style='margin:0;font-size:22px;'>Message bien reçu !</h1>";
    $body .= "</div>";

    // Body
    $body .= "<div style='padding:32px;line-height:1.6;'>";
    $body .= "<p style='font-size:16px;margin:0 0 16px;'>Bonjour " . esc_html($data['firstname']) . ",</p>";
    $body .= "<p>Merci de nous avoir contactés. Nous avons bien reçu votre message et un membre de notre équipe vous répondra <strong>sous 24h ouvrées</strong>.</p>";

    // Récap du message
    $body .= "<div style='margin:24px 0;padding:18px;background:#F0FAFA;border-radius:8px;border-left:4px solid #0FA7A5;'>";
    $body .= "<div style='font-size:11px;font-weight:600;color:#076E6D;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;'>Récapitulatif de votre message</div>";
    $body .= "<div style='font-size:13px;color:#4B5563;white-space:pre-wrap;'>" . esc_html(mb_substr($data['message'], 0, 300)) . (mb_strlen($data['message']) > 300 ? '...' : '') . "</div>";
    $body .= "</div>";

    $body .= "<p>En cas d'urgence, vous pouvez aussi nous joindre directement :</p>";
    $body .= "<div style='margin:20px 0;'>";
    $body .= "<a href='tel:+33670012061' style='display:inline-block;background:#0FA7A5;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;margin-right:8px;'>📞 06 70 01 20 61</a>";
    $body .= "<a href='https://wa.me/33670012061' style='display:inline-block;background:#25D366;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:600;'>💬 WhatsApp</a>";
    $body .= "</div>";

    $body .= "<p style='margin-top:24px;'>À très bientôt,<br><strong>L'équipe Kayli Clinn</strong></p>";
    $body .= "</div>";

    // Footer
    $body .= "<div style='background:#F8F9FB;padding:20px 32px;border-top:1px solid #E5E7EB;text-align:center;font-size:11px;color:#6B7280;line-height:1.5;'>";
    $body .= "Cet email automatique confirme la réception de votre message.<br>";
    $body .= "Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer ce message.";
    $body .= "</div>";

    $body .= "</div></body></html>";

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . KC_CONTACT_FROM_NAME . ' <' . KC_CONTACT_FROM_EMAIL . '>',
        'Reply-To: ' . KC_CONTACT_ADMIN_EMAIL,
    ];

    return wp_mail($data['email'], $subject, $body, $headers);
}

/* ════════════════════════════════════════════════════════
   🔧 FONCTIONS UTILITAIRES
   ════════════════════════════════════════════════════════ */

/**
 * Génère une ligne de tableau HTML pour l'email admin
 */
function kc_contact_email_row($label, $value) {
    return "<tr>"
        . "<td style='padding:10px 0;border-bottom:1px solid #F3F4F6;font-weight:600;color:#0D2340;width:120px;font-size:13px;'>$label</td>"
        . "<td style='padding:10px 0;border-bottom:1px solid #F3F4F6;color:#1F2937;font-size:14px;'>$value</td>"
        . "</tr>";
}

/**
 * Récupère l'IP du visiteur (en gérant les proxies)
 */
function kc_contact_get_ip() {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/**
 * Rate limiting : max N envois par IP par heure
 */
function kc_contact_check_rate_limit() {
    $ip = kc_contact_get_ip();
    $transient_key = 'kc_contact_rl_' . md5($ip);
    $count = (int) get_transient($transient_key);

    if ($count >= KC_CONTACT_RATE_LIMIT) {
        return false;
    }

    set_transient($transient_key, $count + 1, HOUR_IN_SECONDS);
    return true;
}

/* ════════════════════════════════════════════════════════
   📤 EXPOSER LE NONCE AU JAVASCRIPT (pour le formulaire)
   ════════════════════════════════════════════════════════ */
add_action('wp_enqueue_scripts', 'kc_contact_enqueue');
function kc_contact_enqueue() {
    // Crée une variable JS globale : window.kcContact
    wp_register_script('kc-contact-config', '', [], false, true);
    wp_enqueue_script('kc-contact-config');
    wp_localize_script('kc-contact-config', 'kcContact', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('kc_contact_action'),
    ]);
}

/* ════════════════════════════════════════════════════════
   👨‍💼 PAGE D'ADMINISTRATION (consulter les messages reçus)
   ════════════════════════════════════════════════════════ */
add_action('admin_menu', 'kc_contact_admin_menu');
function kc_contact_admin_menu() {
    add_menu_page(
        'Messages Contact',
        'Messages',
        'manage_options',
        'kc-contact-messages',
        'kc_contact_admin_page',
        'dashicons-email-alt',
        30
    );
}

function kc_contact_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'kc_contact_messages';

    // Marquer un message comme lu si demandé
    if (isset($_GET['mark_read']) && current_user_can('manage_options')) {
        $id = (int) $_GET['mark_read'];
        $wpdb->update($table, ['status' => 'read'], ['id' => $id]);
        echo '<div class="notice notice-success"><p>Message #' . $id . ' marqué comme lu.</p></div>';
    }

    $messages = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 100");
    ?>
    <div class="wrap">
        <h1>Messages de contact reçus</h1>
        <p>Les 100 derniers messages reçus via le formulaire de contact.</p>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th style="width:140px;">Date</th>
                    <th style="width:120px;">Type</th>
                    <th>Nom</th>
                    <th>Contact</th>
                    <th>Message</th>
                    <th style="width:100px;">Statut</th>
                    <th style="width:80px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($messages)) : ?>
                    <tr><td colspan="8" style="text-align:center;padding:20px;">Aucun message pour le moment.</td></tr>
                <?php else : foreach ($messages as $msg) : ?>
                    <tr style="<?php echo $msg->status === 'new' ? 'background:#fffbea;' : ''; ?>">
                        <td>#<?php echo (int) $msg->id; ?></td>
                        <td><?php echo esc_html(date('d/m/Y H:i', strtotime($msg->created_at))); ?></td>
                        <td><?php echo esc_html(ucfirst($msg->subject)); ?></td>
                        <td><strong><?php echo esc_html($msg->firstname . ' ' . $msg->lastname); ?></strong></td>
                        <td>
                            <a href="mailto:<?php echo esc_attr($msg->email); ?>"><?php echo esc_html($msg->email); ?></a><br>
                            <small><?php echo esc_html($msg->phone); ?></small>
                        </td>
                        <td><?php echo esc_html(mb_substr($msg->message, 0, 100)) . (mb_strlen($msg->message) > 100 ? '...' : ''); ?></td>
                        <td>
                            <?php
                            $badges = [
                                'new'      => ['#fffbea', '#b54708', 'Nouveau'],
                                'read'     => ['#eff6ff', '#1e40af', 'Lu'],
                                'answered' => ['#ecfdf5', '#065f46', 'Répondu'],
                                'archived' => ['#f3f4f6', '#374151', 'Archivé'],
                            ];
                            $b = $badges[$msg->status] ?? $badges['new'];
                            echo '<span style="background:' . $b[0] . ';color:' . $b[1] . ';padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;">' . $b[2] . '</span>';
                            ?>
                        </td>
                        <td>
                            <?php if ($msg->status === 'new') : ?>
                                <a href="?page=kc-contact-messages&mark_read=<?php echo (int) $msg->id; ?>" class="button button-small">Marquer lu</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}