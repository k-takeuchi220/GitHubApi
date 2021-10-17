# GitHubApi

Twitterのように片思い、片思われフォローを調べたり、片思いフォロー一括解除のようなことができたらな、と思い作成しました。  
  
参考： https://docs.github.com/ja/rest/reference/users  
トークン生成： https://github.com/settings/tokens  
  
example  
```
require './GitHubApi.php';
$token = 'your token';
$userId = 'your github id';
$gitHubApi = new GitHubApi($token, $userId);
// 片思いフォローの一括解除
$followings = $gitHubApi->getUnrequitedFollowings();
foreach ($followings as $following) {
    $gitHubApi->unfollow($following);
}
// 片思われフォローの一括フォロー
$followers = $gitHubApi->getUnrequitedFollowers();
foreach ($followers as $follower) {
    $gitHubApi->follow($follower);
}
```
