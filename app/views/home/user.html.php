<h2><?= $user->username ?></h2>
<? if (count($tweets) > 0): ?>
  <div class="user">
    <? $twitter_user = $tweets[0]->user; ?>
    <img src="<?= $twitter_user->profile_image_url ?>" />
  </div>
  <div class="tweets">
    <? foreach ($tweets as $tweet): ?>
      <div class="tweet">
        <?= $tweet->text ?>
      </div>
    <? endforeach; ?>
  </div>
  - <a href="http://twitter.com/<?= $user->username ?>">http://twitter.com/<?= $user->username ?></a>
<? else: ?>
  No tweets!
<? endif; ?>


<div>
  <a href="/users/">view all the users you've added</a>
</div>