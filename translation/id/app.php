<?php

$core_translation = require __DIR__ . '/core.php';

return array_merge($core_translation, [
    'responseLanguage' => 'Bahasa Indonesia',

    'id' => 'Indonesia',
    'en' => 'English',
    'ok' => 'OK',
    'success' => 'Success',

    /**
     * --------------------------------------------------------------------------
     * Add your custom translation below
     * --------------------------------------------------------------------------
     */
    
    'serviceOrderFailed' => 'Gagal memproses data Service Order.',
    'cartFailed' => 'Gagal memproses data Keranjang.',

    'invalidValue' => '{label} adalah nilai yang tidak valid',
    'selectServiceType' => 'Silakan pilih jenis layanan',
    'selectInstallationPackage' => 'Silakan pilih Paket Instalasi',
    'selectInstallationService' => 'Silakan pilih Layanan Instalasi',
    'selectCleaningType' => 'Silakan pilih Jenis Pembersihan',
]);