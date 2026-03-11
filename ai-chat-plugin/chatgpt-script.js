jQuery(document).ready(function($) {
    
    // Click auf Senden-Button
    $('#chatgpt-send').on('click', function() {
        sendMessage();
    });
    
    // Enter-Taste im Eingabefeld
    $('#chatgpt-input').on('keypress', function(e) {
        if (e.which === 13) { // 13 = Enter
            sendMessage();
        }
    });
    
    // Nachricht senden
    function sendMessage() {
        var message = $('#chatgpt-input').val().trim();
        
        // Prüfen ob leer
        if (message === '') {
            return;
        }
        
        // Nachricht anzeigen
        addMessage('user', message);
        
        // Eingabefeld leeren
        $('#chatgpt-input').val('');
        
        // Button deaktivieren während Request
        $('#chatgpt-send').prop('disabled', true).text('...');
        
        // AJAX Request an WordPress
        $.ajax({
            url: chatgpt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'chatgpt_request',
                message: message,
                nonce: chatgpt_ajax.nonce
            },
            success: function(response) {
                // Button wieder aktivieren
                $('#chatgpt-send').prop('disabled', false).text('Senden');
                
                // Antwort anzeigen
                if (response.success) {
                    addMessage('assistant', response.data);
                } else {
                    addMessage('error', 'Fehler: ' + response.data);
                }
            },
            error: function() {
                // Button wieder aktivieren
                $('#chatgpt-send').prop('disabled', false).text('Senden');
                
                // Fehler anzeigen
                addMessage('error', 'Verbindungsfehler');
            }
        });
    }
    
    // Nachricht zum Chat hinzufügen
    function addMessage(type, content) {
        var messageClass = 'chatgpt-message-' + type;
        var time = new Date().toLocaleTimeString('de-DE', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        var html = '<div class="chatgpt-message ' + messageClass + '">' +
                   '<div class="message-text">' + escapeHtml(content) + '</div>' +
                   '<div class="message-time">' + time + '</div>' +
                   '</div>';
        
        $('#chatgpt-messages').append(html);
        
        // Nach unten scrollen
        var container = $('#chatgpt-messages');
        container.scrollTop(container[0].scrollHeight);
    }
    
    // HTML-Zeichen escapen für Sicherheit
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Fokus auf Eingabefeld
    $('#chatgpt-input').focus();
    
});