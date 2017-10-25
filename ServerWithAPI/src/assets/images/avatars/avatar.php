<?php
/**
 * - Copyright (c) Matt Nunn - All Rights Reserved
 * - Unauthorized copying of this file via any medium is strictly prohibited
 * - Written by Matt Nunn <MH.Nunn@gmail.com> 2016.
 */

/**
 * If there's no avatar set, get one.
 *
 * @return mixed
 */

if (isset($_GET['uid']) && is_numeric($_GET['uid'])) {
    $avatarFile = glob(__DIR__ . DIRECTORY_SEPARATOR . "avatar_" . intval($_GET['uid']) . ".*");
    if (!empty($avatarFile)) {
        header('Content-Type: ' . mime_content_type($avatarFile[0]));
        echo file_get_contents($avatarFile[0]);
    } else {
        echo file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'noavatar.jpg');
    }
} else {
    header('Content-Type: image/jpg');
    echo file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'noavatar.jpg');
}