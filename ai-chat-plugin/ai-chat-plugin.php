<?php
/**
 * Plugin Name: AI Chat Assistant
 * Description: Plugin zur Integration von ChatGPT in WordPress
 * Version: 1.0
 * Author: Dmytro Popov
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_ChatGPT_Plugin {
    
    public function __construct() {
        // WordPress Hooks registrieren
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_chatgpt_request', array($this, 'handle_chatgpt_request'));
        add_action('wp_ajax_nopriv_chatgpt_request', array($this, 'handle_chatgpt_request'));
        add_shortcode('ai_chat', array($this, 'chat_shortcode'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    // Wird beim Aktivieren des Plugins ausgeführt
    public function activate() {
        add_option('chatgpt_api_key', '');
        add_option('chatgpt_model', 'gpt-4o-mini');
        add_option('chatgpt_max_tokens', 500);
        add_option('chatgpt_temperature', 0.7);
        add_option('chatgpt_system_prompt', 'Du bist ein freundlicher Assistent.');
    }
    
    // JavaScript und CSS laden
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('chatgpt-script', plugin_dir_url(__FILE__) . 'chatgpt-script.js', array('jquery'), '1.0', true);
        wp_enqueue_style('chatgpt-style', plugin_dir_url(__FILE__) . 'chatgpt-style.css');
        
        // AJAX URL und Sicherheitstoken an JavaScript übergeben
        wp_localize_script('chatgpt-script', 'chatgpt_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chatgpt_nonce')
        ));
    }
    
    // Menü in WordPress Admin erstellen
    public function add_admin_menu() {
        add_menu_page(
            'AI Chat Einstellungen',
            'AI Chat',
            'manage_options',
            'ai-chat-settings',
            array($this, 'admin_page'),
            'dashicons-format-chat'
        );
    }
    
    // Einstellungen registrieren
    public function admin_init() {
        register_setting('chatgpt_settings', 'chatgpt_api_key');
        register_setting('chatgpt_settings', 'chatgpt_model');
        register_setting('chatgpt_settings', 'chatgpt_max_tokens');
        register_setting('chatgpt_settings', 'chatgpt_temperature');
        register_setting('chatgpt_settings', 'chatgpt_system_prompt');
        
        add_settings_section('chatgpt_main_section', 'Grundeinstellungen', null, 'chatgpt_settings');
        
        add_settings_field('chatgpt_api_key', 'API-Schlüssel', array($this, 'api_key_field'), 'chatgpt_settings', 'chatgpt_main_section');
        add_settings_field('chatgpt_model', 'Modell', array($this, 'model_field'), 'chatgpt_settings', 'chatgpt_main_section');
        add_settings_field('chatgpt_max_tokens', 'Max Tokens', array($this, 'max_tokens_field'), 'chatgpt_settings', 'chatgpt_main_section');
        add_settings_field('chatgpt_temperature', 'Temperatur', array($this, 'temperature_field'), 'chatgpt_settings', 'chatgpt_main_section');
        add_settings_field('chatgpt_system_prompt', 'System-Prompt', array($this, 'system_prompt_field'), 'chatgpt_settings', 'chatgpt_main_section');
    }
    
    // Admin-Seite anzeigen
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>AI Chat Einstellungen</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('chatgpt_settings');
                do_settings_sections('chatgpt_settings');
                submit_button();
                ?>
            </form>
            <hr>
            <h2>Verwendung</h2>
            <p>Shortcode: <code>[ai_chat]</code></p>
        </div>
        <?php
    }
    
    // API-Schlüssel Eingabefeld
    public function api_key_field() {
        $value = get_option('chatgpt_api_key');
        ?>
        <input type="password" id="chatgpt_api_key" name="chatgpt_api_key" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <label>
            <input type="checkbox" id="show_key" /> Anzeigen
        </label>
        <script>
        document.getElementById('show_key').addEventListener('change', function() {
            var input = document.getElementById('chatgpt_api_key');
            input.type = this.checked ? 'text' : 'password';
        });
        </script>
        <?php
    }
    
    // Modell-Auswahl
    public function model_field() {
        $value = get_option('chatgpt_model', 'gpt-4o-mini');
        $models = array(
            'gpt-4o-mini' => 'GPT-4o Mini (empfohlen)',
            'gpt-4o' => 'GPT-4o',
            'gpt-4-turbo' => 'GPT-4 Turbo'
        );
        
        echo '<select name="chatgpt_model">';
        foreach ($models as $key => $label) {
            $selected = ($value === $key) ? 'selected' : '';
            echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }
    
    // Max Tokens Eingabefeld
    public function max_tokens_field() {
        $value = get_option('chatgpt_max_tokens', 500);
        echo '<input type="number" name="chatgpt_max_tokens" value="' . esc_attr($value) . '" min="50" max="4000" />';
    }
    
    // Temperatur Eingabefeld
    public function temperature_field() {
        $value = get_option('chatgpt_temperature', 0.7);
        echo '<input type="number" name="chatgpt_temperature" value="' . esc_attr($value) . '" min="0" max="1" step="0.1" />';
    }
    
    // System-Prompt Textfeld
    public function system_prompt_field() {
        $value = get_option('chatgpt_system_prompt', 'Du bist ein freundlicher Assistent.');
        echo '<textarea name="chatgpt_system_prompt" rows="5" class="large-text">' . esc_textarea($value) . '</textarea>';
    }
    
    // Shortcode für Chat-Widget
    public function chat_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => 'Ihre Frage...',
            'height' => '400px',
            'width' => '100%'
        ), $atts);
        
        ob_start();
        ?>
        <div id="chatgpt-container" style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;">
            <div id="chatgpt-messages"></div>
            <div id="chatgpt-input-container">
                <input type="text" id="chatgpt-input" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" />
                <button id="chatgpt-send">Senden</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // AJAX Request verarbeiten
    public function handle_chatgpt_request() {
        // Sicherheitsprüfung
        if (!wp_verify_nonce($_POST['nonce'], 'chatgpt_nonce')) {
            wp_send_json_error('Ungültige Anfrage');
            return;
        }
        
        // Nachricht holen und säubern
        $message = sanitize_text_field($_POST['message']);
        $api_key = get_option('chatgpt_api_key');
        
        // Prüfen ob API-Schlüssel existiert
        if (empty($api_key)) {
            wp_send_json_error('API-Schlüssel fehlt');
            return;
        }
        
        // Prüfen ob Nachricht existiert
        if (empty($message)) {
            wp_send_json_error('Nachricht ist leer');
            return;
        }
        
        // OpenAI API aufrufen
        $response = $this->call_openai_api($message, $api_key);
        
        // Fehler zurückgeben falls vorhanden
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
            return;
        }
        
        // Erfolgreiche Antwort zurückgeben
        wp_send_json_success($response);
    }
    
    // OpenAI API aufrufen
    private function call_openai_api($message, $api_key) {
        // Einstellungen laden
        $model = get_option('chatgpt_model', 'gpt-4o-mini');
        $max_tokens = get_option('chatgpt_max_tokens', 500);
        $temperature = get_option('chatgpt_temperature', 0.7);
        $system_prompt = get_option('chatgpt_system_prompt', 'Du bist ein freundlicher Assistent.');
        
        // Nachrichten-Array erstellen
        $messages = array();
        
        // System-Prompt hinzufügen (wenn vorhanden)
        if (!empty(trim($system_prompt))) {
            $messages[] = array(
                'role' => 'system',
                'content' => $system_prompt
            );
        }
        
        // Benutzer-Nachricht hinzufügen
        $messages[] = array(
            'role' => 'user',
            'content' => $message
        );
        
        // Request-Daten vorbereiten
        $request_data = array(
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => intval($max_tokens),
            'temperature' => floatval($temperature)
        );
        
        // HTTP-Anfrage vorbereiten
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($request_data),
            'timeout' => 60
        );
        
        // An OpenAI senden
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', $args);
        
        // Prüfen: Ist die Anfrage fehlgeschlagen?
        if (is_wp_error($response)) {
            return new WP_Error('network_error', 'Verbindungsfehler: ' . $response->get_error_message());
        }
        
        // HTTP-Status und Antwort holen
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Fehlerbehandlung nach Status-Code
        if ($status_code === 401) {
            return new WP_Error('error', 'Ungültiger API-Schlüssel');
        }
        
        if ($status_code === 429) {
            return new WP_Error('error', 'Zu viele Anfragen');
        }
        
        if ($status_code === 402) {
            return new WP_Error('error', 'Kein Guthaben');
        }
        
        if ($status_code !== 200) {
            return new WP_Error('error', 'Fehler: ' . $status_code);
        }
        
        // Antwort extrahieren
        if (isset($data['choices'][0]['message']['content'])) {
            return trim($data['choices'][0]['message']['content']);
        }
        
        // Falls keine Antwort vorhanden
        return new WP_Error('error', 'Keine Antwort erhalten');
    }
}

// Plugin starten
new WP_ChatGPT_Plugin();