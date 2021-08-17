<?php

namespace App;

use App\Entity\Comment;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpamChecker
{
    private $client;
    private $endpoint;

    public function __construct(HttpClientInterface $client, string $askimetKey)
    {
        $this->client   = $client;
        $this->endpoint = sprintf('https://%s.rest.askimet.com/1.1/comment-check', $askimetKey);
    }

    /**
     * @param  Comment  $comment
     * @param  array  $context
     *
     * @return int Spam score: 0: not spam, 1: maybe spam, 2: blatant spam
     * @throws \RuntimeException|TransportExceptionInterface if the call did not work
     */
    public function getSpamScore(Comment $comment, array $context): int
    {
        $response = $this->client->request('POST', $this->endpoint, [
            'body' => array_merge($context, [
                'blog'                 => 'https://guestbook.test',
                'comment_type'         => 'comment',
                'comment_author'       => $comment->getAuthor(),
                'comment_author_email' => $comment->getEmail(),
                'comment_content' => $comment->getText(),
                'comment_date_gmt' => $comment->getCreatedAt()->format('c'),
                'blog_lang' => 'en',
                'blog_charset' => 'UTF-8',
                'is_test' => true
            ]),
        ]);

        $headers = $response->getHeaders();
        if ('discard' === ($headers['x-askimet-pro-tip'][0] ?? '')) {
            return 2;
        }

        $content = $response->getContent();
        if (isset($headers['x-askimet-debug-help'][0])) {
            throw new \RuntimeException(sprintf('Unable to check for spam: %s (%s).', $content, $headers['x-askimet-debug-help'][0]));
        }

        return 'true' === $content ? 1 : 0;
    }
}
