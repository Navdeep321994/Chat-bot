<div class="wrap">
    <h1>Live Chat Bot - Conversations</h1>
    <div id="slcbp-admin-chat-container">
        <div id="slcbp-conversation-list">
            <h3>Active Sessions</h3>
            <ul id="slcbp-sessions">
                <li>Loading...</li>
            </ul>
        </div>
        <div id="slcbp-chat-window" style="display:none;">
            <div id="slcbp-admin-chat-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>Chatting with: <span id="current-session-id"></span></div>
                <button id="slcbp-admin-chat-delete" class="button button-link-delete" style="color: #dc3232; border-color: transparent; background: transparent; cursor: pointer; text-decoration: underline;">Delete Chat</button>
            </div>
            <div id="slcbp-admin-chat-body">
                <!-- Messages will load here -->
            </div>
            <div id="slcbp-admin-chat-footer">
                <input type="text" id="slcbp-admin-chat-input" placeholder="Type your reply..." />
                <button id="slcbp-admin-chat-send" class="button button-primary">Send Reply</button>
            </div>
        </div>
    </div>
</div>
