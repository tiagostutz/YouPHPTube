<?php
require_once '../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/user.php';
if (!User::canUpload()) {
    header("Location: {$global['webSiteRootURL']}?error=" . __("You can not manager videos"));
    exit;
}
require_once $global['systemRootPath'] . 'objects/category.php';
require_once $global['systemRootPath'] . 'objects/video.php';
$categories = Category::getAllCategories();

require_once $global['systemRootPath'] . 'objects/userGroups.php';
$userGroups = UserGroups::getAllUsersGroups();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
    <head>
        <title><?php echo $config->getWebSiteTitle(); ?> :: <?php echo __("Videos"); ?></title>
        <?php
        include $global['systemRootPath'] . 'view/include/head.php';
        ?>
        <link href="<?php echo $global['webSiteRootURL']; ?>js/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css" rel="stylesheet" type="text/css"/>
    </head>

    <body>
        <?php
        include 'include/navbar.php';
        ?>

        <div class="container">

            <div class="btn-group" >
                <a href="<?php echo $global['webSiteRootURL']; ?>usersGroups" class="btn btn-warning">
                    <span class="fa fa-users"></span> <?php echo __("User Groups"); ?>
                </a>
                <a href="<?php echo $global['webSiteRootURL']; ?>users" class="btn btn-primary">
                    <span class="fa fa-user"></span> <?php echo __("Users"); ?>
                </a>
                <a href="<?php echo $global['webSiteRootURL']; ?>charts" class="btn btn-info">
                    <span class="fa fa-bar-chart"></span> 
                    <?php echo __("Video Chart"); ?>
                </a>
                <a href="<?php echo $config->getEncoderURL(), "?webSiteRootURL=", urlencode($global['webSiteRootURL']), "&user=", urlencode(User::getUserName()), "&pass=", urlencode(User::getUserPass()) ; ?>" class="btn btn-default">
                    <span class="fa fa-upload"></span> 
                    <?php echo __("Encoder Site"); ?>
                </a>

                <?php
                if (User::isAdmin()) {
                    ?>
                    <a href="<?php echo $global['webSiteRootURL']; ?>ads" class="btn btn-danger">
                        <span class="fa fa-money"></span> <?php echo __("Advertising Manager"); ?>
                    </a>
                    <?php
                }
                ?>
            </div>
            <small class="text-muted clearfix">
                <?php
                $secondsTotal = getSecondsTotalVideosLength();
                $seconds = $secondsTotal % 60;
                $minutes = ($secondsTotal - $seconds) / 60;
                printf(__("You are hosting %d minutes and %d seconds of video"), $minutes, $seconds);
                ?>
            </small>
            <?php
            if (!empty($global['videoStorageLimitMinutes'])) {
                $secondsLimit = $global['videoStorageLimitMinutes'] * 60;
                if ($secondsLimit > $secondsTotal) {

                    $percent = intval($secondsTotal / $secondsLimit * 100);
                } else {
                    $percent = 100;
                }
                ?> and you have <?php echo $global['videoStorageLimitMinutes']; ?> minutes of storage
                <div class="progress">
                    <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" 
                         aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?php echo $percent; ?>%">
                        <?php echo $percent; ?>% of your storage limit used
                    </div>
                </div>
                <?php
            }
            ?>

            <table id="grid" class="table table-condensed table-hover table-striped">
                <thead>
                    <tr>
                        <th data-column-id="title" data-formatter="titleTag" ><?php echo __("Title"); ?></th>
                        <th data-column-id="tags" data-formatter="tags" data-sortable="false" data-width="210px"><?php echo __("Tags"); ?></th>
                        <th data-column-id="duration" data-width="100px"><?php echo __("Duration"); ?></th>
                        <th data-column-id="created" data-order="desc" data-width="100px"><?php echo __("Created"); ?></th>
                        <th data-column-id="commands" data-formatter="commands" data-sortable="false"  data-width="200px"></th>
                    </tr>
                </thead>
            </table>

            <div id="videoFormModal" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title"><?php echo __("Video Form"); ?></h4>
                        </div>
                        <div class="modal-body">
                            <form class="form-compact"  id="updateCategoryForm" onsubmit="">
                                <input type="hidden" id="inputVideoId"  >
                                <label for="inputTitle" class="sr-only"><?php echo __("Title"); ?></label>
                                <input type="text" id="inputTitle" class="form-control first" placeholder="<?php echo __("Title"); ?>" required autofocus>
                                <label for="inputCleanTitle" class="sr-only"><?php echo __("Clean Title"); ?></label>
                                <input type="text" id="inputCleanTitle" class="form-control" placeholder="<?php echo __("Clean Title"); ?>" required>
                                <label for="inputDescription" class="sr-only"><?php echo __("Description"); ?></label>
                                <textarea id="inputDescription" class="form-control" placeholder="<?php echo __("Description"); ?>" required></textarea>
                                <label for="inputCategory" class="sr-only"><?php echo __("Category"); ?></label>
                                <select class="form-control last" id="inputCategory" required>
                                    <?php
                                    foreach ($categories as $value) {
                                        echo "<option value='{$value['id']}'>{$value['name']}</option>";
                                    }
                                    ?>
                                </select>
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <span class="fa fa-globe"></span> <?php echo __("Public Video"); ?>
                                        <div class="material-switch pull-right">
                                            <input id="public" type="checkbox" value="0" class="userGroups"/>
                                            <label for="public" class="label-success"></label>
                                        </div>
                                    </li>
                                    <li class="list-group-item active non-public">
                                        <?php echo __("Groups that can see this video"); ?>
                                        <a href="#" class="btn btn-info btn-xs pull-right" data-toggle="popover" title="<?php echo __("What is User Groups"); ?>" data-placement="bottom"  data-content="<?php echo __("By linking groups to this video, it will no longer be public and only users in the same group will be able to watch this video"); ?>"><span class="fa fa-question-circle" aria-hidden="true"></span> <?php echo __("Help"); ?></a>
                                    </li>
                                    <?php
                                    foreach ($userGroups as $value) {
                                        ?>
                                        <li class="list-group-item non-public">
                                            <span class="fa fa-lock"></span>
                                            <?php echo $value['group_name']; ?>
                                            <span class="label label-info"><?php echo $value['total_users']; ?> Users linked</span>
                                            <div class="material-switch pull-right">
                                                <input id="videoGroup<?php echo $value['id']; ?>" type="checkbox" value="<?php echo $value['id']; ?>" class="videoGroups"/>
                                                <label for="videoGroup<?php echo $value['id']; ?>" class="label-warning"></label>
                                            </div>
                                        </li>
                                        <?php
                                    }
                                    ?>
                                </ul>

                                <?php
                                if (User::isAdmin()) {
                                    ?>

                                    <ul class="list-group">
                                        <li class="list-group-item">
                                            <a href="#" class="btn btn-info btn-xs" data-toggle="popover" title="<?php echo __("What is this"); ?>" data-placement="bottom"  data-content="<?php echo __("This video will work as an advertising and will no longer appear on videos list"); ?>"><span class="fa fa-question-circle" aria-hidden="true"></span> <?php echo __("Help"); ?></a>
                                            <?php echo __("Create an Advertising"); ?>
                                            <div class="material-switch pull-right">
                                                <input id="videoIsAd" type="checkbox" value="0" class="userGroups"/>
                                                <label for="videoIsAd" class="label-success"></label>
                                            </div>
                                        </li>
                                        <li class="list-group-item videoIsAdContent" style="display: none">
                                            <label for="inputAdTitle" class="sr-only"><?php echo __("Advertising Title"); ?></label>
                                            <input type="text" id="inputAdTitle" class="form-control first" placeholder="<?php echo __("Advertising Title"); ?>" required autofocus>
                                            <label for="inputAdUrlRedirect" class="sr-only"><?php echo __("URL"); ?></label>
                                            <input type="url" id="inputAdUrlRedirect" class="form-control last" placeholder="<?php echo __("URL"); ?>" required autofocus>

                                            <label for="inputAdStarts" class="sr-only"><?php echo __("Starts on"); ?></label>
                                            <input type="text" id="inputAdStarts" class="form-control datepicker" placeholder="<?php echo __("Starts on"); ?>" required autofocus>
                                            <small>Leave Blank for Right Now</small>
                                            <label for="inputAdFinish" class="sr-only"><?php echo __("Finish on"); ?></label>
                                            <input type="text" id="inputAdFinish" class="form-control datepicker" placeholder="<?php echo __("Finish on"); ?>" required autofocus>
                                            <small>Leave Blank for Never</small>

                                            <label for="inputAdSkip" class="sr-only"><?php echo __("Skip Button appears after (X) seconds"); ?></label>
                                            <input type="number" id="inputAdSkip" class="form-control " placeholder="<?php echo __("Skip Button appears after (X) seconds"); ?>" required autofocus>
                                            <small>Leave blank for since begin or put a number of seconds bigger the the ad for never</small>


                                            <label for="inputAdClick" class="sr-only"><?php echo __("Stop ad after (X) clicks"); ?></label>
                                            <input type="number" id="inputAdClick" class="form-control " placeholder="<?php echo __("Stop ad after (X) clicks"); ?>" required autofocus>
                                            <small>Leave Blank for Never</small>

                                            <label for="inputAdPrints" class="sr-only"><?php echo __("Stop ad after (X) prints"); ?></label>
                                            <input type="number" id="inputAdPrints" class="form-control " placeholder="<?php echo __("Stop ad after (X) prints"); ?>" required autofocus>
                                            <small>Leave Blank for Never</small>

                                            <label for="inputAdCategory" class="sr-only"><?php echo __("Category to display this Ad"); ?></label>
                                            <select class="form-control last" id="inputAdCategory" required>
                                                <?php
                                                foreach ($categories as $value) {
                                                    echo "<option value='{$value['id']}'>{$value['name']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </li>
                                    </ul>

                                    <?php
                                }
                                ?>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo __("Close"); ?></button>
                            <button type="button" class="btn btn-primary" id="saveCategoryBtn"><?php echo __("Save changes"); ?></button>
                        </div>
                    </div><!-- /.modal-content -->
                </div><!-- /.modal-dialog -->
            </div><!-- /.modal -->
            <?php
            if (User::isAdmin()) {
                ?>
                <div class="alert alert-info">
                    <h1><span class="fa fa-youtube"></span> Let us upload your video to YouTube</h1>
                    <h2>Before you start</h2>
                    <ol>
                        <li>
                            <a href="<?php echo $global['webSiteRootURL']; ?>siteConfigurations" class="btn btn-info btn-xs">Enable Google Login</a> and get your google ID and Key
                        </li>
                        <li>
                            Go to https://console.developers.google.com
                            on <a href="https://console.developers.google.com/apis/dashboard" class="btn btn-info btn-xs" target="_blank">dashboard</a> Enable <strong>YouTube Data API v3</strong>
                        </li>
                        <li>
                            In credentials authorized this redirect URIs <code><?php echo $global['webSiteRootURL']; ?>objects/youtubeUpload.json.php</code>
                        </li>
                        <li>
                            You can find more help on <a href="https://developers.google.com/youtube/v3/getting-started" class="btn btn-info btn-xs"  target="_blank">https://developers.google.com/youtube/v3/getting-started </a>
                        </li>
                    </ol>

                </div>
                <?php
            }
            ?>
        </div><!--/.container-->

        <?php
        include 'include/footer.php';
        ?>
        <script src="<?php echo $global['webSiteRootURL']; ?>js/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js" type="text/javascript"></script>
        <script type="text/javascript" src="<?php echo $global['webSiteRootURL']; ?>monitor/gauge/jquery-asPieProgress.js"></script>

        <script>
            var timeOut;
            var encodingNowId = "";
            function checkProgress() {
                $.ajax({
                    url: '<?php echo $config->getEncoderURL(); ?>status',
                    success: function (response) {
                        if (response.queue_list.length) {
                            for (i = 0; i < response.queue_list.length; i++) {
                                if('<?php echo $global['webSiteRootURL']; ?>'!==response.queue_list[i].streamer_site){
                                    continue;
                                }
                                createQueueItem(response.queue_list[i], i);
                            }

                        }
                        if (response.encoding && '<?php echo $global['webSiteRootURL']; ?>'===response.encoding.streamer_site) {
                            var id = response.encoding.id;
                            // if start encode next before get 100%
                            if (id !== encodingNowId) {
                                $("#encodeProgress" + encodingNowId).slideUp("normal", function () {
                                    $(this).remove();
                                });
                                encodingNowId = id;
                            }

                            $("#downloadProgress" + id).slideDown();

                            if (response.download_status && !response.encoding_status.progress) {
                                $("#encodingProgress" + id).find('.progress-completed').html("<strong>" + response.encoding.name + " [Downloading ...] </strong> " + response.download_status.progress + '%');
                            } else {
                                $("#encodingProgress" + id).find('.progress-completed').html("<strong>" + response.encoding.name + "[" + response.encoding_status.from + " to " + response.encoding_status.to + "] </strong> " + response.encoding_status.progress + '%');
                                $("#encodingProgress" + id).find('.progress-bar').css({'width': response.encoding_status.progress + '%'});
                            }
                            if (response.download_status) {
                                $("#downloadProgress" + id).find('.progress-bar').css({'width': response.download_status.progress + '%'});
                            }
                            if (response.encoding_status.progress >= 100) {
                                $("#encodingProgress" + id).find('.progress-bar').css({'width': '100%'});
                                clearTimeout(timeOut);
                                timeOut = setTimeout(function () {
                                    $("#grid").bootgrid('reload');
                                }, 2000);
                            } else {

                            }

                            setTimeout(function () {
                                checkProgress();
                            }, 1000);
                        } else if (encodingNowId !== "") {
                            $("#encodeProgress" + encodingNowId).slideUp("normal", function () {
                                $(this).remove();
                            });
                            encodingNowId = "";
                            setTimeout(function () {
                                checkProgress();
                            }, 5000);
                        } else {
                            setTimeout(function () {
                                checkProgress();
                            }, 5000);
                        }

                    }
                });
            }

            function createQueueItem(queueItem, position) {
                var id = queueItem.return_vars.videos_id;
                if ($('#encodeProgress' + id).children().length) {
                    return false;
                }
                var item = '<div class="progress progress-striped active " id="encodingProgress' + queueItem.id + '" style="margin: 0;">';
                item += '<div class="progress-bar  progress-bar-success" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;"><span class="sr-only">0% Complete</span></div>';
                item += '<span class="progress-type"><span class="badge "><?php echo __("Queue Position"); ?> '+position+'</span></span><span class="progress-completed">' + queueItem.name + '</span>';
                item += '</div><div class="progress progress-striped active " id="downloadProgress' + queueItem.id + '" style="height: 10px;"><div class="progress-bar  progress-bar-danger" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0;"></div></div> ';
                
                $('#encodeProgress' + id).html(item);
            }
            $(document).ready(function () {

                $('.datepicker').datetimepicker({
                    format: 'yyyy-mm-dd hh:ii',
                    autoclose: true
                });
                $('#public').change(function () {
                    if ($('#public').is(':checked')) {
                        $('.non-public').slideUp();
                    } else {
                        $('.non-public').slideDown();
                    }
                });

                $('#videoIsAd').change(function () {
                    if (!$('#videoIsAd').is(':checked')) {
                        $('.videoIsAdContent').slideUp();
                    } else {
                        $('.videoIsAdContent').slideDown();
                    }
                });

                $('[data-toggle="tooltip"]').tooltip();
                var grid = $("#grid").bootgrid({
                    ajax: true,
                    url: "<?php echo $global['webSiteRootURL'] . "videos.json"; ?>",
                    formatters: {
                        "commands": function (column, row)
                        {
                            var editBtn = '<button type="button" class="btn btn-xs btn-default command-edit" data-row-id="' + row.id + '" data-toggle="tooltip" data-placement="left" title="<?php echo str_replace("'", "\\'", __("Edit")); ?>"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></button>'
                            var deleteBtn = '<button type="button" class="btn btn-default btn-xs command-delete"  data-row-id="' + row.id + '"  data-toggle="tooltip" data-placement="left" title="<?php echo str_replace("'", "\\'", __("Delete")); ?>"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>';
                            var inactiveBtn = '<button style="color: #090" type="button" class="btn btn-default btn-xs command-inactive"  data-row-id="' + row.id + '"  data-toggle="tooltip" data-placement="left" title="<?php echo str_replace("'", "\\'", __("Inactivate")); ?>"><span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></button>';
                            var activeBtn = '<button style="color: #A00" type="button" class="btn btn-default btn-xs command-active"  data-row-id="' + row.id + '"  data-toggle="tooltip" data-placement="left" title="<?php echo str_replace("'", "\\'", __("Activate")); ?>"><span class="glyphicon glyphicon-eye-close" aria-hidden="true"></span></button>';
                            var rotateLeft = '<button type="button" class="btn btn-default btn-xs command-rotate"  data-row-id="left"  data-toggle="tooltip" data-placement="left" title="<?php echo str_replace("'", "\\'", __("Rotate LEFT")); ?>"><span class="fa fa-undo" aria-hidden="true"></span></button>';
                            var rotateRight = '<button type="button" class="btn btn-default btn-xs command-rotate"  data-row-id="right"  data-toggle="tooltip" data-placement="left" title="<?php echo str_replace("'", "\\'", __("Rotate RIGHT")); ?>"><span class="fa fa-repeat " aria-hidden="true"></span></button>';
                            var rotateBtn = "<br>" + rotateLeft + rotateRight;
                            if (row.type == "audio") {
                                rotateBtn = "";
                            }
                            var status;

                            if (row.status == "i") {
                                status = activeBtn;
                            } else if (row.status == "a") {
                                status = inactiveBtn;
                            } else if (row.status == "x") {
                                return editBtn + deleteBtn;
                            } else if (row.status == "d") {
                                return deleteBtn;
                            } else {
                                return editBtn + deleteBtn;
                            }
                            return editBtn + deleteBtn + status + rotateBtn;
                        },
                        "tags": function (column, row) {
                            var tags = "";
                            for (var i in row.tags) {
                                if (typeof row.tags[i].type == "undefined") {
                                    continue;
                                }
                                tags += "<span class='label label-primary fix-width'>" + row.tags[i].label + ": </span><span class=\"label label-" + row.tags[i].type + " fix-width\">" + row.tags[i].text + "</span><br>";
                            }
                            return tags;
                        },
                        "titleTag": function (column, row) {
                            var tags = "";
                            var youTubeLink = "", youTubeUpload = "";
                            youTubeUpload = '<button type="button" class="btn btn-danger btn-xs command-uploadYoutube"  data-toggle="tooltip" data-placement="left" title="<?php echo str_replace("'", "\\'", __("Upload to YouTube")); ?>"><span class="fa fa-upload " aria-hidden="true"></span></button>';
                            if (row.youtubeId) {
                                //youTubeLink += '<a href=\'https://youtu.be/' + row.youtubeId + '\' target=\'_blank\'  class="btn btn-primary" data-toggle="tooltip" data-placement="left" title="<?php echo str_replace("'", "\\'", __("Watch on YouTube")); ?>"><span class="fa fa-external-link " aria-hidden="true"></span></a>';
                            }
                            var yt = '<br><div class="btn-group" role="group" ><a class="btn btn-default  btn-xs" disabled><span class="fa fa-youtube-play" aria-hidden="true"></span> YouTube</a> ' + youTubeUpload + youTubeLink + ' </div>';
                            if (row.status == "d" || row.status == "e") {
                                yt = "";
                            }
                            tags += '<div id="encodeProgress' + row.id + '"></div>';
                            if (/^x.*$/gi.test(row.status) || row.status == 'e') {
                                //tags += '<div class="progress progress-striped active" style="margin:5px;"><div id="encodeProgress' + row.id + '" class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0px"></div></div>';


                            } else if (row.status == 'd') {
                                tags += '<div class="progress progress-striped active" style="margin:5px;"><div id="downloadProgress' + row.id + '" class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0px"></div></div>';

                            }
                            var type, img, is_portrait;
                            if (row.type === "audio") {
                                type = "<span class='fa fa-headphones' style='font-size:14px;'></span> ";
                                img = "<img class='img img-responsive img-thumbnail pull-left rotate" + row.rotation + "' src='<?php echo $global['webSiteRootURL']; ?>view/img/audio_wave.jpg' style='max-height:80px; margin-right: 5px;'> ";
                            } else {
                                type = "<span class='fa fa-film' style='font-size:14px;'></span> ";
                                is_portrait = (row.rotation === "90" || row.rotation === "270") ? "img-portrait" : "";
                                img = "<img class='img img-responsive " + is_portrait + " img-thumbnail pull-left rotate" + row.rotation + "' src='<?php echo $global['webSiteRootURL']; ?>videos/" + row.filename + ".jpg'  style='max-height:80px; margin-right: 5px;'> ";
                            }
                            return img + '<a href="<?php echo $global['webSiteRootURL']; ?>video/' + row.clean_title + '" class="btn btn-default btn-xs">' + type + row.title + "</a>" + tags + "" + yt;
                        }


                    }
                }).on("loaded.rs.jquery.bootgrid", function () {
                    /* Executes after data is loaded and rendered */
                    grid.find(".command-edit").on("click", function (e) {
                        var row_index = $(this).closest('tr').index();
                        var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                        console.log(row);

                        $('#inputVideoId').val(row.id);
                        $('#inputTitle').val(row.title);
                        $('#inputCleanTitle').val(row.clean_title);
                        $('#inputDescription').val(row.description);
                        $('#inputCategory').val(row.categories_id);
                        $('.videoGroups').prop('checked', false);
                        if (row.groups.length === 0) {
                            $('#public').prop('checked', true);
                        } else {
                            $('#public').prop('checked', false);
                            for (var index in row.groups) {
                                $('#videoGroup' + row.groups[index].id).prop('checked', true);
                            }
                        }
                        $('#public').trigger("change");

                        $('#videoIsAd').prop('checked', false);
                        $('#videoIsAd').trigger("change");
                        $('#videoFormModal').modal();
                    }).end().find(".command-delete").on("click", function (e) {
                        var row_index = $(this).closest('tr').index();
                        var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                        console.log(row);
                        swal({
                            title: "<?php echo __("Are you sure?"); ?>",
                            text: "<?php echo __("You will not be able to recover this video!"); ?>",
                            type: "warning",
                            showCancelButton: true,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "<?php echo __("Yes, delete it!"); ?>",
                            closeOnConfirm: false
                        },
                                function () {

                                    modal.showPleaseWait();
                                    $.ajax({
                                        url: 'deleteVideo',
                                        data: {"id": row.id},
                                        type: 'post',
                                        success: function (response) {
                                            if (response.status === "1") {
                                                $("#grid").bootgrid("reload");
                                                swal("<?php echo __("Success"); ?>", "<?php echo __("Your video has been deleted"); ?>", "success");
                                            } else {
                                                swal("<?php echo __("Sorry!"); ?>", "<?php echo __("Your video has NOT been deleted!"); ?>", "error");
                                            }
                                            modal.hidePleaseWait();
                                        }
                                    });
                                });
                    })
                            .end().find(".command-refresh").on("click", function (e) {
                        var row_index = $(this).closest('tr').index();
                        var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                        modal.showPleaseWait();
                        $.ajax({
                            url: 'refreshVideo',
                            data: {"id": row.id},
                            type: 'post',
                            success: function (response) {
                                $("#grid").bootgrid("reload");
                                modal.hidePleaseWait();
                            }
                        });
                    })
                            .end().find(".command-active").on("click", function (e) {
                        var row_index = $(this).closest('tr').index();
                        var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                        modal.showPleaseWait();
                        $.ajax({
                            url: 'setStatusVideo',
                            data: {"id": row.id, "status": "a"},
                            type: 'post',
                            success: function (response) {
                                $("#grid").bootgrid("reload");
                                modal.hidePleaseWait();
                            }
                        });
                    })
                            .end().find(".command-inactive").on("click", function (e) {
                        var row_index = $(this).closest('tr').index();
                        var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                        modal.showPleaseWait();
                        $.ajax({
                            url: 'setStatusVideo',
                            data: {"id": row.id, "status": "i"},
                            type: 'post',
                            success: function (response) {
                                $("#grid").bootgrid("reload");
                                modal.hidePleaseWait();
                            }
                        });
                    })
                            .end().find(".command-rotate").on("click", function (e) {
                        var row_index = $(this).closest('tr').index();
                        var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                        modal.showPleaseWait();
                        $.ajax({
                            url: 'rotateVideo',
                            data: {"id": row.id, "type": $(this).attr('data-row-id')},
                            type: 'post',
                            success: function (response) {
                                $("#grid").bootgrid("reload");
                                modal.hidePleaseWait();
                            }
                        });
                    })
                            .end().find(".command-reencode").on("click", function (e) {
                        var row_index = $(this).closest('tr').index();
                        var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                        modal.showPleaseWait();
                        $.ajax({
                            url: 'reencodeVideo',
                            data: {"id": row.id, "status": "i", "type": $(this).attr('data-row-id')},
                            type: 'post',
                            success: function (response) {
                                modal.hidePleaseWait();
                                if (response.error) {
                                    swal("<?php echo __("Sorry!"); ?>", response.error, "error");
                                } else {
                                    $("#grid").bootgrid("reload");
                                }
                            }
                        });
                    })
                            .end().find(".command-uploadYoutube").on("click", function (e) {
                        var row_index = $(this).closest('tr').index();
                        var row = $("#grid").bootgrid("getCurrentRows")[row_index];
                        modal.showPleaseWait();
                        $.ajax({
                            url: 'youtubeUpload',
                            data: {"id": row.id},
                            type: 'post',
                            success: function (response) {
                                console.log(response);
                                modal.hidePleaseWait();
                                if (!response.success) {
                                    swal({
                                        title: "<?php echo __("Sorry!"); ?>",
                                        text: response.msg,
                                        type: "error",
                                        html: true
                                    });
                                } else {
                                    swal({
                                        title: "<?php echo __("Success!"); ?>",
                                        text: response.msg,
                                        type: "success",
                                        html: true
                                    });
                                    $("#grid").bootgrid("reload");
                                }
                            }
                        });
                    });
                    $('.pie').asPieProgress({});
                    setTimeout(function () {
                        checkProgress()
                    }, 500);
                });

                $('#inputCleanTitle').keyup(function (evt) {
                    $('#inputCleanTitle').val(clean_name($('#inputCleanTitle').val()));
                });

                $('#inputTitle').keyup(function (evt) {
                    $('#inputCleanTitle').val(clean_name($('#inputTitle').val()));
                });

                $('#addCategoryBtn').click(function (evt) {
                    $('#inputCategoryId').val('');
                    $('#inputName').val('');
                    $('#inputCleanName').val('');

                    $('#videoFormModal').modal();
                });

                $('#saveCategoryBtn').click(function (evt) {
                    $('#updateCategoryForm').submit();
                });

                $('#updateCategoryForm').submit(function (evt) {
                    evt.preventDefault();
                    var isPublic = $('#public').is(':checked');
                    var selectedVideoGroups = [];
                    var isAd = $('#videoIsAd').is(':checked');
                    var adElements = {};
                    if (isAd) {
                        adElements = {
                            title: $('#inputAdTitle').val(),
                            starts: $('#inputAdStarts').val(),
                            finish: $('#inputAdFinish').val(),
                            skipSeconds: $('#inputAdSkip').val(),
                            clicks: $('#inputAdClick').val(),
                            prints: $('#inputAdPrints').val(),
                            categories_id: $('#inputAdCategory').val(),
                            redirect: $('#inputAdUrlRedirect').val()
                        }
                    }
                    $('.videoGroups:checked').each(function () {
                        selectedVideoGroups.push($(this).val());
                    });
                    if (!isPublic && selectedVideoGroups.length === 0) {
                        swal("<?php echo __("Sorry!"); ?>", "<?php echo __("You must make this video public or select a group to see your video!"); ?>", "error");
                        return false;
                    }
                    if (isPublic) {
                        selectedVideoGroups = [];
                    }
                    modal.showPleaseWait();
                    $.ajax({
                        url: 'addNewVideo',
                        data: {
                            "id": $('#inputVideoId').val(),
                            "title": $('#inputTitle').val(),
                            "clean_title": $('#inputCleanTitle').val(),
                            "description": $('#inputDescription').val(),
                            "categories_id": $('#inputCategory').val(),
                            "public": isPublic,
                            "videoGroups": selectedVideoGroups,
                            "isAd": isAd,
                            "adElements": adElements
                        },
                        type: 'post',
                        success: function (response) {
                            if (response.status === "1") {
                                $('#videoFormModal').modal('hide');
                                $("#grid").bootgrid("reload");
                                swal("<?php echo __("Success"); ?>", "<?php echo __("Your video has been saved"); ?>", "success");
                            } else {
                                swal("<?php echo __("Sorry!"); ?>", "<?php echo __("Your video has NOT been saved!"); ?>", "error");
                            }
                            modal.hidePleaseWait();
                        }
                    });
                    return false;
                });

            });

        </script>
    </body>
</html>
