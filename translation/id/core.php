<?php
/*
 * Translation configuration for the application.
 * Do not change or remove any values, this used by core
 * 
 * Version: 1.0.0
 * Version Date: 2025-05-05
 */

return [
    #General Rules
    'badRequest' => 'Bad Request.',
    'emptyParams' => 'At least one input must be provided except "id" to update data.',
    'dataNotFound' => 'Data not found.',
    'exceptionOccured' => 'An exception has occurred.',
    'unauthorizedAccess' => 'Unauthorized access.',
    'serverError' => 'Server error.',
    'lockVersionOutdated' => 'The data being updated is outdated. Please refresh the page and try again.',
    'unknownError' => 'An unknown error occurred.',

    #General Authentication Rules
    'loginRequired' => 'You must login to perform this action.',
    'logoutSuccess' => 'You have been logged out successfully.',
    'logoutFailed' => 'Failed to logout.',
    'loginSuccess' => 'You have been logged in successfully.',
    'loginFailed' => 'Failed to login.',

    #General Records Rules
    'createRecordSuccess' => 'Data telah disimpan dengan sukses.',
    'createRecordFailed' => 'Gagal menyimpan data.',
    'updateRecordSuccess' => 'Data telah diperbarui dengan sukses.',
    'updateRecordFailed' => 'Gagal memperbarui data.',
    'deleteRecordSuccess' => 'Data telah dihapus dengan sukses.',
    'deleteRecordFailed' => 'Gagal menghapus data.',
    'noRecordDeleted' => 'Gagal, Record sudah dihapus.',
    'noRecordUpdated' => 'Gagal, tidak ada record yang diperbarui.',

    #Field Validation Rules
    'required' => '{label} tidak boleh kosong.',
    'integer' => '{label} harus berupa bilangan bulat.',
    'array' => '{label} harus berupa array.',
    'number' => '{label} harus berupa angka.',
    'notExist' => '{label} dengan ID {value} tidak ada.',
    'validationFailed' => 'Validasi gagal.',
    'invalidField' => 'Field {label} tidak valid.',
    'fieldDataNotFound' => 'Data {label} tidak ditemukan.',
    'extraField' => 'Field tambahan ditemukan di {label}: {field}. Field yang diizinkan: {value}.',
    'extraFieldFound' => 'Field tambahan ditemukan di {label}.',
    'missingField' => 'Field wajib {field} tidak ditemukan.',
    'missingFieldFound' => 'Field wajib {field} tidak ditemukan di {label}.',
    'nullField' => '{label} field: {field} tidak boleh kosong atau null.',
    'allowedField' => '{field} hanya boleh berisi {value}.',
    'integerNoZero' => '{label} harus berupa bilangan bulat dan lebih besar dari 0.',
    'invalidStatusTransition' => 'Transisi perubahan status data tidak valid.',
    'valueNotInList' => '{label} harus berisi salah satu nilai berikut: {value}.',

    #General Pagination Rules
    'pageMustBeGreaterThanZero' => 'Halaman harus lebih besar dari 0.',
    
    #Status Update Rules
    'disallowedStatusUpdate' => 'Tidak dapat mengubah status karena data sudah {value}.',
    'cannotChangeStatus' => 'Tidak dapat mengubah status dari {value} ke {newValue}.',
    'deletedStatusChanged' => 'Anda tidak memiliki izin untuk mengubah status dari {value} ke status lainnya. Hak akses admin diperlukan.',
    
    #Superadmin Rights Rules
    'superadminOnly' => 'Anda tidak memiliki izin untuk melakukan tindakan ini.',
    'updatePermission' => 'Anda tidak memiliki izin untuk memperbarui {label} dari {tableName} karena data tersebut diacu oleh data lain.',

    #General Time Rules
    'day' => 'hari',
    'days' => 'hari',
    'minute' => 'menit',
    'minutes' => 'menit',
    'hour' => 'jam',
    'hours' => 'jam',
];