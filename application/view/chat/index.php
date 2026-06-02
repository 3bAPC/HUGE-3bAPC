<div class="container">
    <h1>Chat with your Friends</h1>
    <div class="box">

        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>

        <h3>What happens here ?</h3>
        <div>
            Here you can Chat with your friends or even create chat groups and chat with multiple friends at once!
        </div>
        <br>
        <div class="chat-container">

            <div class="chat-users">
                <div class="chat-user-list">
                    <?php foreach ($this->chats as $chat) { ?>
                        <div class="chat-user<?php if ((int) $this->selectedChatID === (int) $chat->chat_id) { echo ' active'; } ?>">
                            <div class="chat-user-avatar">
                                <?php if (!empty($chat->chat_avatar_link)) { ?>
                                    <img src="<?= $chat->chat_avatar_link; ?>" />
                                <?php } ?>
                            </div>

                            <div class="chat-user-username">
                                <span class="chat-user-name"><?= $chat->chat_name; ?></span>
                            </div>

                            <a href="<?php echo Config::get('URL'); ?>chat/index?chatID=<?php echo $chat->chat_id; ?>" class="chat-button">
                                Chat
                            </a>

                            <!-- Display unread message count with a red border if greater than 0 -->
                            <?php if (isset($chat->unread_count) && (int)$chat->unread_count > 0) : ?>
                                <span class="unread-badge">
                                    <?= $chat->unread_count; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="chat-panel">
                <?php if ($this->selectedChatID > 0) { ?>
                    <?php if (!empty($this->messages)) {
                    $currentUserID = (int) Session::get('user_id');
                    $messageCount = count($this->messages);
                ?>
                <div class="message-container">
                    <?php foreach ($this->messages as $index => $message) {

                        // Message style logic
                        $previousMessage = ($index > 0) ? $this->messages[$index - 1] : null; // GetPrevious Message or set null if first
                        $nextMessage = ($index < ($messageCount - 1)) ? $this->messages[$index + 1] : null; // GetNextMessage or set null if last
                        $isSender = ((int) $message->sent_from_id === $currentUserID); // Check if message is from user logged in for color purposes
                        $messageTypeClass = $isSender ? 'sender' : 'recipient'; // Define message classes for coloring and positon from isSender
                        $hasPreviousFromSameSender = $previousMessage && ((int) $previousMessage->sent_from_id === (int) $message->sent_from_id); // Check if previous message is from same sender for styling
                        $hasNextFromSameSender = $nextMessage && ((int) $nextMessage->sent_from_id === (int) $message->sent_from_id); // Check if next message is from same sender for styling
                        $positionClass = '';

                        // Set message classes
                        if (!$hasPreviousFromSameSender && $hasNextFromSameSender) {
                            $positionClass = ' first';
                        } elseif ($hasPreviousFromSameSender && $hasNextFromSameSender) {
                            $positionClass = ' middle';
                        } elseif ($hasPreviousFromSameSender && !$hasNextFromSameSender) {
                            $positionClass = ' last';
                        }
                    ?>
                    
                    <!-- Display Message -->
                    <div class="bubble <?= $messageTypeClass . $positionClass; ?>"><?= $message->content; ?></div>
                    
                    <?php } ?>
                </div>
                    <?php } else { ?>
                        <p class="empty-message-text">No messages yet. Start the conversation!</p>
                    <?php } ?>

                    <!-- Message Input & Button -->
                    <div class="action-container">
                        <form action="<?php echo Config::get('URL'); ?>chat/sendMessage" method="post">
                            <input type="hidden" name="chatID" value="<?php echo $this->selectedChatID; ?>">

                            <input class="message-input" type="text" name="messageContent" placeholder="Enter Message">
                            <button type="submit">SendMessage</button>
                        </form>
                    </div>
                <?php } else { ?>
                    <div class="test">
                        <p>Select a friend from the list to start chatting.</p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
