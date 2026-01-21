<?php

$core_translation = require __DIR__ . '/core.php';

return array_merge($core_translation, [
    'responseLanguage' => 'Language English',

    'id' => 'Indonesia',
    'en' => 'English',
    'ok' => 'OK',
    'success' => 'Success',

    /**
     * --------------------------------------------------------------------------
     * Add your custom translation below
     * --------------------------------------------------------------------------
     */

    'serviceOrderFailed' => 'Failed to process Service Order data.',
    'invalidValue' => '{label} is invalid value',
    'selectServiceType' => 'Please select service type',
    'selectInstallationPackage' => 'Please select Installation Package',
    'selectInstallationService' => 'Please select Installation Service',
    'selectCleaningType' => 'Please select Cleaning Type',
]);