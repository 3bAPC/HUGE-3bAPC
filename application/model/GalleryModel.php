<?php

class GalleryModel {

    /**
     * Verify if the file meets every requirenment
     * @return boolean if the file is allowed to upload or not.
     */
    public static function verifyFileUpload() {
        if ($_FILES['fileUpload']['error'] !== UPLOAD_ERR_OK) {
            Session::add('feedback_negative', 'hello');
            return false;
        }

        if ($_FILES['fileUpload']['size'] > 5 * 1024 * 1024) {
            Session::add('feedback_negative', 'The file is too large. The maximum file size sits at 5MB.');
            return false;
        }

        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $fileInfo->file($_FILES['fileUpload']['tmp_name']);
        $accepted = ['image/jpeg', 'image/png', 'image/webp', 'image/svg'];

        if (!in_array($mime, $accepted)) {
            Session::add('feedback_negative', 'This file type is not allowed for image upload.');
            return false;
        }

        return true;
    }

    public static function uploadImage($userID) {
        $database = DatabaseFactory::getFactory()->getConnection();

        $filename = preg_replace('/[^a-zA-Z0-9. -]/', '_', basename($_FILES['fileUpload']['name']));
        $targetDirectory = dirname(__DIR__) . '/fileUploads/' . $userID . '/';
        $targetFile = $targetDirectory  . time() . '_' . $filename;
        $targetFileSize = $_FILES['fileUpload']['size'];

        if (!file_exists($targetDirectory)) {
            mkdir($targetDirectory, 0777, true);
        }

        move_uploaded_file($_FILES['fileUpload']['tmp_name'], $targetFile);

        // Save Image info into database
        $sql = "INSERT INTO gallery (owner, name, size, timestamp)
                VALUES (:owner, :name, :size, NOW())";
        $query = $database->prepare($sql);

        $queryResult = $query->execute(array(
            ':owner' => $userID,
            ':name' => $filename,
            ':size' => $targetFileSize
        ));

        if ($queryResult) return true;
        Session::add('feedback_negative', 'Something went wrong when saving into the database');
        return false;
    }
}