# Dokumentasi Mailer Queue Yii2

Panduan lengkap untuk mengirim email **asynchronous** menggunakan Yii2 Queue, cocok untuk production.  
Mulai dari instalasi, konfigurasi, helper, usage, hingga tips debugging.

---

## 1. Install Yii2 Queue

Install package Yii2 Queue via Composer:

```bash
composer require yiisoft/yii2-queue
```

Jika ingin menggunakan Redis sebagai backend queue:

```bash
composer require yiisoft/yii2-queue-redis
```

Jika menggunakan database sebagai backend queue (DB Queue):

```bash
composer require yiisoft/yii2-queue-db
```

---

## 2. Jika ingin menggunakan Redis sebagai backend queue

Tambahkan konfigurasi komponen queue di `config/web.php` atau `config/console.php`:

```php
'components' => [
    'queue' => [
        'class' => \yii\queue\redis\Queue::class,
        'redis' => 'redis',          // koneksi Redis
        'channel' => 'default',      // nama channel queue
        'mutex' => \yii\mutex\RedisMutex::class,
    ],
],
```

Pastikan service Redis sudah berjalan dan `Yii::$app->redis` sudah dikonfigurasi.

---

## 3. Jika menggunakan database sebagai backend queue (DB Queue)

Buat tabel queue dengan migration:

```bash
php yii migrate --migrationPath=@yii/queue/migrations/
```

Tambahkan konfigurasi DB Queue:

```php
'components' => [
    'queue' => [
        'class' => \yii\queue\db\Queue::class,
        'db' => 'db',
        'tableName' => '{{%queue}}',
        'channel' => 'default',
        'mutex' => \yii\mutex\MysqlMutex::class,
    ],
],
```

---

## 4. Jalankan Worker Queue

### Manual

```bash
php yii queue/run
```

### Background (Linux)

```bash
nohup php yii queue/run > /dev/null 2>&1 &
```

### Menggunakan Supervisor (Production)

Buat file konfigurasi Supervisor `/etc/supervisor/conf.d/yii2-queue.conf`:

```ini
[program:yii2-queue]
command=php /path/to/yii queue/run
autostart=true
autorestart=true
stderr_logfile=/var/log/yii2-queue.err.log
stdout_logfile=/var/log/yii2-queue.out.log
user=www-data
```

Reload dan start Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start yii2-queue
```

---

## 5. Helper `MailerQueueHelper`

Helper untuk **push job email ke queue**.  

### Kirim email HTML biasa

```php
use app\helpers\MailerQueueHelper;

MailerQueueHelper::sendMailAsync(
    'user@example.com',
    'Halo!',
    '<p>Ini email test via queue.</p>'
);
```

### Kirim email menggunakan template view

```php
MailerQueueHelper::sendTemplateMailAsync(
    'mail/welcome',
    ['name' => 'John'],
    'user@example.com',
    'Selamat Datang!'
);
```

### Kirim email dengan CC/BCC dan attachment

```php
MailerQueueHelper::sendMailAsync(
    'user@example.com',
    'Report',
    '<p>Lampiran laporan</p>',
    null,
    ['cc@example.com'],
    ['bcc@example.com'],
    ['/var/www/files/report.pdf']
);
```

---

### Fungsi utama Helper

| Fungsi | Deskripsi |
|--------|-----------|
| `sendMailAsync($to, $subject, $body, $from, $cc, $bcc, $attachments)` | Mengirim email HTML biasa via queue. |
| `sendTemplateMailAsync($view, $params, $to, $subject, $from, $cc, $bcc, $attachments)` | Mengirim email berbasis template view via queue. |

---

## 6. Flow Pengiriman Email

1. Helper `MailerQueueHelper` push job ke queue.  
2. Worker queue (`yii queue/run`) mengeksekusi job.  
3. `SendMailJob::execute()` dijalankan → email dikirim.  
4. Job selesai → otomatis dihapus dari queue.

---

## 7. Tips Production

- Gunakan **RedisQueue** untuk performa lebih baik jika volume job tinggi.  
- Jalankan worker queue sebagai **service** (Supervisor / systemd) agar selalu aktif.  
- Tambah jumlah worker untuk throughput tinggi:

```bash
php yii queue/run --queue=mail --workers=3
```

- Pastikan `Yii::$app->mailer` telah terkonfigurasi SMTP atau mailer yang valid.

---

## 8. Debugging

- Cek tabel `queue` (DB Queue) atau Redis key (Redis Queue) untuk melihat job yang belum dijalankan.  
- Gunakan log Yii2 (`runtime/logs/app.log`) untuk men-trace error pengiriman email.  
- Jika email tidak terkirim:
  - Periksa konfigurasi SMTP/mail server.
  - Pastikan worker queue sedang berjalan.

---

## 9. Contoh File Helper `MailerQueueHelper.php`

```php
<?php
namespace app\helpers;

use Yii;
use yii\base\Component;
use yii\queue\JobInterface;

class MailerQueueHelper extends Component
{
    public static function sendMailAsync($to, string $subject, string $body, ?string $from = null, array $cc = [], array $bcc = [], array $attachments = [])
    {
        Yii::$app->queue->push(new SendMailJob([
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'from' => $from,
            'cc' => $cc,
            'bcc' => $bcc,
            'attachments' => $attachments,
        ]));
    }

    public static function sendTemplateMailAsync(string $view, array $params, $to, string $subject, ?string $from = null, array $cc = [], array $bcc = [], array $attachments = [])
    {
        Yii::$app->queue->push(new SendMailJob([
            'view' => $view,
            'params' => $params,
            'to' => $to,
            'subject' => $subject,
            'from' => $from,
            'cc' => $cc,
            'bcc' => $bcc,
            'attachments' => $attachments,
        ]));
    }
}

class SendMailJob extends \yii\base\BaseObject implements JobInterface
{
    public $to;
    public $subject;
    public $body;
    public $from;
    public $cc = [];
    public $bcc = [];
    public $attachments = [];
    public $view;
    public $params = [];

    public function execute($queue)
    {
        $from = $this->from ?? Yii::$app->params['adminEmail'] ?? 'no-reply@example.com';
        $mailer = Yii::$app->mailer;

        if ($this->view) {
            $mail = $mailer->compose($this->view, $this->params)
                ->setFrom($from)
                ->setTo($this->to)
                ->setSubject($this->subject);
        } else {
            $mail = $mailer->compose()
                ->setFrom($from)
                ->setTo($this->to)
                ->setSubject($this->subject)
                ->setHtmlBody($this->body);
        }

        if ($this->cc) $mail->setCc($this->cc);
        if ($this->bcc) $mail->setBcc($this->bcc);

        if ($this->attachments) {
            foreach ($this->attachments as $file) {
                if (file_exists($file)) $mail->attach($file);
            }
        }

        $mail->send();
    }
}
```

---

## 10. Catatan

- Gunakan ini untuk **production** agar request pengguna tidak terblokir saat mengirim email.  
- Pastikan queue worker selalu berjalan agar email diproses otomatis.