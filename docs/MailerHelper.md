# MailerHelper Yii2

Helper untuk mengirim email di Yii2 secara langsung (**synchronous**).  
Cocok untuk development atau kasus sederhana.

---

## Fitur

- Mengirim email HTML atau plain text
- Menggunakan template view
- Mendukung CC / BCC
- Lampiran file (attachment)

---

## 1. Install Yii2 Mailer

Pastikan Yii2 Mailer sudah terinstall:

```bash
composer require yiisoft/yii2-swiftmailer
```

---

## 2. Konfigurasi Mailer

Tambahkan konfigurasi mailer di `config/web.php`:

```php
'components' => [
    'mailer' => [
        'class' => 'yii\swiftmailer\Mailer',
        'useFileTransport' => false, // set false agar email benar-benar dikirim
        'transport' => [
            'class' => 'Swift_SmtpTransport',
            'host' => 'smtp.example.com',
            'username' => 'user@example.com',
            'password' => 'password',
            'port' => '587',
            'encryption' => 'tls',
        ],
    ],
],
```

---

## 3. Usage MailerHelper

### 3.1 Kirim Email HTML Langsung

```php
use app\helpers\MailerHelper;

MailerHelper::sendMail(
    'user@example.com',
    'Subject Email',
    '<p>Hello world</p>'
);
```

**Parameter:**

- `$to`: string|array, email penerima
- `$subject`: string, judul email
- `$body`: string, konten HTML
- `$from`: string|null, alamat pengirim (default dari `params['adminEmail']`)
- `$cc`: array, daftar email CC
- `$bcc`: array, daftar email BCC
- `$attachments`: array, daftar file path untuk attachment

### 3.2 Kirim Email dengan Template

```php
MailerHelper::sendTemplateMail(
    'mail/welcome',        // view file di folder mail
    ['name' => 'John'],    // parameter untuk view
    'user@example.com',     // penerima
    'Welcome Email'         // subject
);
```

### 3.3 Kirim Email Plain Text

```php
MailerHelper::sendTextMail(
    'user@example.com',
    'Subject',
    'Hello plain text'
);
```

### 3.4 Kirim Email dengan CC, BCC, dan Attachment

```php
MailerHelper::sendMail(
    'user@example.com',
    'Report',
    '<p>See attachment</p>',
    null,
    ['cc@example.com'],
    ['bcc@example.com'],
    ['/path/to/file.pdf']
);
```

---

## 4. Catatan

- `sendMail` dan `sendTemplateMail` bersifat **blocking**, cocok untuk development atau tugas kecil.
- Untuk production dan asynchronous, gunakan `MailerQueueHelper` dengan Yii2 Queue.
- Pastikan SMTP server, credentials, dan port sudah benar.
- Gunakan attachment hanya file yang ada di server.