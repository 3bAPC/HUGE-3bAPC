<div class="container">
    <h1>Up- and Download Images</h1>
    <div class="box">

        <!-- echo out the system feedback (error and success messages) -->
        <?php $this->renderFeedbackMessages(); ?>

        <div>
            <form action="<?php echo Config::get('URL'); ?>gallery/prepareImageUpload" method="post" enctype="multipart/form-data">
                Select image to upload:
                <input type="file" name="fileUpload" id="fileUpload"> <!-- accept=".png, .jpg, .jpeg, .webpb, .svg" -->
                <input type="submit" value="Upload Image" name="submit">
            </form>
        </div>

    </div>
</div>