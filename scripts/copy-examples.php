<?php
/**
 * Copy example files to target location and remove the `.example` extension
 */

$filesToCopy = [
    // format: "source relative to skeleton" => "target relative to root"
    ".env.example" => ".env",
    "php.ini.example" => "php.ini",
    "yii.example" => "yii",
    "README.md.example" => "README.md",
    "translation/id/app.php.example" => "translation/id/app.php",
    "translation/en/app.php.example" => "translation/en/app.php",
    "helpers/Constants.php.example" => "helpers/Constants.php",
    "exceptions/ErrorMessage.php.example" => "exceptions/ErrorMessage.php",
    "web/index.php.example" => "web/index.php",
    "config/setup/console.php" => "config/console.php",
    "config/setup/web.php" => "config/web.php",
    "config/setup/db.php" => "config/db.php",
    "config/setup/modules.php" => "config/modules.php",
    "config/setup/mongodb.php" => "config/mongodb.php",
    "config/setup/params.php" => "config/params.php",
    "config/setup/params_app.php" => "config/params_app.php",
    "config/setup/url_manager.php" => "config/url_manager.php",
    "config/setup/db_manager.php" => "config/db_manager.php",
    "config/setup/mailer.php" => "config/mailer.php",
];

$skeletonPath = __DIR__ . '/../vendor/rahmatsyaparudin/yii2-api-skeleton/';
$rootPath = __DIR__ . '/../';

$flagFile = $rootPath . '.skeleton_examples_copied';
if (file_exists($flagFile)) {
    echo "\e[33mExample files were already copied. Skipping...\e[0m\n";
    exit(0);
}

foreach ($filesToCopy as $src => $dst) {
    $fullSrc = $skeletonPath . $src;
    $fullDst = $rootPath . $dst;

    // pastikan folder tujuan ada
    $dir = dirname($fullDst);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (file_exists($fullSrc)) {
        copy($fullSrc, $fullDst);
        echo "Copied $src → $dst\n";
    } else {
        echo "Warning: $src not found in skeleton\n";
    }
}

file_put_contents($flagFile, date(DATE_ATOM));
echo "\e[32mExample files copied successfully!\e[0m\n";
