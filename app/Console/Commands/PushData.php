<?php
/**
 *
 * PHP version >= 7.0
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Ddeboer\Imap\Server;
use Ddeboer\Imap\SearchExpression;
use Ddeboer\Imap\Search\Flag\Unseen;
use Ddeboer\Imap\Message;
use Ddeboer\Imap\Message\Attachment;
use Carbon\Carbon;
use Storage;

/**
 * Class PushData
 *
 * @category Console_Command
 * @package  App\Console\Commands
 */
class PushData extends Command
{
    const MAIL_FOLDER = 'INBOX';

    private $connection;
    private $search;
    private $messages;
    private $remoteStorageDisk;

    private $hostname;
    private $email;
    private $password;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "push:data {--disk= : Specify the remote storage disk}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Push Netsuite Data";

    public function __construct()
    {
        parent::__construct();
        $this->hostname = config('mail.clients.automation.hostname');
        $this->email = config('mail.clients.automation.email');
        $this->password = config('mail.clients.automation.password');
        $this->imapServer = new Server($this->hostname);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() : void
    {
        if($this->remoteStorageDisk = $this->option('disk')) {
            $this->init();
        } else {
            $this->info('Remote storage disk not specified');
        }
    }

    private function init() : void
    {
        $this->authenticate();
        $this->setParameters();
        $this->getMessages();
        $this->handleMessages();
    }

    private function authenticate() : void
    {
        $this->connection = $this->imapServer->authenticate($this->email, $this->password);
    }

    private function setParameters() : void
    {
        $this->search = new SearchExpression;
        $this->search->addCondition(new Unseen);
    }

    private function getMessages() : void
    {
        $mailbox = $this->connection->getMailbox(self::MAIL_FOLDER);
        $this->messages = $mailbox->getMessages($this->search);
    }

    private function handleMessages() : void
    {
        foreach($this->messages as $message) {
            $this->parseMessage($message);
        }
    }

    private function parseMessage(Message $message) : void
    {
        $attachments = $message->getAttachments();
        if($this->saveAttachment($attachments[0])) {
            $message->markAsSeen();
        }
    }

    private function saveAttachment(?Attachment $attachment) : bool
    {
        if($attachment) {
            return Storage::disk($this->remoteStorageDisk)->put($this->generateFileName($attachment), $attachment->getDecodedContent());
        }
        return false;
    }

    private function generateFileName(Attachment $attachment) : string
    {
        $fileInfo = pathinfo($attachment->getFilename());
        return "{$fileInfo['basename']}";
    }

}
