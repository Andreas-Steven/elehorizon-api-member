<?php
namespace app\helpers;

use Yii;
use yii\base\Component;

/**
 * MailerHelper
 * 
 * Helper untuk mengirim email di Yii2 secara langsung (synchronous).
 * Cocok untuk development atau kasus sederhana.
 *
 * Fitur:
 * - HTML atau plain text
 * - Template view
 * - CC / BCC
 * - Attachment
 */
class MailerHelper extends Component
{
    /**
     * Mengirim email HTML langsung ke penerima.
     * Mendukung CC, BCC, dan attachment.
     *
     * Usage:
     * ```php
     * MailerHelper::sendMail(
     *     'user@example.com',
     *     'Subject',
     *     '<p>Hello world</p>'
     * );
     * ```
     *
     * @param string|array $to Email penerima
     * @param string $subject Judul email
     * @param string $body Konten HTML
     * @param string|null $from Alamat pengirim (default dari params['adminEmail'])
     * @param array $cc Daftar email CC
     * @param array $bcc Daftar email BCC
     * @param array $attachments Array path file untuk lampiran
     * @return bool True jika email berhasil dikirim
     */
    public static function sendMail($to, string $subject, string $body, ?string $from = null, array $cc = [], array $bcc = [], array $attachments = []): bool
    {
        $from = $from ?? Yii::$app->params['adminEmail'] ?? 'no-reply@example.com';

        $mail = Yii::$app->mailer->compose()
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($subject)
            ->setHtmlBody($body);

        if ($cc) $mail->setCc($cc);
        if ($bcc) $mail->setBcc($bcc);
        if ($attachments) {
            foreach ($attachments as $file) {
                if (file_exists($file)) $mail->attach($file);
            }
        }

        return $mail->send();
    }

    /**
     * Mengirim email menggunakan template view.
     * Data dari $params akan di-render di view.
     *
     * Usage:
     * ```php
     * MailerHelper::sendTemplateMail(
     *     'mail/welcome',
     *     ['name' => 'John'],
     *     'user@example.com',
     *     'Welcome!'
     * );
     * ```
     *
     * @param string $view Nama view template
     * @param array $params Parameter untuk view
     * @param string|array $to Email penerima
     * @param string $subject Judul email
     * @param string|null $from Alamat pengirim
     * @param array $cc Daftar email CC
     * @param array $bcc Daftar email BCC
     * @param array $attachments File lampiran
     * @return bool True jika email berhasil dikirim
     */
    public static function sendTemplateMail(string $view, array $params, $to, string $subject, ?string $from = null, array $cc = [], array $bcc = [], array $attachments = []): bool
    {
        $from = $from ?? Yii::$app->params['adminEmail'] ?? 'no-reply@example.com';

        $mail = Yii::$app->mailer->compose($view, $params)
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($subject);

        if ($cc) $mail->setCc($cc);
        if ($bcc) $mail->setBcc($bcc);
        if ($attachments) {
            foreach ($attachments as $file) {
                if (file_exists($file)) $mail->attach($file);
            }
        }

        return $mail->send();
    }

    /**
     * Mengirim email **plain text**.
     *
     * Usage:
     * ```php
     * MailerHelper::sendTextMail(
     *     'user@example.com',
     *     'Subject',
     *     'Hello plain text'
     * );
     * ```
     *
     * @param string|array $to Email penerima
     * @param string $subject Judul email
     * @param string $textBody Konten teks
     * @param string|null $from Alamat pengirim
     * @return bool True jika email berhasil dikirim
     */
    public static function sendTextMail($to, string $subject, string $textBody, ?string $from = null): bool
    {
        $from = $from ?? Yii::$app->params['adminEmail'] ?? 'no-reply@example.com';

        return Yii::$app->mailer->compose()
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($subject)
            ->setTextBody($textBody)
            ->send();
    }
}
