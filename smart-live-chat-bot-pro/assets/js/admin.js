jQuery(document).ready(function ($) {
    let currentSession = null;
    let lastAdminMessageId = 0;

    const $sessionsList = $('#slcbp-sessions');
    const $chatWindow = $('#slcbp-chat-window');
    const $chatBody = $('#slcbp-admin-chat-body');
    const $input = $('#slcbp-admin-chat-input');
    const $sendBtn = $('#slcbp-admin-chat-send');
    const $currentSessionSpan = $('#current-session-id');

    let isLoadingConversations = false;
    let isFetchingMessages = false;

    function loadConversations() {
        if (isLoadingConversations) return;
        isLoadingConversations = true;

        $.ajax({
            url: slcbp_admin_ajax.api_url + '/admin/conversations',
            method: 'GET',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', slcbp_admin_ajax.nonce);
            },
            success: function (response) {
                isLoadingConversations = false;
                if (response.status === 'success') {
                    if (response.conversations.length === 0) {
                        $sessionsList.html('<li>No active sessions.</li>');
                        return;
                    }

                    const newIds = response.conversations.map(c => c.session_id);

                    // Remove old sessions
                    $('#slcbp-sessions li').each(function () {
                        const sid = $(this).data('session_id');
                        if (sid && !newIds.includes(sid)) {
                            $(this).remove();
                        }
                        if ($(this).text() === 'Loading...' || $(this).text() === 'No active sessions.') {
                            $(this).remove();
                        }
                    });

                    // Add new sessions safely without destroying the list
                    response.conversations.forEach(conv => {
                        let $li = $('#slcbp-sessions li').filter(function () { return $(this).data('session_id') === conv.session_id; });
                        const displayName = conv.user_name ? `${conv.user_name} (${conv.session_id})` : conv.session_id;

                        if ($li.length === 0) {
                            $li = $('<li></li>')
                                .text(displayName)
                                .data('session_id', conv.session_id)
                                .on('click', function () {
                                    openConversation(conv.session_id, displayName);
                                    $('#slcbp-sessions li').removeClass('active');
                                    $(this).addClass('active');
                                });
                            $sessionsList.append($li);
                        } else {
                            $li.text(displayName);
                        }

                        if (currentSession === conv.session_id) {
                            $li.addClass('active');
                            $currentSessionSpan.text(displayName);
                        }
                    });
                }
            },
            error: function() {
                isLoadingConversations = false;
            }
        });
    }

    function openConversation(sessionId, displayName) {
        currentSession = sessionId;
        $currentSessionSpan.text(displayName || sessionId);
        $chatWindow.show();
        $chatBody.empty();
        lastAdminMessageId = 0;
        fetchAdminMessages();
    }

    function fetchAdminMessages() {
        if (!currentSession) return;
        if (isFetchingMessages) return;
        isFetchingMessages = true;

        const fetchSession = currentSession; // Capture to prevent race condition

        $.ajax({
            url: slcbp_admin_ajax.api_url + '/admin/chat?session_id=' + fetchSession + '&last_id=' + lastAdminMessageId,
            method: 'GET',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', slcbp_admin_ajax.nonce);
            },
            success: function (response) {
                isFetchingMessages = false;
                if (fetchSession !== currentSession) return; // Prevent mixing up chats!

                if (response.status === 'success' && response.messages.length > 0) {
                    response.messages.forEach(msg => {
                        const msgId = parseInt(msg.id, 10);
                        if (msgId > lastAdminMessageId) {
                            lastAdminMessageId = msgId;
                            const msgHtml = `<div class="slcbp-msg ${msg.sender_type}">${msg.message}</div>`;
                            $chatBody.append(msgHtml);
                        }
                    });
                    $chatBody.scrollTop($chatBody[0].scrollHeight);
                }
            },
            error: function() {
                isFetchingMessages = false;
            }
        });
    }

    function sendAdminMessage() {
        if (!currentSession) return;
        const message = $input.val().trim();
        if (!message) return;

        const sendSession = currentSession; // Capture session
        $input.val('');

        $.ajax({
            url: slcbp_admin_ajax.api_url + '/admin/chat',
            method: 'POST',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', slcbp_admin_ajax.nonce);
            },
            data: {
                message: message,
                session_id: sendSession
            },
            success: function (response) {
                if (sendSession !== currentSession) return; // Prevent appending to wrong chat
                if (response.status === 'success') {
                    if (!isFetchingMessages) {
                        fetchAdminMessages();
                    }
                }
            }
        });
    }

    function deleteConversation() {
        if (!currentSession) return;
        if (!confirm('Are you sure you want to permanently delete this chat history?')) return;

        $.ajax({
            url: slcbp_admin_ajax.api_url + '/admin/chat?session_id=' + currentSession,
            method: 'DELETE',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', slcbp_admin_ajax.nonce);
            },
            success: function (response) {
                if (response.status === 'success') {
                    currentSession = null;
                    $chatWindow.hide();
                    loadConversations();
                }
            }
        });
    }

    $('#slcbp-admin-chat-delete').on('click', deleteConversation);

    $sendBtn.on('click', sendAdminMessage);
    $input.on('keypress', function (e) {
        if (e.which == 13) {
            sendAdminMessage();
        }
    });

    loadConversations();
    setInterval(loadConversations, 5000); // refresh list every 5s
    
    function scheduleAdminPoll() {
        // Poll faster if we are in an active session, otherwise slow down
        let interval = currentSession ? 500 : 10000;
        setTimeout(function() {
            if (currentSession) {
                fetchAdminMessages();
            }
            scheduleAdminPoll();
        }, interval);
    }
    scheduleAdminPoll();
});
