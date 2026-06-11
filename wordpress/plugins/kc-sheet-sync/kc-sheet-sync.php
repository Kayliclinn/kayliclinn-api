<?php
/**
 * Plugin Name: Kayli Clinn — Synchro Google Sheet
 * Description: Ajoute automatiquement chaque réservation à un Google Sheet (référence KC-XXXX, statut, montants) et envoie un e-mail « acompte reçu ». Complément du plugin de réservation kc-booking.
 * Version: 1.2.0
 * Author: Kayli Clinn
 */

if (!defined('ABSPATH')) exit;

/* ════════════════════════════════════════════════════════════════════
   CONFIGURATION
   ════════════════════════════════════════════════════════════════════ */
// Identifiant du Google Sheet (la longue suite entre /d/ et /edit dans l'URL)
if (!defined('KC_SHEET_ID'))  define('KC_SHEET_ID', '14qEwUCtN-035VHtLuw__xQNGV21codnmksVtdC7uw5k');
// Nom de l'onglet par défaut (détecté automatiquement si différent)
if (!defined('KC_SHEET_TAB')) define('KC_SHEET_TAB', 'Réservations');

/* ════════════════════════════════════════════════════════════════════
   RÉFÉRENCE LISIBLE
   ════════════════════════════════════════════════════════════════════ */
function kc_sheet_ref($id) { return sprintf('KC-%04d', (int) $id); }

/* ════════════════════════════════════════════════════════════════════
   AUTHENTIFICATION GOOGLE SHEETS (réutilise le compte de service de l'agenda)
   ════════════════════════════════════════════════════════════════════ */
