<div class="wrap">
    <h2 class="response-heading">DESTINATION DATABASE!</h2>
    <div class="responsemessage">
        <?php
        $getCdbSourceUrl = get_option('cdb_source_url');
        if ($getCdbSourceUrl === false) { ?>
            <form method="post">
                <input required style="width: 400px" type="text" name="source_url"
                       placeholder="Enter source website address (e.g. www.example.rs)"><br>
                <input type="submit" value="Insert url">
            </form><?php } else { ?>
            <div><p>Your source website url is <strong><?php echo $getCdbSourceUrl; ?></strong></p></div>
            <div class="change-url-holder">
                <form method="post">
                    <input required style="width: 400px" type="text" name="source_url"
                           placeholder="Enter new source website address (e.g. www.example.rs)"
                           value="<?php echo $getCdbSourceUrl; ?>">
                    <input type="submit" value="Editurl">
                </form>
            </div>
        <?php } ?>
    </div>
    <div class="response-holder">
        <?php if ($getCdbSourceUrl != '') { ?>
            <button id="pull-database">Import database</button><?php } ?>
        <h4>If you want to change database destination to SOURCE click button bellow:</h4>
        <a class="button" data-id="source-database" href="javascript:void(0)">Make databes of this website as SOURCE
            databese.</a><br>
    </div>
    <div id="responsebox"></div>
</div>