<div class="container">
    <h1>Up- and Download Images</h1>
    <div class="box">

        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>

        <div class="upload-image-container">
            <form action="<?php echo Config::get('URL'); ?>gallery/prepareImageUpload" method="post" enctype="multipart/form-data">
                Select image to upload:
                <input type="file" name="fileUpload" id="fileUpload"> <!-- accept=".png, .jpg, .jpeg, .webpb, .svg" -->
                <input type="submit" value="Upload Image" name="submit">
            </form>
        </div>
        <div class="gallery-container">
            <?php

                $targetDirectory = dirname(dirname(dirname(__DIR__))) . '/fileUploads/' . Session::get('user_id') . '/';
                $images = glob($targetDirectory . '*.{jpg,jpeg,png,webpb,svg}', GLOB_BRACE);

                if ($images) {
                    foreach($images as $image) {
                        $filename = basename($image);
                        $imageURL = Config::get('URL') . 'fileUploads/' . Session::get('user_id') . '/' . $filename;

                        echo '<img style="max-height: 500px; max-width: 250px" src="'. $imageURL .'" /><br />';
                    }
                } else {
                    echo 'No images';
                }
            ?>
        </div>

    </div>
</div>