function kc_sheet_token($force = false) {
    if (!$force) { $c = get_transient('kc_sheet_token'); if ($c) return $c; }
    if (!function_exists('kc_get_service_account') || !function_exists('kc_b64url_encode'))
        return new WP_Error('kc_no_main', 'Plugin de réservation kc-booking introuvable.');

    $sa = kc_get_service_account();
    if (!$sa || !isset($sa['client_email'], $sa['private_key']))
        return new WP_Error('kc_no_sa', 'Compte de service Google non configuré (voir Réglages du plugin réservation).');

    $pk = $sa['private_key'];
    $pk = preg_replace('/\\\\+n/', "\n", $pk);
    $pk = str_replace(["\r\n", "\r"], "\n", $pk);
    $pk = trim($pk);
    if (preg_match('/-----BEGIN ([A-Z0-9 ]+)-----(.*?)-----END \\1-----/s', $pk, $m)) {
        $type = trim($m[1]);
        $body = preg_replace('/[^A-Za-z0-9+\/=]/', '', $m[2]);
        $pk = "-----BEGIN {$type}-----\n" . chunk_split($body, 64, "\n") . "-----END {$type}-----\n";
    }

    $now = time();
    $header  = ['alg' => 'RS256', 'typ' => 'JWT'];
    $payload = [
        'iss'   => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/spreadsheets',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'exp'   => $now + 3600,
        'iat'   => $now,
    ];
    $si  = kc_b64url_encode(wp_json_encode($header)) . '.' . kc_b64url_encode(wp_json_encode($payload));
    $sig = '';
    if (!openssl_sign($si, $sig, $pk, 'sha256'))
        return new WP_Error('kc_sign', 'Échec de la signature JWT (clé privée illisible).');
    $jwt = $si . '.' . kc_b64url_encode($sig);

    $resp = wp_remote_post('https://oauth2.googleapis.com/token', [
        'timeout' => 15,
        'body'    => ['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt],
    ]);
    if (is_wp_error($resp)) return $resp;
    $b = json_decode(wp_remote_retrieve_body($resp), true);
    if (empty($b['access_token']))
        return new WP_Error('kc_oauth', 'OAuth Google : ' . ($b['error_description'] ?? $b['error'] ?? 'échec') . '.');
    set_transient('kc_sheet_token', $b['access_token'], 3500);
    return $b['access_token'];
}

/* ════════════════════════════════════════════════════════════════════
   APPELS API GOOGLE SHEETS
   ════════════════════════════════════════════════════════════════════ */
function kc_sheet_api($method, $path, $body = null) {
    $tok = kc_sheet_token();
    if (is_wp_error($tok)) return $tok;
    $args = ['method' => $method, 'timeout' => 15,
        'headers' => ['Authorization' => 'Bearer ' . $tok, 'Content-Type' => 'application/json']];
    if ($body !== null) $args['body'] = wp_json_encode($body);
    $resp = wp_remote_request('https://sheets.googleapis.com/v4/spreadsheets/' . KC_SHEET_ID . $path, $args);
    if (is_wp_error($resp)) return $resp;
    $code = wp_remote_retrieve_response_code($resp);
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    if ($code < 200 || $code >= 300)
        return new WP_Error('kc_sheets', ($data['error']['message'] ?? 'Erreur Google Sheets') . ' (code ' . $code . ')');
    return $data;
}

// Nom réel de l'onglet (détecté + mis en cache pour gérer les accents/renommages)
function kc_sheet_tab() {
    $t = get_transient('kc_sheet_tab_title');
    if ($t) return $t;
    $res = kc_sheet_api('GET', '?fields=sheets.properties.title');
    if (is_wp_error($res)) return KC_SHEET_TAB;
    $title = $res['sheets'][0]['properties']['title'] ?? KC_SHEET_TAB;
    set_transient('kc_sheet_tab_title', $title, DAY_IN_SECONDS);
    return $title;
}
function kc_sheet_rng($a1) { return "'" . str_replace("'", "''", kc_sheet_tab()) . "'!" . $a1; }

function kc_sheet_append($row) {
    $range = rawurlencode(kc_sheet_rng('A1'));
    return kc_sheet_api('POST', '/values/' . $range . ':append?valueInputOption=USER_ENTERED&insertDataOption=INSERT_ROWS', ['values' => [$row]]);
}
function kc_sheet_find_row($ref) {
    $range = rawurlencode(kc_sheet_rng('A:A'));
    $res = kc_sheet_api('GET', '/values/' . $range);
    if (is_wp_error($res)) return 0;
    foreach (($res['values'] ?? []) as $i => $r) {
        if (isset($r[0]) && $r[0] === $ref) return $i + 1; // ligne 1-indexée
    }
    return 0;
}

/* ════════════════════════════════════════════════════════════════════
   CONSTRUCTION D'UNE LIGNE À PARTIR D'UNE RÉSERVATION
   ════════════════════════════════════════════════════════════════════ */
function kc_sheet_build_row($b, $paid = false) {
    $req = ($b->cta_type === 'calendar_paid');

    if ($paid)                          { $statut = 'Confirmé';            $paiement = 'Payé'; }
    elseif ($b->status === 'cancelled') { $statut = 'Annulé';             $paiement = '—'; }
    elseif ($b->status === 'confirmed') { $statut = 'Confirmé';            $paiement = $req ? 'Payé' : 'Gratuit'; }
    else                                { $statut = 'En attente paiement'; $paiement = $req ? 'En attente' : 'Gratuit'; }

    $presta = $b->type_name;
    if (function_exists('kc_booking_meta')) {
        $vn = kc_booking_meta($b, 'variant_name');
        if ($vn) $presta .= ' — ' . $vn;
    }

    $total = ($b->total_amount_ttc !== null) ? (float) $b->total_amount_ttc : '';
    $dep   = ($b->deposit_amount_ttc !== null) ? (float) $b->deposit_amount_ttc : '';
    $is_paid = ($paiement === 'Payé');
    $acompte = $is_paid ? $dep : '';
    $solde   = '';
    if ($total !== '') $solde = $is_paid ? round($total - ($dep !== '' ? $dep : 0), 2) : $total;

    $modepay = $req ? (($req && function_exists('kc_opt') && kc_opt('stripe_enabled')) ? 'Carte (Stripe)' : 'À régler') : '—';

    $start = '';
    try { $start = (new DateTime($b->start_datetime))->format('d/m/Y H:i'); } catch (Exception $e) {}

    return [
        kc_sheet_ref($b->id),                                   // A Référence
        current_time('d/m/Y H:i'),                              // B Date demande
        $start,                                                 // C Date intervention
        $presta,                                                // D Prestation
        trim($b->client_firstname . ' ' . $b->client_lastname), // E Client
        $b->client_phone,                                       // F Téléphone
        $b->client_email,                                       // G Email
        $b->client_address ?: '',                               // H Adresse
        $statut,                                                // I Statut
        $paiement,                                              // J Paiement
        $total,                                                 // K Total TTC
        $acompte,                                               // L Acompte reçu
        $solde,                                                 // M Solde dû
        $modepay,                                               // N Mode paiement
        $b->staff_name ?: '',                                   // O Intervenant
        $b->client_message ?: '',                               // P Notes
    ];
}

/* ════════════════════════════════════════════════════════════════════
   ACTIONS : création, paiement, annulation
   ════════════════════════════════════════════════════════════════════ */
function kc_sheet_on_create($b) {
    kc_sheet_append(kc_sheet_build_row($b, false));
}

function kc_sheet_on_paid($b, $pi = '') {
    $token = $b->cancellation_token;
    $already = get_transient('kc_sheet_paid_' . $token);

    $ref   = kc_sheet_ref($b->id);
    $total = ($b->total_amount_ttc !== null) ? (float) $b->total_amount_ttc : '';
    $dep   = ($b->deposit_amount_ttc !== null) ? (float) $b->deposit_amount_ttc : '';
    $solde = ($total !== '') ? round($total - ($dep !== '' ? $dep : 0), 2) : '';

    $rownum = kc_sheet_find_row($ref);
    if ($rownum > 1) {
        kc_sheet_api('POST', '/values:batchUpdate', ['valueInputOption' => 'USER_ENTERED', 'data' => [
            ['range' => kc_sheet_rng('I' . $rownum . ':J' . $rownum), 'values' => [['Confirmé', 'Payé']]],
            ['range' => kc_sheet_rng('L' . $rownum . ':M' . $rownum), 'values' => [[$dep, $solde]]],
        ]]);
    } else {
        kc_sheet_append(kc_sheet_build_row($b, true)); // sécurité : ligne absente → on l'ajoute payée
    }

    // E-mail admin « acompte reçu » (une seule fois)
    if (!$already && function_exists('kc_email_admin')) {
        kc_email_admin($b, '✅ Acompte reçu — réservation confirmée',
            '<p>Bonne nouvelle : le client a réglé son acompte (' . esc_html(number_format((float) $dep, 2, ',', ' ')) . ' €). '
            . 'La réservation <strong>' . esc_html($ref) . '</strong> est confirmée.</p>');
    }

    // Nom + référence visibles directement dans Stripe (sur le paiement)
    $sk = function_exists('kc_opt') ? kc_opt('stripe_sk') : '';
    if ($pi && $sk) {
        $name = trim($b->client_firstname . ' ' . $b->client_lastname);
        wp_remote_post('https://api.stripe.com/v1/payment_intents/' . rawurlencode($pi), [
            'timeout' => 15,
            'headers' => ['Authorization' => 'Bearer ' . $sk],
            'body'    => [
                'description'          => $ref . ' — ' . $name,
                'metadata[reference]'  => $ref,
                'metadata[client]'     => $name,
                'metadata[prestation]' => $b->type_name,
            ],
        ]);
    }

    set_transient('kc_sheet_paid_' . $token, 1, DAY_IN_SECONDS);
}

function kc_sheet_on_cancel($b) {
    $rownum = kc_sheet_find_row(kc_sheet_ref($b->id));
    if ($rownum > 1) {
        kc_sheet_api('POST', '/values:batchUpdate', ['valueInputOption' => 'USER_ENTERED', 'data' => [
            ['range' => kc_sheet_rng('I' . $rownum), 'values' => [['Annulé']]],
        ]]);
    }
}

/* ════════════════════════════════════════════════════════════════════
   BRANCHEMENT : on observe les réponses de l'API du plugin réservation
   ════════════════════════════════════════════════════════════════════ */
add_filter('rest_post_dispatch', 'kc_sheet_dispatch', 10, 3);
function kc_sheet_dispatch($result, $server, $request) {
    if (!function_exists('kc_get_booking_by_token')) return $result;
    $route = $request->get_route();
    $ns = '/kc-booking/v1';
    if (strpos($route, $ns) !== 0) return $result;
    $method = $request->get_method();

    // 1) Création d'une réservation → log dans le Sheet + référence dans la réponse
    if ($route === $ns . '/bookings' && $method === 'POST') {
        $data = ($result instanceof WP_REST_Response) ? $result->get_data() : null;
        if (is_array($data) && !empty($data['ok']) && !empty($data['token'])) {
            $b = kc_get_booking_by_token($data['token']);
            if ($b) {
                kc_sheet_on_create($b);
                $data['reference'] = kc_sheet_ref($b->id);
                $result->set_data($data);
            }
        }
    }
    // 2) Consultation d'une réservation → on ajoute la référence à la réponse
    elseif ($method === 'GET' && preg_match('#^' . preg_quote($ns, '#') . '/bookings/[^/]+$#', $route)) {
        $data = ($result instanceof WP_REST_Response) ? $result->get_data() : null;
        if (is_array($data) && !empty($data['token'])) {
            $b = kc_get_booking_by_token($data['token']);
            if ($b) { $data['reference'] = kc_sheet_ref($b->id); $result->set_data($data); }
        }
    }
    // 3) Paiement confirmé (webhook Stripe)
    elseif ($route === $ns . '/stripe/webhook' && $method === 'POST') {
        // SÉCURITÉ : ne traiter que si la signature Stripe est valide.
        // Ce filtre s'exécute même quand le handler principal a rejeté
        // l'événement : sans cette vérification, un faux webhook pourrait
        // marquer une ligne « Payé » dans le Sheet et déclencher un e-mail.
        $secret = function_exists('kc_opt') ? kc_opt('stripe_webhook_secret') : '';
        $sig    = $request->get_header('Stripe-Signature');
        if ($secret && function_exists('kc_stripe_verify_sig')
            && !kc_stripe_verify_sig($request->get_body(), $sig, $secret)) {
            return $result; // signature absente ou invalide → on ignore
        }
        $event = json_decode($request->get_body(), true);
        if (is_array($event) && ($event['type'] ?? '') === 'checkout.session.completed') {
            $obj   = $event['data']['object'] ?? [];
            $token = $obj['metadata']['kc_token'] ?? '';
            $pi    = $obj['payment_intent'] ?? '';
            if ($token) { $b = kc_get_booking_by_token($token); if ($b) kc_sheet_on_paid($b, $pi); }
        }
    }
    // 4) Annulation par le client
    elseif ($method === 'POST' && preg_match('#^' . preg_quote($ns, '#') . '/bookings/[^/]+/cancel$#', $route)) {
        $data = ($result instanceof WP_REST_Response) ? $result->get_data() : null;
        if (is_array($data) && !empty($data['ok'])) {
            $token = $request->get_param('token');
            if ($token) { $b = kc_get_booking_by_token($token); if ($b) kc_sheet_on_cancel($b); }
        }
    }

    return $result;
}

/* ════════════════════════════════════════════════════════════════════
   ENDPOINT « PAYER PLUS TARD » : régénère un lien de paiement Stripe frais
   ════════════════════════════════════════════════════════════════════ */
add_action('rest_api_init', function () {
    register_rest_route('kc-booking/v1', '/bookings/(?P<token>[a-zA-Z0-9_-]{32,64})/pay', [
        'methods' => 'POST', 'callback' => 'kc_sheet_rest_pay', 'permission_callback' => '__return_true',
    ]);
});
function kc_sheet_rest_pay(WP_REST_Request $req) {
    if (!function_exists('kc_get_booking_by_token') || !function_exists('kc_stripe_create_checkout'))
        return new WP_Error('kc_no_main', 'Service indisponible.', ['status' => 500]);
    $b = kc_get_booking_by_token($req['token']);
    if (!$b) return new WP_Error('kc_not_found', 'Réservation introuvable.', ['status' => 404]);
    if ($b->status === 'cancelled') return new WP_Error('kc_cancelled', 'Réservation annulée.', ['status' => 409]);
    if (function_exists('kc_booking_meta') && kc_booking_meta($b, 'payment_status') === 'paid')
        return rest_ensure_response(['ok' => true, 'already_paid' => true]);
    if ($b->cta_type !== 'calendar_paid')
        return new WP_Error('kc_free', 'Aucun paiement requis pour cette réservation.', ['status' => 409]);

    $amount = ($b->deposit_amount_ttc !== null) ? (float) $b->deposit_amount_ttc
            : (($b->total_amount_ttc !== null) ? (float) $b->total_amount_ttc : 0);
    if ($amount <= 0) return new WP_Error('kc_no_amount', 'Montant indisponible.', ['status' => 409]);

    $sess = kc_stripe_create_checkout($b, $amount);
    if (is_wp_error($sess) || empty($sess['url']))
        return new WP_Error('kc_stripe', 'Paiement momentanément indisponible.', ['status' => 502]);
    return rest_ensure_response(['ok' => true, 'payment_url' => $sess['url'], 'reference' => kc_sheet_ref($b->id)]);
}

/* ════════════════════════════════════════════════════════════════════
   E-MAIL « À FINALISER » : lien durable vers la page de gestion + référence
   On réécrit proprement le message (mêmes styles), sans toucher au plugin principal.
   ════════════════════════════════════════════════════════════════════ */
add_filter('wp_mail', 'kc_sheet_improve_pending_email');
function kc_sheet_improve_pending_email($atts) {
    if (!function_exists('kc_email_wrap') || !function_exists('kc_booking_manage_url')
        || !function_exists('kc_email_details_rows') || !function_exists('kc_email_button')
        || !function_exists('kc_get_booking_enriched')) return $atts;

    $company = function_exists('kc_opt') ? kc_opt('company_name', 'Kayli Clinn') : 'Kayli Clinn';
    if (($atts['subject'] ?? '') !== '[' . $company . '] Finalisez votre réservation') return $atts;

    $to = is_array($atts['to'] ?? '') ? reset($atts['to']) : ($atts['to'] ?? '');
    $to = trim(preg_replace('/.*<(.+)>.*/', '$1', (string) $to));
    if (!$to) return $atts;

    global $wpdb;
    $id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}kc_bookings WHERE client_email = %s AND status = 'pending' ORDER BY created_at DESC, id DESC LIMIT 1",
        $to));
    if (!$id) return $atts;
    $b = kc_get_booking_enriched((int) $id);
    if (!$b) return $atts;

    $ref     = kc_sheet_ref($b->id);
    $manage  = kc_booking_manage_url($b);
    $deposit = function_exists('kc_money') ? kc_money($b->deposit_amount_ttc)
             : number_format((float) $b->deposit_amount_ttc, 2, ',', ' ') . ' €';

    $inner = '<p>Bonjour ' . esc_html($b->client_firstname) . ',</p>'
           . '<p>Votre créneau est réservé. Il reste l\'acompte à régler pour le confirmer définitivement — vous pouvez le faire maintenant, ou plus tard, depuis votre espace de réservation.</p>'
           . '<p style="font-size:14px;margin:0 0 6px;">Votre référence : <strong>' . esc_html($ref) . '</strong></p>'
           . kc_email_details_rows($b)
           . kc_email_button('Régler l\'acompte (' . $deposit . ')', $manage)
           . '<p style="font-size:13px;color:#6b7280;">Ce lien reste valable : vous pourrez revenir régler l\'acompte quand vous le souhaitez. Sans règlement, le créneau pourra être libéré.</p>';

    $atts['message'] = kc_email_wrap('Finalisez votre réservation', $inner);
    return $atts;
}

