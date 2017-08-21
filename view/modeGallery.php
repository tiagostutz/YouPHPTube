<?php
if (!file_exists('../videos/configuration.php')) {
    if (!file_exists('../install/index.php')) {
        die("No Configuration and no Installation");
    }
    header("Location: install/index.php");
}

require_once '../videos/configuration.php';

require_once $global['systemRootPath'] . 'objects/user.php';
require_once $global['systemRootPath'] . 'objects/functions.php';

if (!empty($_GET['type'])) {
    if ($_GET['type'] == 'audio') {
        $_SESSION['type'] = 'audio';
    } else if ($_GET['type'] == 'video') {
        $_SESSION['type'] = 'video';
    } else {
        $_SESSION['type'] = "";
        unset($_SESSION['type']);
    }
}

require_once $global['systemRootPath'] . 'objects/video.php';

if (empty($_GET['page'])) {
    $_GET['page'] = 1;
} else {
    $_GET['page'] = intval($_GET['page']);
}
$_POST['rowCount'] = 24;
$_POST['current'] = $_GET['page'];
$_POST['sort']['created'] = 'desc';
$videos = Video::getAllVideos("viewableNotAd");
foreach ($videos as $key => $value) {
    $name = empty($value['name']) ? $value['user'] : $value['name'];
    $videos[$key]['creator'] = '<div class="pull-left"><img src="' . User::getPhoto($value['users_id']) . '" alt="" class="img img-responsive img-circle" style="max-width: 20px;"/></div><div class="commentDetails" style="margin-left:25px;"><div class="commenterName"><strong>' . $name . '</strong> <small>' . humanTiming(strtotime($value['videoCreation'])) . '</small></div></div>';
}
$total = Video::getTotalVideos("viewableNotAd");
$totalPages = ceil($total / $_POST['rowCount']);
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language']; ?>">
    <head>
        <title><?php echo __('Gallery'); ?> - <?php echo $config->getWebSiteTitle(); ?></title>
        <meta name="generator" content="YouPHPTube - A Free Youtube Clone Script" />
        <?php
        include $global['systemRootPath'] . 'view/include/head.php';
        ?>
    </head>

    <body>
        <?php
        include 'include/navbar.php';
        ?>
        <div class="row text-center" style="padding: 10px;">
            <?php
            echo $config->getAdsense();
            ?>
        </div>        
        <div class="container-fluid gallery" itemscope itemtype="http://schema.org/VideoObject">
            <div class="col-xs-12 col-sm-1 col-md-1 col-lg-1"></div>
            <div class="col-xs-12 col-sm-10 col-md-10 col-lg-10 list-group-item">
                <?php
                if (!empty($videos)) {
                    ?>
                    <div class="row">
                        <?php
                        foreach ($videos as $value) {
                            $img_portrait = ($value['rotation'] === "90" || $value['rotation'] === "270") ? "img-portrait" : "";
                            ?>
                            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6 galleryVideo thumbsImage">
                                <a href="<?php echo $global['webSiteRootURL']; ?>video/<?php echo $value['clean_title']; ?>" title="<?php echo $value['title']; ?>" >
                                    <?php
                                    $imgGif = "";
                                    if (file_exists("{$global['systemRootPath']}videos/{$value['filename']}.gif")) {
                                        $imgGif = "{$global['webSiteRootURL']}videos/{$value['filename']}.gif";
                                    }
                                    if ($value['type'] !== "audio") {
                                        $poster = "{$global['webSiteRootURL']}videos/{$value['filename']}.jpg";
                                    } else {
                                        $poster = "{$global['webSiteRootURL']}view/img/audio_wave.jpg";
                                    }
                                    ?>    
                                    <img src="<?php echo $poster; ?>" alt="<?php echo $value['title']; ?>" class="thumbsJPG img img-responsive <?php echo $img_portrait; ?>  rotate<?php echo $value['rotation']; ?>" />
                                    <?php
                                    if (!empty($imgGif)) {
                                        ?>
                                        <img src="<?php echo $imgGif; ?>" style="position: absolute; top: 0; display: none;" alt="<?php echo $value['title']; ?>" id="thumbsGIF<?php echo $value['id']; ?>" class="thumbsGIF img-responsive <?php echo $img_portrait; ?>  rotate<?php echo $value['rotation']; ?>" height="130px" />
                                    <?php } ?>                                        
                                    <span class="duration"><?php echo Video::getCleanDuration($value['duration']); ?></span>
                                </a>
                                <a href="<?php echo $global['webSiteRootURL']; ?>video/<?php echo $value['clean_title']; ?>" title="<?php echo $value['title']; ?>">
                                    <h2><?php echo $value['title']; ?></h2>
                                </a>
                                <span class="watch-view-count col-lg-6" itemprop="interactionCount"><?php echo number_format($value['views_count'], 0); ?> <?php echo __("Views"); ?></span>
                                <?php
                                $value['tags'] = Video::getTags($value['id']);
                                foreach ($value['tags'] as $value2) {
                                    if ($value2->label === __("Group")) {
                                        ?>
                                        <span class="label label-<?php echo $value2->type; ?> col-lg-6 group"><?php echo $value2->text; ?></span>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                            <?php
                        }
                        ?> 

                    </div>
                    <div class="row">

                        <ul class="pages">
                        </ul>
                        <script>
                            $(document).ready(function () {
                                // Total Itens <?php echo $total; ?>

                                $('.pages').bootpag({
                                    total: <?php echo $totalPages; ?>,
                                    page: <?php echo $_GET['page']; ?>,
                                    maxVisible: 10
                                }).on('page', function (event, num) {
                                    window.location.replace("<?php echo $global['webSiteRootURL']; ?>page/" + num);
                                });
                            });
                        </script>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="alert alert-warning">
                        <span class="glyphicon glyphicon-facetime-video"></span> <strong><?php echo __("Warning"); ?>!</strong> <?php echo __("We have not found any videos or audios to show"); ?>.
                    </div>
                <?php } ?> 
            </div>

            <div class="col-xs-12 col-sm-1 col-md-1 col-lg-1"></div>


        </div>
        <?php
        include 'include/footer.php';
        ?>


    </body>
</html>
