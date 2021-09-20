<?php

declare(strict_types=1);

use App\Entity\Comment;
use Symfony\Component\Notifier\Bridge\Slack\Block\SlackDividerBlock;
use Symfony\Component\Notifier\Bridge\Slack\Block\SlackSectionBlock;
use Symfony\Component\Notifier\Bridge\Slack\SlackOptions;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Notification\ChatNotificationInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class CommentReviewNotification extends Notification implements EmailNotificationInterface, ChatNotificationInterface
{
    private Comment $comment;

    public function __construct(Comment $comment)
    {
        $this->comment = $comment;

        parent::__construct('New comment posted');
    }

    public function asEmailMessage(
        EmailRecipientInterface $recipient,
        string $transport = null
    ) : ?EmailMessage {
        $message = EmailMessage::fromNotification($this, $recipient);

        $message->getMessage()
                ->htmlTemplate('emails/comment_notification.html.twig')
                ->context(['comment' => $this->comment]);
        $message->transport($transport);

        return $message;
    }

    public function getChannels(RecipientInterface $recipient) : array
    {
        if (preg_match('{\b(great|awesome)\b}i', $this->comment->getText())) {
            return ['email', 'chat/slack'];
        }

        $this->importance(Notification::IMPORTANCE_LOW);

        return ['email'];
    }

    public function asChatMessage(
        RecipientInterface $recipient,
        string $transport = null
    ) : ?ChatMessage {
        if ('slack' !== $transport) {
            return null;
        }

        $message = ChatMessage::fromNotification($this);
        $message->subject($this->getSubject());
        $message->options(
            (new SlackOptions())
                ->iconEmoji('tada')
                ->iconUrl('https://guestbook.test')
                ->username('Guestbook')
                ->block((new SlackSectionBlock())->text($this->getSubject()))
                ->block((new SlackDividerBlock()))
                ->block(
                    (new SlackSectionBlock())->text(
                        sprintf(
                            '%s (%s) says: %s',
                            $this->comment->getAuthor(),
                            $this->comment->getEmail(),
                            $this->comment->getText()
                        )
                    )
                )
        );

        return $message;
    }
}