/* ════════════════════════════════════════════════════════════════════
   PAGE ADMIN : test de connexion
   ════════════════════════════════════════════════════════════════════ */
add_action('admin_menu', function () {
    add_submenu_page('kc-booking-dashboard', 'Synchro Google Sheet', '📊 Google Sheet', 'manage_options', 'kc-sheet-sync', 'kc_sheet_admin_page');
}, 14);

function kc_sheet_admin_page() {
    $msg = '';
    if (isset($_POST['kc_sheet_test']) && check_admin_referer('kc_sheet_test')) {
        delete_transient('kc_sheet_token');
        delete_transient('kc_sheet_tab_title');
        $res = kc_sheet_api('GET', '?fields=properties.title,sheets.properties.title');
        if (is_wp_error($res)) {
            $msg = '<div class="notice notice-error"><p>❌ <strong>Échec :</strong> ' . esc_html($res->get_error_message()) . '</p></div>';
        } else {
            $title = $res['properties']['title'] ?? '?';
            $tab   = $res['sheets'][0]['properties']['title'] ?? '?';
            $msg = '<div class="notice notice-success"><p>✅ <strong>Connexion réussie !</strong> Classeur : <strong>' . esc_html($title) . '</strong> · 1er onglet : <strong>' . esc_html($tab) . '</strong></p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>📊 Synchronisation Google Sheet</h1>
        <?php echo $msg; ?>
        <p>Chaque réservation est ajoutée automatiquement à ton Google Sheet, avec une référence <code>KC-XXXX</code>.</p>
        <table class="form-table">
            <tr><th>Identifiant du tableau</th><td><code><?php echo esc_html(KC_SHEET_ID); ?></code></td></tr>
        </table>
        <form method="post">
            <?php wp_nonce_field('kc_sheet_test'); ?>
            <input type="hidden" name="kc_sheet_test" value="1">
            <button type="submit" class="button button-primary button-large">🔌 Tester la connexion au Google Sheet</button>
        </form>
        <p class="description" style="margin-top:14px;">
            En cas d'erreur, vérifie 2 choses :<br>
            1. Le Google Sheet est bien <strong>partagé en « Éditeur »</strong> avec l'adresse du compte de service (voir Réservations → 🧪 Diagnostic Google).<br>
            2. L'API <strong>« Google Sheets API »</strong> est bien <strong>activée</strong> dans Google Cloud Console (projet de l'agenda).
        </p>
    </div>
    <?php
}
