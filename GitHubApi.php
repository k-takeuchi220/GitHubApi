<?php

class GitHubApi
{
    protected $token;
    protected $userId;

    protected const GIT_API_URL = 'https://api.github.com/';
    protected const FOLLOWERS_URL_FORMAT = self::GIT_API_URL."users/%s/followers";
    protected const FOLLOWING_URL_FORMAT = self::GIT_API_URL."users/%s/following";
    protected const FOLLOW_URL_FORMAT = self::GIT_API_URL."user/following/%s";

    public function __construct(string $token, string $userId)
    {
        $this->token = $token;
        $this->userId = $userId;
    }

    /**
     * 指定ユーザのフォロー
     */
    public function follow(string $userId): void
    {
        $url = sprintf(self::FOLLOW_URL_FORMAT, $userId);
        $this->curl($url, $this->token, 'PUT');
    }

    /**
     * 指定ユーザのフォロー解除
     */
    public function unfollow(string $userId): void
    {
        $url = sprintf(self::FOLLOW_URL_FORMAT, $userId);
        $this->curl($url, $this->token, 'DELETE');
    }

    /**
     * 片思いフォロー一覧取得
     */
    public function getUnrequitedFollowings(): array
    {
        $followings = $this->getFollowings();
        $followers = $this->getFollowers();

        return array_diff($followings, $followers);
    }

    /**
     * 片思われフォロー一覧取得
     */
    public function getUnrequitedFollowers(): array
    {
        $followings = $this->getFollowings();
        $followers = $this->getFollowers();

        return array_diff($followers, $followings);
    }

    /**
     * フォロワー一覧取得
     */
    public function getFollowers(int $page = 1, int $perPage = 100): array
    {
        $userId = $this->userId;
        $url = sprintf(self::FOLLOWERS_URL_FORMAT, $userId);

        $query = ['page' => $page, 'per_page' => $perPage];
        $datas = $this->curl($url, $this->token, 'GET', $query);

        $followers = array_column($datas, 'login');
        return $followers;
    }

    /**
     * フォロー一覧取得
     */
    public function getFollowings(int $page = 1, int $perPage = 100): array
    {
        $userId = $this->userId;
        $url = sprintf(self::FOLLOWING_URL_FORMAT, $userId);

        $query = ['page' => $page, 'per_page' => $perPage];
        $datas = $this->curl($url, $this->token, 'GET', $query);

        $followings = array_column($datas, 'login');
        return $followings;
    }

    protected function curl(string $url, string $token, string $type, array $query = []): array
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

        if (!empty($query)) {
            $url .= '?'.http_build_query($query);
        }

        $context = stream_context_create($options);
        $contents = file_get_contents($url, false, $context);
    
        $datas = json_decode($contents, true);
        return $datas ?? [];
    }
}

?>

