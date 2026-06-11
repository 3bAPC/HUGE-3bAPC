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
    public function index()
    {
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

}