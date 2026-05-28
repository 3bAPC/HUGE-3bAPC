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
                    <?php foreach ($this->users as $user) { ?>
                        <form action="index" method="put">
                            
                            <input type="hidden" name="chatID" value="<?= $user->user_id ?>">

                            <div class="chat-user">
                                <div class="chat-user-avatar">
                                    <?php if (isset($user->user_avatar_link)) { ?>
                                        <img src="<?= $user->user_avatar_link; ?>" />
                                    <?php } ?>
                                </div>

                                <div class="chat-user-username">
                                    <span class="chat-user-name"><?= $user->user_name; ?></span>
                                </div>

                                <button type="submit" class="chat-button">Chat</button>
                            </div>
                        </form>
                    <?php } ?>
                </div>
            </div>

            <div class="chat-panel">
            </div>
        </div>
    </div>
</div>
