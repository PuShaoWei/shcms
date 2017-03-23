<?php

namespace App\Crawl;

use App\Article;
use App\Comment;
use App\Service\ArticleService;
use App\Service\SegmentfaultService;
use App\Service\UserService;
use App\Tag;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use Psr\Http\Message\ResponseInterface;

class SegmentfaultQuestionPage extends Crawl
{
    use EasyCrawl;

    /**
     * 从DOM取出需要的数据
     * @param ResponseInterface $response
     * @return mixed
     * @internal param $questionPageUrl
     */
    public function parse(ResponseInterface $response)
    {
        $dom = new Crawler($response->getBody()->getContents());
        $question = $this->parseQuestion($dom);
        $question['answers'] = $this->parseAnswers($dom);
        return $question;
    }

    /**
     * 取出问题数据
     * @param Crawler $dom
     * @return mixed
     */
    protected function parseQuestion($dom)
    {
        $question['slug'] = 'segmentfault-' . $dom->filter('#questionTitle')->attr('data-id');
        $question['title'] = utf8_to_unicode_str($dom->filter('#questionTitle>a')->text());
        $question['body'] = utf8_to_unicode_str(trim($dom->filter('.question')->html()));
        $question['tags'] = $dom->filter('.taglist--inline li a')->each(function (Crawler $node, $i) {
            return $node->attr('data-original-title');
        });

        $userName = $dom->filter('.question__author a strong')->first()->text();
        $userUrl = $dom->filter('.question__author a')->first()->attr('href');

        $u = explode('/', $userUrl);
        $user_id = end($u);;
        $userEmail = $user_id . '@segmentfault.com';

        $question['user'] = [
            'name' => utf8_to_unicode_str($userName),
            'email' => $userEmail,
            'password' => Str::random(),
        ];

        return $question;
    }

    /**
     * 取出回答数据
     * @param Crawler $dom
     * @return mixed
     */
    protected function parseAnswers($dom)
    {
        return $dom->filter('.widget-answers__item[id]')->each(function (Crawler $node, $i) {
            $userName = $node->filter('.answer__info--author-name')->first()->text();
            $userUrl = $node->filter('.answer__info--author-name')->first()->attr('href');

            $u = explode('/', $userUrl);
            $user_id = end($u);
            $userEmail = $user_id . '@segmentfault.com';

            return [
                'slug' => 'segmentfault-' . $node->attr('id'),
                'time' => $node->filter('.list-inline>li')->first()->filter('a')->text(),
                'body' => utf8_to_unicode_str(trim($node->filter('.answer')->first()->html())),
                'is_awesome' => $node->filter('.accepted-check-icon')->count(),
                'user' => [
                    'name' => utf8_to_unicode_str($userName),
                    'email' => $userEmail,
                    'password' => Str::random(),
                    'rank' => $node->filter('.answer__info--author-rank')->first()->text(),
                ],
            ];
        });
    }
    public function store(array $question)
    {
        $article = $this->storeQuestion($question);
        foreach ($question['answers'] as $answer) {
            $comment = $this->storeComment($article, $answer);
            // 是否新添加答案
            if ($comment->wasRecentlyCreated) {
                \Log::info('add answer: ' . $comment->slug);
            }
        }
    }

    /**
     * 储存问题
     * @param $question
     * @return Article
     */
    protected function storeQuestion($question)
    {
        $user = UserService::firstOrCreate(['email' => $question['user']['email']], $question['user']);
        if ($user->wasRecentlyCreated) {
            SegmentfaultService::crawlAvatar($user);
            \Log::info('add question user: ' . $user->email);
        }
        $question['user_id'] = $user->id;
        $question['body'] = SegmentfaultService::filterBody($question['body']);

        /** @var Article $article */
        $article = Article::firstOrCreate(Arr::only($question, ['slug']), $question);
        if ($article->wasRecentlyCreated) {
            \Log::info('add question: ' . $article->slug);
        }

        foreach ($question['tags'] as $tag_str) {
            if (!$article->tags()->whereTitle($tag_str)->exists()) {
                $tag = Tag::firstOrCreate(['title' => $tag_str], ['title' => $tag_str, 'slug' => ArticleService::filterTagSlug($tag_str)]);
                $article->tags()->attach($tag);
            }
        }

        return $article;
    }

    /**
     * 储存回答
     * @param $article
     * @param $answer
     * @return Comment
     */
    protected function storeComment($article, $answer)
    {
        $answerUser = UserService::firstOrCreate(Arr::only($answer['user'], ['email']), $answer['user']);
        if ($answerUser->wasRecentlyCreated) {
            SegmentfaultService::crawlAvatar($answerUser);
            \Log::info('add answer user: ' . $answerUser->email);
        }

        $answer['user_id'] = $answerUser->id;
        $answer['article_id'] = $article->id;
        $answer['body'] = SegmentfaultService::filterBody($answer['body']);
        /** @var Comment $comment */
        $comment = Comment::firstOrCreate(Arr::only($answer, ['slug']), $answer);

        // 判断最佳答案
        if ($answer['is_awesome'] != $comment->is_awesome) {
            $comment->is_awesome = $answer['is_awesome'];
            $comment->save();
            \Log::info('answer change awesome: ' . $comment->slug);
        }
        return $comment;
    }


}
