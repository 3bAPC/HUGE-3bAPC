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
                        </div>
                    <?php } ?>
                </div>
            </div>

            <div class="chat-panel">
                <?php if (!empty($this->messages)) { ?>
                    <!-- Show Messages -->
                    <?php foreach ($this->messages as $message) { ?> 
                        <p><?= $message->content ?></p>
                    <?php } ?>
                <?php } else { ?>
                    <p>No Messages</p>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
