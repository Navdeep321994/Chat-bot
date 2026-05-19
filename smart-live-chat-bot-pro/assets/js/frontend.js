jQuery(document).ready(function($) {
    let sessionId = localStorage.getItem('slcbp_session_id');
    if (!sessionId) {
        sessionId = slcbp_ajax.new_session_id;
        localStorage.setItem('slcbp_session_id', sessionId);
    }

    let lastMessageId = 0;

    let userName = localStorage.getItem('slcbp_user_name');
    
    // If returning user, hide welcome message
    if (userName) {
        $('#slcbp-welcome-msg').hide();
    }

    const $widget = $('#slcbp-chat-widget');
    const $launcher = $('#slcbp-chat-launcher');
    const $closeBtn = $('#slcbp-close-btn');
    const $input = $('#slcbp-chat-input');
    const $sendBtn = $('#slcbp-chat-send');
    const $body = $('#slcbp-chat-body');

    $closeBtn.on('click', function() {
        $widget.hide();
        $launcher.show();
    });

    function appendMessage(text, sender) {
        const msgHtml = `<div class="slcbp-message ${sender}">${text}</div>`;
        $body.append(msgHtml);
        $body.scrollTop($body[0].scrollHeight);
    }

    let isFetchingMessages = false;
    let initialFetchDone = false;

    function fetchMessages() {
        if (isFetchingMessages) return;
        isFetchingMessages = true;

        $.ajax({
            url: slcbp_ajax.api_url + '?session_id=' + sessionId + '&last_id=' + lastMessageId,
            method: 'GET',
            success: function(response) {
                isFetchingMessages = false;
                if (response.status === 'success') {
                    if (response.messages && response.messages.length > 0) {
                        let hasNewMessage = false;
                        response.messages.forEach(msg => {
                            const msgId = parseInt(msg.id, 10);
                            if (msgId > lastMessageId) {
                                lastMessageId = msgId;
                                
                                if (!initialFetchDone) {
                                    appendMessage(msg.message, msg.sender_type === 'user' ? 'user' : 'bot');
                                } else {
                                    // Only append if it's from the bot (admin), user messages are appended on send
                                    if (msg.sender_type !== 'user') {
                                        appendMessage(msg.message, 'bot');
                                        hasNewMessage = true;
                                    }
                                }
                            }
                        });
                        
                        // If widget is closed and admin sent message, you could add notification badge here
                        if (hasNewMessage && $widget.is(':hidden')) {
                            $launcher.css('background-color', '#dc3545'); // Turn red to alert user
                        }
                    }
                    initialFetchDone = true;
                }
            },
            error: function() {
                isFetchingMessages = false;
            }
        });
    }

    function scheduleNextPoll() {
        let interval = $widget.is(':hidden') ? 10000 : 500; // 10s if closed, 500ms if open
        setTimeout(function() {
            fetchMessages();
            scheduleNextPoll();
        }, interval);
    }
    scheduleNextPoll();

    $launcher.on('click', function() {
        $widget.css('display', 'flex');
        $launcher.hide();
        $launcher.css('background-color', '#007bff'); // Reset color
        $body.scrollTop($body[0].scrollHeight);
        fetchMessages();
    });

    function sendMessage() {
        const message = $input.val().trim();
        if (!message) return;

        appendMessage(message, 'user');
        $input.val('');

        if (!userName) {
            userName = message;
            localStorage.setItem('slcbp_user_name', userName);
            
            $.ajax({
                url: slcbp_ajax.api_url.replace('/chat', '/set_name'),
                method: 'POST',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', slcbp_ajax.nonce);
                },
                data: {
                    name: userName,
                    session_id: sessionId
                },
                success: function() {
                    appendMessage("Thank you, " + userName + "! An admin will be with you shortly.", 'bot');
                }
            });
            return;
        }

        $.ajax({
            url: slcbp_ajax.api_url,
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', slcbp_ajax.nonce);
            },
            data: {
                message: message,
                session_id: sessionId
            },
            success: function(response) {
                // Sent successfully
            }
        });
    }

    $sendBtn.on('click', sendMessage);
    $input.on('keypress', function(e) {
        if (e.which == 13) {
            sendMessage();
        }
    });
});
