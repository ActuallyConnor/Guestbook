<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\ImageOptimizer;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use CommentReviewNotification;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface as MailerTransportException;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface as HttpTransportException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class CommentMessageHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $entityManager;
    private SpamChecker $spamChecker;
    private CommentRepository $commentRepository;
    private MessageBusInterface $bus;
    private WorkflowInterface $workflow;
    private NotifierInterface $notifier;
    private ImageOptimizer $imageOptimizer;
    private string $photoDir;
    private ?LoggerInterface $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param SpamChecker            $spamChecker
     * @param CommentRepository      $commentRepository
     * @param MessageBusInterface    $bus
     * @param WorkflowInterface      $commentStateMachine
     * @param NotifierInterface      $notifier
     * @param ImageOptimizer         $imageOptimizer
     * @param string                 $photoDir
     * @param LoggerInterface|null   $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        SpamChecker $spamChecker,
        CommentRepository $commentRepository,
        MessageBusInterface $bus,
        WorkflowInterface $commentStateMachine,
        NotifierInterface $notifier,
        ImageOptimizer $imageOptimizer,
        string $photoDir,
        LoggerInterface $logger = null
    ) {
        $this->entityManager     = $entityManager;
        $this->spamChecker       = $spamChecker;
        $this->commentRepository = $commentRepository;
        $this->bus               = $bus;
        $this->workflow          = $commentStateMachine;
        $this->notifier          = $notifier;
        $this->imageOptimizer    = $imageOptimizer;
        $this->photoDir          = $photoDir;
        $this->logger            = $logger;
    }

    /**
     * @param CommentMessage $message
     *
     * @throws MailerTransportException|HttpTransportException
     */
    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->getId());
        if (!$comment) {
            return;
        }

        if ($this->workflow->can($comment, 'accept')) {
            $score      = $this->spamChecker->getSpamScore($comment, $message->getContext());
            $transition = 'accept';
            if (2 === $score) {
                $transition = 'reject_spam';
            } elseif (1 === $score) {
                $transition = 'might_be_spam';
            }
            $this->workflow->apply($comment, $transition);
            $this->entityManager->flush();

            $this->bus->dispatch($message);
        } elseif (
            $this->workflow->can($comment, 'publish')
            || $this->workflow->can($comment, 'publish_ham')
        ) {
            $this->notifier->send(new CommentReviewNotification($comment), $this->notifier->getAdminRecipients()[0]);
        } elseif ($this->workflow->can($comment, 'optimize')) {
            if ($comment->getPhotoFilename()) {
                $this->imageOptimizer->resize($this->photoDir . '/' . $comment->getPhotoFilename());
            }
            $this->workflow->apply($comment, 'optimize');
            $this->entityManager->flush();
        } elseif ($this->logger) {
            $this->logger->debug('Dropping comment message',
                [
                    'comment' => $comment->getId(),
                    'state'   => $comment->getState(),
                ]
            );
        }
    }
}
