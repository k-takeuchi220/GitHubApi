<?php

class GitHubApi
{
    protected $token;

    protected $userId;

    // [userId][page_perPage] => followers
    protected $_cacheFollowers;

    // [userId][page_perPage] => followings
    protected $_cacheFollowings;

    protected const GIT_API_URL = 'https://api.github.com/';
    protected const FOLLOWERS_URL_FORMAT = self::GIT_API_URL."users/%s/followers";
    protected const FOLLOWING_URL_FORMAT = self::GIT_API_URL."users/%s/following";
    protected const FOLLOW_URL_FORMAT = self::GIT_API_URL."user/following/%s";

    // 使えないらしい
    // protected const CHECK_FOLLOW_URL_FORMAT = self::GIT_API_URL."user/%s/following/%s";

    protected const MAX_PER_PAGE = 100;
    
    public function __construct(string $token, string $userId)
    {
        $this->token = $token;
        $this->userId = $userId;
    }

    //======================================== 自身に対する処理 ========================================/

    public function getUserId(): string
    {
        return $this->userId;
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
     * フォロー一覧取得
     */
    public function getFollowings(int $page = 1, int $perPage = 100): array
    {
        $userId = $this->getUserId();
        $followings = $this->getFollowingsByUserId($userId, $page, $perPage);
        return $followings;
    }

    /**
     * フォロワー一覧取得
     */
    public function getFollowers(int $page = 1, int $perPage = 100): array
    {
        $userId = $this->getUserId();
        $followers = $this->getFollowersByUserId($userId, $page, $perPage);
        return $followers;
    }

    /**
     * 全てのフォロー一覧取得
     * default 上限10ページ 1000ユーザまで
     */
    public function getAllFollowings(int $maxPage = 10): array
    {
        $userId = $this->getUserId();
        $followings = $this->getAllFollowingsByUserId($userId, $maxPage);
        return $followings;
    }

    /**
     * 全てのフォロワー一覧取得
     * default 上限10ページ 1000ユーザまで
     */
    public function getAllFollowers(int $maxPage = 10): array
    {
        $userId = $this->getUserId();
        $followers = $this->getAllFollowersByUserId($userId, $maxPage);
        return $followers;
    }

    /**
     * 片思いフォロー一覧取得
     */
    public function getUnrequitedFollowings(int $maxPage = 10): array
    {
        $userId = $this->getUserId();
        $unrequitedFollowings = $this->getUnrequitedFollowingsByUserId($userId, $maxPage);

        return $unrequitedFollowings;
    }

    /**
     * 片思われフォロー一覧取得
     */
    public function getUnrequitedFollowers(int $maxPage = 10): array
    {
        $userId = $this->getUserId();
        $unrequitedFollowers = $this->getUnrequitedFollowersByUserId($userId, $maxPage);

        return $unrequitedFollowers;
    }

    //======================================== 共通処理 ========================================//

    /**
     * 該当ユーザのフォロ一覧取得
     */
    public function getFollowingsByUserId(string $userId, int $page = 1, int $perPage = 100): array
    {
        $pageKey = $this->makePageKey($page, $perPage);
        if (!empty($this->_cacheFollowings) && isset($this->_cacheFollowings[$userId]) && isset($this->_cacheFollowings[$userId][$pageKey])) {
            return $this->_cacheFollowings[$userId];
        }
        $url = sprintf(self::FOLLOWING_URL_FORMAT, $userId);
        $query = ['page' => $page, 'per_page' => $perPage];
        $datas = $this->curl($url, $this->token, 'GET', $query);
        $followings = array_column($datas, 'login');
        $this->_cacheFollowings[$userId][$pageKey] = $followings;

        return $followings;
    }
    
    /**
     * 該当ユーザのフォロワー一覧取得
     */
    public function getFollowersByUserId(string $userId, int $page = 1, int $perPage = 100): array
    {
        $pageKey = $this->makePageKey($page, $perPage);
        if (!empty($this->_cacheFollowers) && isset($this->_cacheFollowers[$userId]) && isset($this->_cacheFollowers[$userId][$pageKey])) {
            return $this->_cacheFollowers[$userId][$pageKey];
        }
        $url = sprintf(self::FOLLOWERS_URL_FORMAT, $userId);
        $query = ['page' => $page, 'per_page' => $perPage];
        $datas = $this->curl($url, $this->token, 'GET', $query);
        $followers = array_column($datas, 'login');
        $this->_cacheFollowers[$userId][$pageKey] = $followers;

        return $followers;
    }

    /**
     * 該当ユーザの全てのフォロー一覧取得
     * default 上限10ページ 1000ユーザまで
     */
    public function getAllFollowingsByUserId(string $userId, int $maxPage = 10): array
    {
        $followings = [];
        $perPage = self::MAX_PER_PAGE;
        for ($page = 1; $page <= $maxPage; ++$page) {
            $followingsPage = $this->getfollowingsByUserId($userId, $page, $perPage);
            if (empty($followingsPage)) {
                break;
            }
            $followings = array_merge($followings, $followingsPage);
        }

        return $followings;
    }

    /**
     * 該当ユーザの全てのフォロワー一覧取得
     * default 上限10ページ 1000ユーザまで
     */
    public function getAllFollowersByUserId(string $userId, int $maxPage = 10): array
    {
        $followers = [];
        $perPage = self::MAX_PER_PAGE;
        for ($page = 1; $page <= $maxPage; ++$page) {
            $followersPage = $this->getFollowersByUserId($userId, $page, $perPage);
            if (empty($followersPage)) {
                break;
            }
            $followers = array_merge($followers, $followersPage);
        }

        return $followers;
    }

    /**
     * 該当ユーザの片思いフォロー一覧取得
     */
    public function getUnrequitedFollowingsByUserId(string $userId, int $maxPage = 10): array
    {
        $followings = $this->getAllFollowingsByUserId($userId, $maxPage);
        $followers = $this->getAllFollowersByUserId($userId, $maxPage);

        return array_diff($followings, $followers);
    }

    /**
     * 該当ユーザの片思われフォロー一覧取得
     */
    public function getUnrequitedFollowersByUserId(string $userId, int $maxPage = 10): array
    {
        $followings = $this->getAllFollowingsByUserId($userId, $maxPage);
        $followers = $this->getAllFollowersByUserId($userId, $maxPage);

        return array_diff($followers, $followings);
    }

    protected function makePageKey(int $page, int $perPage): string
    {
        return $page.'_'.$perPage;
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

