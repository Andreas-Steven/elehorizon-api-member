<?php
namespace app\helpers;

use Yii;
use yii\base\Component;
use yii\queue\JobInterface;

/**
 * MailerQueueHelper
 * 
 * Helper untuk mengirim email via queue (asynchronous), cocok untuk production.
 * Email dikirim di background tanpa blocking request.
 *
 * Fitur:
 * - HTML atau plain text
 * - Template view
 * - CC / BCC
 * - Attachment
 * - Queue background (Yii2 Queue)
 *
 * Usage:
 * ```php
 * use app\helpers\MailerQueueHelper;
 *
 * // Kirim email HTML via queue
 * MailerQueueHelper::sendMailAsync('user@example.com', 'Subject', '<p>Hello world</p>');
 *
 * // Kirim email template via queue
 * MailerQueueHelper::sendTemplateMailAsync(
 *     'mail/welcome',
 *     ['name' => 'John'],
 *     'user@example.com',
 *     'Welcome!'
 * );
 *
 * // Kirim email dengan attachment & CC/BCC via queue
 * MailerQueueHelper::sendMailAsync(
 *     'user@example.com',
 *     'Report',
 *     '<p>See attachment</p>',
 *     null,
 *     ['cc@example.com'],
 *     ['bcc@example.com'],
 *     ['/path/to/file.pdf']
 * );
 * ```
 */
class MailerQueueHelper extends Component
{
    /**
     * Push email HTML biasa ke queue untuk dikirim secara asynchronous.
     *
     * @param string|array $to Penerima email
     * @param string $subject Subject email
     * @param string $body Body email HTML
     * @param string|null $from Email pengirim, default Yii::$app->params['adminEmail']
     * @param array $cc CC email
     * @param array $bcc BCC email
     * @param array $attachments Daftar path file attachment
     */
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

    /**
     * Push email berbasis template view ke queue untuk dikirim secara asynchronous.
     *
     * @param string $view Path view template, misal 'mail/welcome'
     * @param array $params Parameter yang dikirim ke view template
     * @param string|array $to Penerima email
     * @param string $subject Subject email
     * @param string|null $from Email pengirim
     * @param array $cc CC email
     * @param array $bcc BCC email
     * @param array $attachments Daftar path file attachment
     */
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

/**
 * SendMailJob
 *
 * Job untuk worker queue Yii2, bertugas mengeksekusi pengiriman email di background.
 */
class SendMailJob extends \yii\base\BaseObject implements JobInterface
{
    /** @var string|array Penerima email */
    public $to;
    
    /** @var string Subject email */
    public $subject;
    
    /** @var string Body email HTML */
    public $body;
    
    /** @var string|null Pengirim email */
    public $from;
    
    /** @var array Daftar CC email */
    public $cc = [];
    
    /** @var array Daftar BCC email */
    public $bcc = [];
    
    /** @var array Daftar path file attachment */
    public $attachments = [];
    
    /** @var string|null View template jika pakai template */
    public $view;
    
    /** @var array Parameter untuk view template */
    public $params = [];

    /**
     * Method yang dijalankan worker queue.
     * 
     * Jika property `view` di-set, email akan dikirim berdasarkan template view,
     * jika tidak, body HTML langsung dikirim.
     *
     * Menangani:
     * - CC / BCC
     * - Attachment
     * - Pengirim default
     *
     * @param \yii\queue\Queue $queue
     */
    public function execute($queue)
    {
        $from = $this->from ?? Yii::$app->params['adminEmail'] ?? 'no-reply@example.com';
        $mailer = Yii::$app->mailer;

        if ($this->view) {
            // Kirim email pakai template view
            $mail = $mailer->compose($this->view, $this->params)
                ->setFrom($from)
                ->setTo($this->to)
                ->setSubject($this->subject);
        } else {
            // Kirim email HTML langsung
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
