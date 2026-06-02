<?php

class ChatController extends Controller
{
    /**
     * Construct this object by extending the basic Controller class
     */
    public function __construct()
    {
        parent::__construct();
        Auth::checkAuthentication();
    }

    /**
     * This method controls what happens when you move to /overview/index in your app.
     * Shows a list of all users.
     */
    public function index()
    {
        $currentUserID = (int) Session::get('user_id');
        $selectedChatID = (int) Request::get('chatID');
        $messages = null;

        if (!empty($selectedChatID)) {
            // Updates the "last_seen" timestamp for this user in this specific chat
            ChatModel::updateLastSeen($selectedChatID, $currentUserID);
            
            $messages = ChatModel::getChatMessages($selectedChatID, $currentUserID);
        }

        $this->View->render('chat/index', array(
            'chats' => ChatModel::getDirectChatsOfUser($currentUserID),
            'messages' => $messages,
            'selectedChatID' => $selectedChatID
        ));
    }

    public function createDirectChat() {
        $currentUserID = (int) Session::get('user_id');
        $otherUserID = (int) Request::post('user_id');

        if (empty($otherUserID) || $otherUserID === $currentUserID) {
            Redirect::to('profile/index');
            return;
        }

        $chatID = ChatModel::getOrCreateDirectChat($currentUserID, $otherUserID);

        if (!empty($chatID)) {
            Redirect::to('chat/index?chatID=' . $chatID);
            return;
        }

        Redirect::to('profile/index');
    }

    public function sendMessage() {
        $userID = (int) Session::get('user_id');
        $chatID = (int) Request::post('chatID');
        $message = Request::post('messageContent');

        if (empty($chatID)) {
            Redirect::to('chat/index?chatID=' . $chatID);
            return;
        }

        ChatModel::sendMessage($chatID, $userID, $message);
        Redirect::to('chat/index?chatID=' . $chatID);
    }

    /**
     * This method controls what happens when you move to /overview/showProfile in your app.
     * Shows the (public) details of the selected user.
     * @param $user_id int id the the user
     */
    public function showProfile($user_id)
    {
        if (isset($user_id)) {
            $this->View->render('profile/showProfile', array(
                'user' => UserModel::getPublicProfileOfUser($user_id))
            );
        } else {
            Redirect::home();
        }
    }
}
