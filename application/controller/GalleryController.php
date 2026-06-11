<?php

class GalleryController extends Controller
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
    public function index() {
        $this->View->render('gallery/index');
    }

    /**
     * Prepare the image upload and call function in the Model to upload the image correctly.
     * @return void
     */
    public function prepareImageUpload() {
        $userID = (int) Session::get('user_id');

        if (!GalleryModel::verifyFileUpload()) {
            Redirect::to('gallery/index');
            return;
        }

        GalleryModel::uploadImage($userID);
        Redirect::to('gallery/index');

    }

    /**
     * Securely serves an image, ensuring only the correct user can access it.
     */
    public function showImage($filename) {
        $userID = (int) Session::get('user_id');
        $cleanFilename = basename(urldecode($filename));
        $filePath = dirname(dirname(__DIR__)) . '/fileUploads/' . $userID . '/' . $cleanFilename;

        if (file_exists($filePath)) {
            $mime = mime_content_type($filePath);
            header('Content-Type: ' . $mime);
            readfile($filePath);
        } else {
            header("HTTP/1.0 404 Not Found");
            echo "Image not found or access denied.";
        }
    }

}