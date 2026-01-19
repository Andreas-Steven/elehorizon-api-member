<?php
$vendorPath = __DIR__ . '/../vendor/rahmatsyaparudin/yii2-api-skeleton/';
$rootPath = __DIR__ . '/../';

function copyDir($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst, 0755, true);
    $skipFiles = ['composer.json', 'README.md', '.env'];

    while(false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            $srcPath = "$src/$file";
            $dstPath = "$dst/$file";

            // Kecualikan composer.json
            if (in_array($file, $skipFiles)) {
                continue;
            }

            if (is_dir($srcPath)) {
                copyDir($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }

    closedir($dir);
}

/**
 * Merge composer dependencies from skeleton into root composer.json
 */
function mergeComposerDependencies($rootComposer, $skeletonComposer) {
    if (!file_exists($skeletonComposer) || !file_exists($rootComposer)) {
        echo "\e[33mWarning: composer.json not found for merging.\e[0m\n";
        return;
    }

    $rootData     = json_decode(file_get_contents($rootComposer), true);
    $skeletonData = json_decode(file_get_contents($skeletonComposer), true);

    foreach (['require'] as $section) {
        if (isset($skeletonData[$section])) {
            foreach ($skeletonData[$section] as $pkg => $ver) {
                if (!isset($rootData[$section][$pkg])) {
                    $rootData[$section][$pkg] = $ver;
                    echo "\e[36mAdded $pkg ($ver) to $section\e[0m\n";
                }
            }
        }
    }

    file_put_contents(
        $rootComposer,
        json_encode($rootData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
    );
}

copyDir($vendorPath, $rootPath);
mergeComposerDependencies($rootPath . 'composer.json', $vendorPath . 'composer.json');

echo "\n\e[32mSkeleton installed/updated successfully.\e[0m\n";
echo "\e[36mNext step:\e[0m Run \e[33mcomposer update\e[0m or \e[33mcomposer update --ignore-platform-reqs\e[0m to install new dependencies.\n";