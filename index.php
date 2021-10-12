<?php

$gitApi = new GitAPi();
$followings = $gitApi->getOnlyMyselfFollowings();
var_dump($followings);

class GitAPi
{
    // settings
    public const TOKEN = '';
    public const TARGET_USER = 'k-takeuchi220';

    protected $urls;
    protected const GIT_API_URL = 'https://api.github.com/';
    protected const FOLLOWERS_URL = 0;
    protected const FOLLOWING_URL = 1;

    public function __construct()
    {
        $user = self::TARGET_USER;
        $this->urls = [
            self::FOLLOWERS_URL => self::GIT_API_URL."users/${user}/followers",
            self::FOLLOWING_URL => self::GIT_API_URL."users/${user}/following",
        ];
    }

    /**
     * 片思いフォロー一覧取得
     */
    public function getOnlyMyselfFollowings(): array
    {
        $followings = $this->getFollowings();
        $followers = $this->getFollowers();

        return array_diff($followings, $followers);
    }

    /**
     * 片思われフォロー一覧取得
     */
    public function getOnlyOtherFollowers(): array
    {
        $followings = $this->getFollowings();
        $followers = $this->getFollowers();

        return array_diff($followers, $followings);
    }

    /**
     * フォロワー一覧取得
     */
    public function getFollowers(): array
    {
        $url = $this->urls[self::FOLLOWERS_URL];
        $datas = $this->curl($url, self::TOKEN);

        $followings = [];
        foreach ($datas as $data) {
            $followings[] = $data['login'];
        }
        return $followings;
    }

    /**
     * フォロー一覧取得
     */
    public function getFollowings(): array
    {
        $url = $this->urls[self::FOLLOWING_URL];
        $datas = $this->curl($url, self::TOKEN);

        $followings = [];
        foreach ($datas as $data) {
            $followings[] = $data['login'];
        }
        return $followings;
    }

    protected function curl(string $url, string $token, string $type='GET'): array
    {
        $options = [
            'http' => [
                'method' => $type,
                'header' => [
                    'User-Agent: My User Agent',
                    'Authorization: bearer '.$token,
                    'Content-type: application/json; charset=UTF-8',
                ],
            ],
        ];

        $context = stream_context_create($options);
        $contents = file_get_contents($url, false, $context);
    
        $datas = json_decode($contents, true);
        return $datas;
    }
}

?>

